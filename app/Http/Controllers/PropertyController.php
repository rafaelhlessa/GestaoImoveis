<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\User;
use App\Models\PropertyUser;
use App\Models\PropertyCoOwner;
use App\Models\Authorization;
use App\Models\TypeOwnership;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Property::class);

        $user = Auth::user();
        if ($user instanceof \App\Models\User) {
            if (!$user->relationLoaded('profiles')) {
                $user->load('profiles');
            }
        }

        // Proprietários e prestadores podem acessar suas próprias propriedades
        if ($user instanceof \App\Models\User && ($user->hasProfile('proprietario') || $user->hasProfile('prestador'))) {
            $cacheKey = 'properties_user_' . $user->id;
            $properties = Cache::remember($cacheKey, 60, function () use ($user) {
                return Property::select([
                    'properties.id', 'properties.is_active', 'properties.title_deed', 'properties.title_deed_number',
                    'properties.area', 'properties.unit', 'properties.type_property', 'properties.property_category', 'properties.property_subtype', 'properties.address',
                    'properties.city', 'properties.district', 'properties.locality', 'properties.nickname',
                    'properties.created_at', 'properties.updated_at'
                ])->whereHas('owners', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with(['owners', 'owners.typeOwnership'])
                ->paginate(20);
            });

            // Carregar imagens separadamente para evitar problemas de cache
            foreach ($properties->items() as $property) {
                $fullProperty = Property::select(['id', 'file_photo'])->find($property->id);
                $property->file_photo = $fullProperty ? $fullProperty->file_photo : null;
            }

            // Debug: verificar se há propriedades null
            Log::info('Properties debug:', [
                'user_id' => $user->id,
                'properties_count' => $properties->count(),
                'properties_items' => $properties->items(),
                'has_null_items' => collect($properties->items())->contains(null)
            ]);

            // Usar o componente correto para propriedades próprias
            return Inertia::render('Properties/IndexProperty', [
                'properties' => $properties,
                'can' => [
                    'update' => collect($properties->items())->pluck('id')->mapWithKeys(function ($id) {
                        $property = Property::find($id);
                        return [$id => Gate::allows('update', $property)];
                    }),
                ],
            ]);
        } else {
            // Outros perfis não têm acesso
            return $this->returnUnauthorizedError(
                'Você não tem permissão para acessar esta área.',
                'access_denied'
            );
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $currentUser = Auth::user();
        if ($currentUser instanceof \App\Models\User) {
            if (!$currentUser->relationLoaded('profiles')) {
                $currentUser->load('profiles');
            }
        }

    if (!($currentUser instanceof \App\Models\User) || (!$currentUser->hasProfile('proprietario') && !$currentUser->hasProfile('prestador'))) {
            return $this->returnUnauthorizedError(
                'Você não tem permissão para criar propriedades.',
                'general'
            );
        }

        // Lógica para garantir o comportamento correto na seleção de proprietário
        $profiles = ($currentUser instanceof \App\Models\User) ? $currentUser->profiles->pluck('slug')->toArray() : [];
        $isOwnerOnly = in_array('proprietario', $profiles) && !in_array('prestador', $profiles);
        $isProviderOnly = in_array('prestador', $profiles) && !in_array('proprietario', $profiles);
        $isBoth = in_array('proprietario', $profiles) && in_array('prestador', $profiles);

        $authorizedOwners = [];
        if ($isProviderOnly || $isBoth) {
                $authorizedOwnerIds = DB::table('authorizations')
                ->where('service_provider_id', $currentUser->id)
                ->where('can_create_properties', 1)
                ->pluck('owner_id')
                ->toArray();
            $authorizedOwners = \App\Models\User::whereIn('id', $authorizedOwnerIds)
                ->get()
                ->map([$this, 'formatUser'])
                ->toArray();
        }

        if ($isOwnerOnly) {
            $users = [ $this->formatUser($currentUser) ];
        } elseif ($isProviderOnly) {
            $users = $authorizedOwners;
        } elseif ($isBoth) {
            $users = array_merge([ $this->formatUser($currentUser) ], $authorizedOwners);
        } else {
            $users = [];
        }
        $authorizations = $this->getUserAuthorizations($currentUser);


        return Inertia::render('Properties/CreateProperty', [
            'mode' => 'create',
            'typeOwners' => TypeOwnership::all()->map(function($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                ];
            }),
            'users' => $users,
            'authorizations' => $authorizations,
            'currentUser' => $this->formatUser($currentUser),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePropertyRequest $request)
    {
        $validated = $request->validated();
        $currentUser = Auth::user();
        if ($currentUser instanceof \App\Models\User) {
            if (!$currentUser->relationLoaded('profiles')) {
                $currentUser->load('profiles');
            }
        }

        try {
            // ✅ CORREÇÃO: Proprietários (perfil 1) sempre podem adicionar a si mesmos
            if ($request->has('owners') && is_array($request->owners)) {
                // Só valida permissões se NÃO for proprietário puro
                if (!($currentUser instanceof \App\Models\User) || !$currentUser->hasProfile('proprietario')) {
                    $this->validateOwnerPermissions($currentUser, $request->owners);
                }

                // Valida percentuais independente do perfil
                $this->validateOwnershipPercentages($request->owners);
            }

            $propertyId = null;

            // Determina o owner_id principal
            $ownerId = $this->determineMainOwnerId($validated['owners'] ?? [], $currentUser);
            $validated['owner_id'] = $ownerId;

            DB::transaction(function () use ($validated, $request, &$propertyId, $currentUser) {

                $property = Property::create($validated);

                // Inserindo Proprietários e Co-proprietários
                if ($request->has('owners') && is_array($request->owners)) {
                    foreach ($request->owners as $index => $owner) {
                        Log::info("Processando proprietário {$index}:", is_array($owner) ? $owner : []);

                        try {
                            // Nunca confundir id do registro (PropertyUser/PropertyCoOwner) com id de usuário
                            $userId = $owner['user_id'] ?? ($owner['user']['id'] ?? null);
                            $typeOwnershipId = $owner['type_ownership_id'] ?? $owner['type_ownership'] ?? null;
                            $percentage = $owner['percentage'] ?? $owner['percent'] ?? 0;
                            $percentage = is_numeric($percentage) ? (float) $percentage : 0.0;

                            if ($userId === null || $userId === '') {
                                // Co-proprietário (sem cadastro)
                                $name = $owner['name'] ?? null;
                                if (!$name) {
                                    throw new \InvalidArgumentException('Nome do co-proprietário é obrigatório.');
                                }
                                // Default seguro para tipo de propriedade (1 = Proprietário), se não vier do front
                                if ($typeOwnershipId === null) {
                                    $typeOwnershipId = 1;
                                }

                                PropertyCoOwner::create([
                                    'property_id' => $property->id,
                                    'name' => $name,
                                    'cpf_cnpj' => $owner['cpf_cnpj'] ?? null,
                                    'percentage' => $percentage,
                                    'type_ownership_id' => $typeOwnershipId,
                                    'observations' => $owner['observations'] ?? $owner['other'] ?? null,
                                ]);

                                Log::info('PropertyCoOwner criado para nova propriedade', ['index' => $index, 'name' => $name]);
                            } else {
                                // Proprietário registrado
                                if ($typeOwnershipId === null) {
                                    $typeOwnershipId = 1;
                                }

                                PropertyUser::create([
                                    'owner_id' => $userId,
                                    'user_id' => $userId,
                                    'type_ownership_id' => $typeOwnershipId,
                                    'percentage' => $percentage,
                                    'other' => $owner['observations'] ?? $owner['other'] ?? null,
                                    'property_id' => $property->id,
                                ]);

                                Log::info('PropertyUser criado para nova propriedade', ['index' => $index, 'user_id' => $userId]);
                            }
                        } catch (\Exception $e) {
                            Log::error("Erro ao criar proprietário {$index}", [
                                'error' => $e->getMessage(),
                                'owner_data' => $owner
                            ]);
                            throw $e;
                        }
                    }
                } else if ($currentUser->profiles->contains('slug', 'owner')) {
                    // ✅ Se é proprietário e não tem owners no request, adiciona automaticamente
                    PropertyUser::create([
                        'owner_id' => $currentUser->id,
                        'user_id' => $currentUser->id,
                        'type_ownership_id' => 1, // Tipo "Proprietário"
                        'percentage' => 100,
                        'other' => null,
                        'property_id' => $property->id,
                    ]);
                }

                // Inserindo Documentos
                if ($request->has('documents') && is_array($request->documents)) {
                    foreach ($request->documents as $doc) {
                        try {
                            if (($doc['file'] ?? null) === 'processing') { throw new \InvalidArgumentException('Arquivo ainda está processando no cliente.'); }
                            $normalized = $this->normalizeFileForStorage($doc['file'] ?? null, $doc['file_name'] ?? 'arquivo');
                            PropertyDocument::create([
                                'name' => $doc['name'],
                                'date' => ($doc['date'] === "Sem Data" || empty($doc['date'])) ? null : $doc['date'],
                                'show' => $doc['show'] ?? true,
                                'file' => $normalized, // Salvar Base64 normalizado
                                'file_name' => $doc['file_name'],
                                'property_id' => $property->id,
                            ]);

                        } catch (\Exception $e) {
                            Log::error("Erro ao salvar documento: " . $e->getMessage());
                            // Continua com os outros documentos mesmo se um falhar
                        }
                    }
                }

                $propertyId = $property->id;
            });

            return redirect()->route('property.show', $propertyId)
                ->with('success', 'Propriedade criada com sucesso.');

        } catch (\Exception $e) {
            Log::error('Erro ao criar propriedade: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar propriedade: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Property $property)
    {
        // Carrega o usuário com sua atividade
        $user = Auth::user();
        if ($user instanceof \App\Models\User) {
            $user = \App\Models\User::with(['activity', 'profiles'])->find($user->id);
        }

        // Carrega a propriedade com todos os relacionamentos necessários, incluindo file_photo para exibição
        $property = Property::select([
            'properties.id', 'properties.is_active', 'properties.title_deed', 'properties.title_deed_number',
            'properties.other', 'properties.area', 'properties.unit', 'properties.type_property', 'properties.property_category', 'properties.property_subtype',
            'properties.address', 'properties.city', 'properties.city_id', 'properties.district',
            'properties.locality', 'properties.nickname', 'properties.about', 'properties.created_at',
            'properties.updated_at', 'properties.file_photo' // ✅ Incluído file_photo para exibição
        ])->with([
            'owners', 'owners.typeOwnership',
            'documents' => function($query) {
                $query->select(['id', 'property_id', 'name', 'file_name', 'date', 'show', 'created_at', 'updated_at']);
                // Removido 'file' dos documentos para evitar dados binários grandes
            },
            'evaluations' => function($query) {
                $query->select([
                        'id',
                        'property_id',
                        'user_id',
                        'valuation',
                        'comments',
                        'property_type',
                        'urban_subtype',
                        'property_condition',
                        'pdf_path',
                        'owner_acknowledged',
                        'owner_acknowledged_at',
                        'created_at',
                        'updated_at'
                    ])
                      ->with(['user'])
                      ->orderBy('created_at', 'desc')
                      ->limit(10); // Limita a 10 avaliações mais recentes
            }
        ])->find($property->id);

        if (!$property) {
            return $this->returnUnauthorizedError(
                'Propriedade não encontrada.',
                'property_access'
            );
        }

        $canEdit = false;
        $hasAccess = false;
        $canEvaluate = false;

        // Verificar se é proprietário da propriedade
        $isOwnerOfProperty = PropertyUser::where('property_id', $property->id)
            ->where('user_id', $user->id)
            ->exists();

        // Lógica baseada em perfis acumuláveis
        if ($user instanceof \App\Models\User && $user->hasProfile('proprietario')) {
                $hasAccess = $isOwnerOfProperty;
                $canEdit = $hasAccess;
                $canEvaluate = false;
        } elseif ($user instanceof \App\Models\User && $user->hasProfile('prestador')) {
                // Query para verificar acesso a documentos
                $hasAccess = DB::table('authorizations')
                    ->where('service_provider_id', $user->id)
                    ->where('can_view_documents', 1)
                    ->whereExists(function ($query) use ($property) {
                        $query->select(DB::raw(1))
                            ->from('property_user')
                            ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                            ->where('property_user.property_id', $property->id);
                    })
                    ->exists();

            // Query para verificar permissão de criação/edição
            $canEdit = DB::table('authorizations')
                ->where('service_provider_id', $user->id)
                ->where('can_create_properties', 1)
                ->whereExists(function ($query) use ($property) {
                    $query->select(DB::raw(1))
                        ->from('property_user')
                        ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                        ->where('property_user.property_id', $property->id);
                })
                ->exists();

            // Permissão de avaliação para prestadores de serviço
            $hasAuthorizationToEvaluate = DB::table('authorizations')
                ->where('service_provider_id', $user->id)
                ->where('evaluation_permission', 1)
                ->whereExists(function ($query) use ($property) {
                    $query->select(DB::raw(1))
                        ->from('property_user')
                        ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                        ->where('property_user.property_id', $property->id);
                })
                ->exists();

            $canEvaluate = $hasAuthorizationToEvaluate &&
                        $user->activity &&
                        (bool) $user->activity->evaluation_permission;
        } else {
            $hasAccess = false;
            $canEdit = false;
            $canEvaluate = false;
        }

        if (!$hasAccess) {
            // ✅ Usar modal personalizado em vez de abort()
            return $this->returnUnauthorizedError(
                'Você não tem permissão para acessar esta propriedade.',
                'property_access'
            );
        }

        // Carregar TypeOwnership para o frontend
        $typeOwnership = TypeOwnership::all();

        // Debug detalhado para verificar as permissões
        Log::info('=== ACESSO À PROPRIEDADE ===', [
            'property_id' => $property->id,
            'property_name' => $property->nickname,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_profile' => $user->profiles->pluck('slug')->toArray(),
            'is_owner_of_property' => $isOwnerOfProperty,
            'route_name' => request()->route()->getName(),
            'permissions_calculated' => [
                'can_evaluate' => $canEvaluate,
                'can_edit' => $canEdit,
                'has_access' => $hasAccess
            ]
        ]);
        
        // ✅ Carregando todos os tipos de proprietários para exibição
        $registeredOwners = $property->owners->map(function ($owner) {
            return [
                'id' => $owner->id,
                'user_id' => $owner->pivot->user_id,
                'name' => $owner->name,
                'cpf_cnpj' => $owner->cpf_cnpj,
                'percentage' => $owner->pivot->percentage,
                'type_ownership_id' => $owner->pivot->type_ownership_id,
                'observations' => $owner->pivot->other,
                'type' => 'registered'
            ];
        });


        $coOwners = PropertyCoOwner::with('typeOwnership')
            ->where('property_id', $property->id)
            ->get()
            ->map(function ($coOwner) {
                return [
                    'id' => $coOwner->id,
                    'user_id' => null,
                    'name' => $coOwner->name,
                    'cpf_cnpj' => $coOwner->cpf_cnpj,
                    'percentage' => $coOwner->percentage,
                    'type_ownership_id' => $coOwner->type_ownership_id,
                    'observations' => $coOwner->observations,
                    'type' => 'co-owner'
                ];
            });
    

        $allOwners = $registeredOwners->concat($coOwners);

        // Carregar avaliações com mídia
        $property->load(['evaluations.media', 'evaluations.user']);

        return Inertia::render('Properties/ShowProperty', [
            'property' => $property,
            'documents' => $property->documents->toArray(),
            'owners' => $allOwners->toArray(),
            'evaluations' => $property->evaluations->map(function($e){
                return array_merge($e->toArray(), [
                    'pdf_url' => $e->pdf_url,
                ]);
            })->toArray(),
            'typeOwnership' => $typeOwnership->toArray(),
            'success' => session('success'),
            'isServiceProvider' => ($user instanceof \App\Models\User) ? $user->hasProfile('prestador') : false,
            'canEdit' => $canEdit,
            'canEvaluate' => $canEvaluate,
            'canView' => $hasAccess,
            'canCreate' => $canEdit,
            'userActivity' => $user->activity ? [
                'id' => $user->activity->id,
                'name' => $user->activity->name,
                'evaluation_permission' => $user->activity->evaluation_permission,
            ] : null,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Property $property)
    {
        $currentUser = Auth::user();
        if ($currentUser instanceof \App\Models\User) {
            if (!$currentUser->relationLoaded('profiles')) {
                $currentUser->load('profiles');
            }
        }

        // ✅ Verificar permissões ANTES do Gate
        if (!$this->canEditProperty($currentUser, $property)) {
            Log::warning('EDIT NEGADO - canEditProperty retornou false');
            return $this->returnUnauthorizedError(
                'Você não tem permissão para editar esta propriedade.',
                'property_access'
            );
        }

        // ✅ Gate::authorize após verificação customizada
        try {
            Gate::authorize('update', $property);
            Log::info('Gate::authorize passou com sucesso');
        } catch (\Exception $e) {
            Log::error('Gate::authorize falhou:', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return $this->returnUnauthorizedError(
                'Falha na autorização do sistema: ' . $e->getMessage(),
                'property_access'
            );
        }

        $property = Property::select([
            'properties.id', 'properties.is_active', 'properties.title_deed', 'properties.title_deed_number',
            'properties.other', 'properties.area', 'properties.unit', 'properties.type_property', 'properties.property_category', 'properties.property_subtype',
            'properties.address', 'properties.city', 'properties.city_id', 'properties.district',
            'properties.locality', 'properties.nickname', 'properties.about', 'properties.created_at',
            'properties.updated_at', 'properties.file_photo'
        ])->with([
            'documents' => function($query) {
                $query->select(['id', 'property_id', 'name', 'file_name', 'date', 'show', 'created_at', 'updated_at']);
            },
            'owners', 'owners.typeOwnership'
        ])->findOrFail($property->id);

        // ✅ CORREÇÃO: Carregar proprietários registrados
        $registeredOwners = PropertyUser::with(['typeOwnership'])
            ->where('property_id', $property->id)
            ->get()
            ->map(function ($owner) {
                $user = User::find($owner->user_id);
                return [
                    'id' => $owner->id,
                    'user_id' => $owner->user_id,
                    'name' => $user ? $user->name : 'Usuário não encontrado',
                    'cpf_cnpj' => $user ? $user->cpf_cnpj : null,
                    'percentage' => $owner->percentage,
                    'type_ownership_id' => $owner->type_ownership_id,
                    'observations' => $owner->other,
                    'type' => 'registered',
                    'profiles' => $user ? $user->profiles->pluck('slug')->toArray() : [],
                ];
            });

        // ✅ CORREÇÃO: Carregar co-proprietários (sem cadastro)
        $coOwners = PropertyCoOwner::with('typeOwnership')
            ->where('property_id', $property->id)
            ->get()
            ->map(function ($coOwner) {
                return [
                    'id' => $coOwner->id,
                    'user_id' => null, // Co-proprietário não tem user_id
                    'name' => $coOwner->name,
                    'cpf_cnpj' => $coOwner->cpf_cnpj,
                    'percentage' => $coOwner->percentage,
                    'type_ownership_id' => $coOwner->type_ownership_id,
                    'observations' => $coOwner->observations,
                    'type' => 'co-owner',
                ];
            });

        // ✅ Combinar ambos os tipos de proprietários
        $allOwners = $registeredOwners->concat($coOwners);

        Log::info('=== DEBUG EDIT PROPERTY - SUCESSO ===', [
            'registered_owners_count' => $registeredOwners->count(),
            'co_owners_count' => $coOwners->count(),
            'total_owners' => $allOwners->count(),
        ]);

        return Inertia::render('Properties/EditProperty', [
            'mode' => 'edit',
            'property' => $property,
            'typeOwners' => TypeOwnership::all()->map(function($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                ];
            }),
            'users' => $this->getAvailableUsers($currentUser),
            'authorizations' => $this->getUserAuthorizations($currentUser),
            'currentUser' => $this->formatUser($currentUser),
            'owners' => $allOwners->toArray(), // ✅ Todos os proprietários combinados
            'documents' => PropertyDocument::select(['id', 'property_id', 'name', 'file_name', 'date', 'show', 'created_at', 'updated_at'])
                                        ->where('property_id', $property->id)
                                        ->get(),
        ]);
    }

    /**
     * ✅ CORREÇÃO: Método update sem validação desnecessária para proprietários
     */
    public function update(UpdatePropertyRequest $request, string $id)
    {
        $property = Property::findOrFail($id);
        $currentUser = Auth::user();
        if ($currentUser instanceof \App\Models\User) {
            if (!$currentUser->relationLoaded('profiles')) {
                $currentUser->load('profiles');
            }
        }

        try {
            // ✅ Validar permissões: usuários com perfil "proprietario" podem gerenciar owners (mesmo se também forem prestadores)
            if ($request->has('owners') && is_array($request->owners)) {
                if (!($currentUser instanceof \App\Models\User) || !$currentUser->hasProfile('proprietario')) {
                    Log::info('Validando permissões de proprietários (usuário sem perfil proprietario)');
                    $this->validateOwnerPermissions($currentUser, $request->owners);
                } else {
                    Log::info('Pulando validação de permissões - usuário possui perfil proprietario');
                }

                // Valida percentuais independente do perfil
                $this->validateOwnershipPercentages($request->owners);
            }

            DB::transaction(function () use ($request, $property) {
                // Atualizar dados básicos da propriedade (preservando foto se não enviada)
                $propertyData = $request->except(['documents', 'owners']);

                // ✅ Só atualiza file_photo se foi enviada uma nova
                if (!$request->has('file_photo') || empty($request->file_photo)) {
                    unset($propertyData['file_photo']);
                }

                $property->update($propertyData);

                // ✅ CORREÇÃO: Atualizar proprietários (registrados E co-proprietários)
                if ($request->has('owners') && is_array($request->owners)) {
                    Log::info('Atualizando proprietários', ['owners_count' => count($request->owners)]);

                    // Deletar proprietários existentes
                    PropertyUser::where('property_id', $property->id)->delete();
                    PropertyCoOwner::where('property_id', $property->id)->delete();

                    foreach ($request->owners as $index => $owner) {
                        try {
                            // Nunca usar owner['id'] como user_id; considerar apenas user_id explícito ou owner.user.id
                            $userId = $owner['user_id'] ?? ($owner['user']['id'] ?? null);
                            $typeOwnershipId = $owner['type_ownership_id'] ?? $owner['type_ownership'] ?? null;
                            $percentage = $owner['percentage'] ?? $owner['percent'] ?? 0;
                            $percentage = is_numeric($percentage) ? (float) $percentage : 0.0;

                            if ($userId === null || $userId === '') {
                                // ✅ Co-proprietário (sem cadastro) → PropertyCoOwner
                                $name = $owner['name'] ?? null;
                                if (!$name) {
                                    throw new \InvalidArgumentException('Nome do co-proprietário é obrigatório.');
                                }

                                if ($typeOwnershipId === null) {
                                    $typeOwnershipId = 1; // Default: Proprietário
                                }

                                PropertyCoOwner::create([
                                    'property_id' => $property->id,
                                    'name' => $name,
                                    'cpf_cnpj' => $owner['cpf_cnpj'] ?? null,
                                    'percentage' => $percentage,
                                    'type_ownership_id' => $typeOwnershipId,
                                    'observations' => $owner['observations'] ?? $owner['other'] ?? null,
                                ]);

                                Log::info('PropertyCoOwner atualizado', ['index' => $index, 'name' => $name]);
                            } else {
                                // ✅ Proprietário registrado → PropertyUser
                                if ($typeOwnershipId === null) {
                                    $typeOwnershipId = 1;
                                }

                                PropertyUser::create([
                                    'owner_id' => $userId,
                                    'user_id' => $userId,
                                    'type_ownership_id' => $typeOwnershipId,
                                    'percentage' => $percentage,
                                    'other' => $owner['observations'] ?? $owner['other'] ?? null,
                                    'property_id' => $property->id,
                                ]);

                                Log::info('PropertyUser atualizado', ['index' => $index, 'user_id' => $userId]);
                            }
                        } catch (\Exception $e) {
                            Log::error("Erro ao atualizar proprietário {$index}", [
                                'error' => $e->getMessage(),
                                'owner_data' => $owner
                            ]);
                            throw $e;
                        }
                    }
                }

                // ✅ Atualizar documentos (APENAS ADICIONAR NOVOS)
                if ($request->has('documents') && is_array($request->documents) && !empty($request->documents)) {
                    Log::info('Adicionando novos documentos', ['count' => count($request->documents)]);

                    foreach ($request->documents as $document) {
                        if (isset($document['file']) && !empty($document['file'])) {
                            try {
                                $existingDoc = PropertyDocument::where('property_id', $property->id)
                                    ->where('file_name', $document['file_name'])
                                    ->first();

                                if ($existingDoc) {
                                    // Atualiza documento existente
                                    $normalized = $this->normalizeFileForStorage($document['file'] ?? null, $document['file_name'] ?? 'arquivo');
                                    $existingDoc->update([
                                        'name' => $document['name'],
                                        'date' => ($document['date'] === "Sem Data" || empty($document['date'])) ? null : $document['date'],
                                        'show' => $document['show'] ?? true,
                                        'file' => $normalized,
                                        'file_name' => $document['file_name'],
                                    ]);
                                    Log::info('Documento atualizado', ['file_name' => $document['file_name']]);
                                } else {
                                    // Cria novo documento
                                    $normalized = $this->normalizeFileForStorage($document['file'] ?? null, $document['file_name'] ?? 'arquivo');
                                    PropertyDocument::create([
                                        'name' => $document['name'],
                                        'date' => ($document['date'] === "Sem Data" || empty($document['date'])) ? null : $document['date'],
                                        'show' => $document['show'] ?? true,
                                        'file' => $normalized,
                                        'file_name' => $document['file_name'],
                                        'property_id' => $property->id,
                                    ]);
                                    Log::info('Novo documento criado', ['file_name' => $document['file_name']]);
                                }
                            } catch (\Exception $e) {
                                Log::error("Erro ao processar documento: " . $e->getMessage());
                            }
                        }
                    }
                }

                // ✅ Exclusão explícita de documentos
                if ($request->has('documents_to_delete') && is_array($request->documents_to_delete)) {
                    foreach ($request->documents_to_delete as $documentId) {
                        PropertyDocument::where('id', $documentId)
                            ->where('property_id', $property->id)
                            ->delete();
                        Log::info('Documento excluído', ['document_id' => $documentId]);
                    }
                }
            });

            Log::info('UPDATE realizado com sucesso');
            return redirect()->route('property.show', $id)
                ->with('success', 'Propriedade atualizada com sucesso.');

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar propriedade: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar propriedade. Tente novamente.');
        }
    }

    /**
     * ✅ CORREÇÃO: Método canEditProperty com logs detalhados
     */
    private function canEditProperty($user, $property)
    {
        if ($user instanceof \App\Models\User) {
            if (!$user->relationLoaded('profiles')) {
                $user->load('profiles');
            }
        }
        Log::info('=== canEditProperty - INÍCIO ===', [
            'user_id' => $user->id,
            'user_profiles' => ($user instanceof \App\Models\User) ? $user->profiles->pluck('slug')->toArray() : [],
            'property_id' => $property->id
        ]);

    if ($user instanceof \App\Models\User && $user->hasProfile('proprietario') && !$user->hasProfile('prestador')) {
            // Proprietário puro: verifica se é dono da propriedade
            $isOwner = PropertyUser::where('property_id', $property->id)
                ->where('user_id', $user->id)
                ->exists();

            Log::info('Proprietário puro - Verificação:', [
                'is_owner' => $isOwner,
                'sql_query' => PropertyUser::where('property_id', $property->id)
                    ->where('user_id', $user->id)
                    ->toSql(),
                'bindings' => [$property->id, $user->id]
            ]);

            return $isOwner;
        }

    if ($user instanceof \App\Models\User && $user->hasProfile('prestador') && !$user->hasProfile('proprietario')) {
            // Prestador puro: verifica autorização
            $canEdit = DB::table('authorizations')
                ->where('service_provider_id', $user->id)
                ->where('can_create_properties', 1)
                ->whereExists(function ($query) use ($property) {
                    $query->select(DB::raw(1))
                        ->from('property_user')
                        ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                        ->where('property_user.property_id', $property->id);
                })
                ->exists();

            Log::info('Prestador puro - Verificação:', ['can_edit' => $canEdit]);
            return $canEdit;
        }

    if ($user instanceof \App\Models\User && $user->hasProfile('proprietario') && $user->hasProfile('prestador')) {
            // Proprietário/Prestador: verifica primeiro se é proprietário
            $isOwner = PropertyUser::where('property_id', $property->id)
                ->where('user_id', $user->id)
                ->exists();

            Log::info('Proprietário/Prestador - Verificação proprietário:', ['is_owner' => $isOwner]);

            if ($isOwner) {
                return true;
            }

            // Se não é proprietário, verifica como prestador
            $canEditAsProvider = DB::table('authorizations')
                ->where('service_provider_id', $user->id)
                ->where('can_create_properties', 1)
                ->whereExists(function ($query) use ($property) {
                    $query->select(DB::raw(1))
                        ->from('property_user')
                        ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                        ->where('property_user.property_id', $property->id);
                })
                ->exists();

            Log::info('Proprietário/Prestador - Verificação prestador:', ['can_edit_as_provider' => $canEditAsProvider]);
            return $canEditAsProvider;
        }

        Log::warning('Perfis desconhecidos:', ['profiles' => $user->profiles->pluck('slug')->toArray()]);
        return false;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($property)
    {
        Gate::authorize('delete', $property);
        $property->delete();
        return redirect()->route('properties.index');
    }

    // ====================================
    // MÉTODOS AUXILIARES - USANDO DB::table PARA EVITAR PROBLEMAS
    // ====================================

    /**
     * Retorna usuários disponíveis baseado no perfil do usuário
     */
    private function getAvailableUsers($currentUser)
    {
        // Proprietário puro: só ele mesmo
        if ($currentUser instanceof \App\Models\User) {
            if (!$currentUser->relationLoaded('profiles')) {
                $currentUser->load('profiles');
            }
        }
        if ($currentUser instanceof \App\Models\User && $currentUser->hasProfile('proprietario') && !$currentUser->hasProfile('prestador')) {
            return [ $this->formatUser($currentUser) ];
        }
        // Prestador puro: apenas autorizados
    if ($currentUser instanceof \App\Models\User && $currentUser->hasProfile('prestador') && !$currentUser->hasProfile('proprietario')) {
            $authorizedOwnerIds = DB::table('authorizations')
                ->where('service_provider_id', $currentUser->id)
                ->where('can_create_properties', 1)
                ->pluck('owner_id')
                ->toArray();
            return User::whereIn('id', $authorizedOwnerIds)
                ->get()
                ->map([$this, 'formatUser'])
                ->toArray();
        }
        // Proprietário/Prestador: ele mesmo + autorizados
    if ($currentUser instanceof \App\Models\User && $currentUser->hasProfile('proprietario') && $currentUser->hasProfile('prestador')) {
            $authorizedOwnerIds = DB::table('authorizations')
                ->where('service_provider_id', $currentUser->id)
                ->where('can_create_properties', 1)
                ->pluck('owner_id')
                ->toArray();
            $authorizedOwnerIds[] = $currentUser->id;
            $authorizedOwnerIds = array_unique($authorizedOwnerIds);
            return User::whereIn('id', $authorizedOwnerIds)
                ->get()
                ->map([$this, 'formatUser'])
                ->toArray();
        }
        return [];
    }

    /**
     * Retorna autorizações do usuário atual
     */
    private function getUserAuthorizations($currentUser)
    {
        if ($currentUser instanceof \App\Models\User) {
            if (!$currentUser->relationLoaded('profiles')) {
                $currentUser->load('profiles');
            }
        }
        if ($currentUser instanceof \App\Models\User && $currentUser->hasProfile('prestador')) {
            // Usando query manual para evitar problemas com relacionamentos
            $authorizations = DB::table('authorizations')
                ->join('users', 'users.id', '=', 'authorizations.owner_id')
                ->where('authorizations.service_provider_id', $currentUser->id)
                ->where('authorizations.can_create_properties', 1)
                ->select(
                    'authorizations.id',
                    'authorizations.owner_id',
                    'authorizations.service_provider_id',
                    'authorizations.can_create_properties',
                    'users.id as user_id',
                    'users.name as user_name',
                    'users.cpf_cnpj as user_cpf_cnpj'
                )
                ->get()
                ->map(function ($auth) {
                    // Busca o perfil via Eloquent
                    $user = \App\Models\User::find($auth->user_id);
                    $profile = $user ? $user->profiles->pluck('slug')->toArray() : [];
                    return [
                        'id' => $auth->id,
                        'owner_id' => $auth->owner_id,
                        'service_provider_id' => $auth->service_provider_id,
                        'can_create_properties' => $auth->can_create_properties,
                        'owner' => [
                            'id' => $auth->user_id,
                            'name' => $auth->user_name,
                            'cpf_cnpj' => $auth->user_cpf_cnpj,
                            'profiles' => $profile,
                        ]
                    ];
                })
                ->toArray();

            return $authorizations;
        }

        return [];
    }

    /**
     * Valida se o usuário pode adicionar os proprietários especificados
     */
    private function validateOwnerPermissions($currentUser, array $owners)
    {
        if (empty($owners)) return;

        if ($currentUser instanceof \App\Models\User) {
            if (!$currentUser->relationLoaded('profiles')) {
                $currentUser->load('profiles');
            }
        }
        
        Log::info('=== validateOwnerPermissions ===', [
            'user_profiles' => ($currentUser instanceof \App\Models\User) ? $currentUser->profiles->pluck('slug')->toArray() : [],
            'owners_count' => count($owners)
        ]);

        // ✅ CORREÇÃO: Proprietário puro só pode adicionar a si mesmo
        if ($currentUser instanceof \App\Models\User && $currentUser->hasProfile('proprietario') && !$currentUser->hasProfile('prestador')) {
            foreach ($owners as $owner) {
                $userId = $owner['user_id'] ?? $owner['id'] ?? null;
                
                // ✅ Ignora co-proprietários (sem user_id) na validação
                if ($userId === null || $userId === '') {
                    Log::info('Co-proprietário detectado - pulando validação', ['name' => $owner['name'] ?? 'sem nome']);
                    continue;
                }
                
                if ($userId != $currentUser->id) {
                    Log::error('Proprietário tentando adicionar outro usuário:', [
                        'owner_id' => $userId,
                        'current_user_id' => $currentUser->id
                    ]);
                    throw new \Exception(
                        "Proprietários só podem adicionar a si mesmos como proprietário registrado."
                    );
                }
            }
            return;
        }

        // Para prestadores, verifica autorizações (apenas para proprietários registrados)
        $availableUsers = collect($this->getAvailableUsers($currentUser));
        $availableUserIds = $availableUsers->pluck('id')->toArray();

        Log::info('Usuários disponíveis para este perfil:', [
            'available_user_ids' => $availableUserIds
        ]);

        foreach ($owners as $owner) {
            $userId = $owner['user_id'] ?? $owner['id'] ?? null;
            
            // ✅ Ignora co-proprietários (sem user_id) na validação
            if ($userId === null || $userId === '') {
                Log::info('Co-proprietário detectado - pulando validação', ['name' => $owner['name'] ?? 'sem nome']);
                continue;
            }
            
            if (!in_array($userId, $availableUserIds)) {
                Log::error('Usuário não autorizado:', [
                    'user_id' => $userId,
                    'available_ids' => $availableUserIds
                ]);
                throw new \Exception(
                    "Você não tem permissão para adicionar o usuário ID {$userId} como proprietário."
                );
            }
        }
    }

    /**
     * Valida percentuais de propriedade
     */
    private function validateOwnershipPercentages(array $owners)
    {
        if (empty($owners)) return;

        // Separa proprietários por tipo
        $proprietarios = array_filter($owners, function($owner) {
            $typeId = $owner['type_ownership_id'] ?? $owner['type_ownership'];
            return $typeId == 1;
        });

        if (!empty($proprietarios)) {
            $totalProprietarios = array_sum(array_map(function($owner) {
                return floatval($owner['percentage'] ?? $owner['percent']);
            }, $proprietarios));

            if (abs($totalProprietarios - 100) > 0.01) {
                throw new \Exception(
                    "O percentual total dos proprietários deve ser 100%. Atual: {$totalProprietarios}%"
                );
            }
        }

        // Verifica duplicatas APENAS entre usuários cadastrados (ignora co-proprietários sem user_id)
        $userIds = array_values(array_filter(array_map(function($owner) {
            return $owner['user_id'] ?? $owner['id'] ?? null;
        }, $owners), function ($id) {
            return !is_null($id) && $id !== '';
        }));

        if (count($userIds) !== count(array_unique($userIds))) {
            throw new \Exception("Não é possível adicionar o mesmo usuário como proprietário mais de uma vez.");
        }
    }

    /**
     * Determina o owner_id principal da propriedade
     */
    private function determineMainOwnerId(array $owners, $currentUser)
    {
        if (empty($owners)) {
            return $currentUser->id;
        }

        // Procura proprietário (tipo 1) com 100% que tenha user_id válido (ignora co-proprietários)
        foreach ($owners as $owner) {
            $percentage = floatval($owner['percentage'] ?? $owner['percent'] ?? 0);
            $typeId = $owner['type_ownership_id'] ?? $owner['type_ownership'] ?? null;
            $uid = $owner['user_id'] ?? $owner['id'] ?? null;

            if ($typeId == 1 && $percentage == 100 && $uid) {
                return $uid;
            }
        }

        // Senão, pega o primeiro proprietário (tipo 1) que tenha user_id válido
        foreach ($owners as $owner) {
            $typeId = $owner['type_ownership_id'] ?? $owner['type_ownership'] ?? null;
            $uid = $owner['user_id'] ?? $owner['id'] ?? null;
            if ($typeId == 1 && $uid) {
                return $uid;
            }
        }

        // Fallback: usuário autenticado
        return $currentUser->id;
    }

    /**
     * Formata dados do usuário para o frontend
     */
    public function formatUser($user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'cpf_cnpj' => $user->cpf_cnpj,
            'profiles' => $user->profiles->pluck('slug')->toArray(),
        ];
    }

    /**
     * Limpa dados base64
     */
    private function cleanBase64($base64Data)
    {
        // Remove qualquer prefixo data:*;base64,
        return preg_replace('/^data:[^;]+;base64,/', '', (string) $base64Data);
    }

    /**
     * Normaliza e valida o payload do arquivo para armazenamento em BLOB (Base64 limpo)
     * - Aceita string com prefixo data: ou Base64 puro ou texto XML cru
     * - Retorna Base64 dos bytes do arquivo
     */
    private function normalizeFileForStorage(?string $filePayload, string $fileName): string
    {
        if (!$filePayload) {
            throw new \InvalidArgumentException('Arquivo não enviado.');
        }

        $clean = trim($this->cleanBase64($filePayload));

        // Tenta Base64 estrito primeiro
        $decodedStrict = base64_decode($clean, true);

        if ($decodedStrict !== false) {
            // Validação de tamanho (estimativa rápida sem decodificar novamente)
            $estimatedBytes = (int) floor(strlen($clean) * 0.75);
            if ($estimatedBytes > 6 * 1024 * 1024) {
                throw new \InvalidArgumentException('Arquivo excede o limite de 6MB.');
            }
            // Já está em Base64 limpo: evita re-encode para poupar memória
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if ($ext === 'kml') {
                // Validação leve: tenta olhar por <kml> nos primeiros bytes decodificados (até 2KB)
                $sample = substr($decodedStrict, 0, 2048);
                if (stripos($sample, '<kml') === false) {
                    Log::warning('KML salvo sem tag <kml> na amostra. Verifique a origem do arquivo.', ['file_name' => $fileName]);
                }
            }
            return $clean;
        } else {
            // Se falhar no Base64 estrito, tentar detectar XML cru (string começando por < ou contendo <kml)
            $text = ltrim($filePayload, "\xEF\xBB\xBF\x00\xFF\xFE\xFE\xFF"); // remove BOMs comuns
            $looksLikeXml = str_starts_with(trim($text), '<') || stripos($text, '<kml') !== false;
            if ($looksLikeXml) {
                $decoded = $text; // tratar como texto cru; armazenar bytes como estão
            } else {
                // última tentativa: Base64 não estrito
                $decoded = base64_decode($clean, false);
                if ($decoded === false) {
                    throw new \InvalidArgumentException('Conteúdo do arquivo inválido: não é Base64 nem XML.');
                }
            }
            // Se for KML, validar presença da tag <kml (não falhar para KMZ)
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if ($ext === 'kml') {
                $sample = is_string($decoded) ? substr($decoded, 0, 2048) : '';
                if ($sample && stripos($sample, '<kml') === false) {
                    Log::warning('KML salvo sem tag <kml> (modo texto/base64 flexível).', ['file_name' => $fileName]);
                }
            }

            // Retorna Base64 normalizado
            $encoded = base64_encode($decoded);
            $estimatedBytes = (int) floor(strlen($encoded) * 0.75);
            if ($estimatedBytes > 6 * 1024 * 1024) {
                throw new \InvalidArgumentException('Arquivo excede o limite de 6MB.');
            }
            return $encoded;
        }
    }

    // ====================================
    // MÉTODOS EXISTENTES
    // ====================================


    private function returnUnauthorizedError($message = null, $type = 'general')
    {
        $user = Auth::user();
        // Determina o tipo de erro baseado no contexto
        if ($type === 'general' && $user) {
            if ($user instanceof \App\Models\User && $user->hasProfile('proprietario') && !$user->hasProfile('prestador')) {
                $type = 'property_access';
            } elseif ($user instanceof \App\Models\User && $user->hasProfile('prestador')) {
                $type = 'service_provider';
            }
        }
        // Mensagem padrão se não fornecida
        if (!$message) {
            switch ($type) {
                case 'property_access':
                    $message = 'Você não tem permissão para acessar esta propriedade. Verifique se você é o proprietário ou possui autorização adequada.';
                    break;
                case 'document_access':
                    $message = 'Este documento não está disponível para visualização ou você não possui permissão para acessá-lo.';
                    break;
                case 'service_provider':
                    $message = 'Como prestador de serviço, você precisa de autorização específica do proprietário para acessar esta funcionalidade.';
                    break;
                default:
                    $message = 'Você não tem permissão para acessar esta página. Verifique suas credenciais e tente novamente.';
            }
        }
        return Inertia::render('Error/Unauthorized', [
            'message' => $message,
            'type' => $type,
            'redirectTo' => '/dashboard',
            'userProfiles' => $user ? $user->profiles->pluck('slug')->toArray() : [],
        ]);
    }

    public function updateDocument(Request $request, string $documentId)
    {
        $request->validate([
            'show' => 'required|boolean',
        ]);

        $document = PropertyDocument::findOrFail($documentId);
        $property = Property::findOrFail($document->property_id);
        $user = Auth::user();

        // Verificar se o usuário tem permissão para alterar a visibilidade
        $canEdit = $this->canEditProperty($user, $property);


        if (!$canEdit) {
            return $this->returnUnauthorizedError(
                'Você não tem permissão para alterar a visibilidade deste documento.',
                'document_access'
            );
        }

        $document->update(['show' => $request->show]);

        return redirect()->back()->with('success', 'Visibilidade do documento atualizada com sucesso.');
    }

    // Duplicate updateDocumentShow method removed to fix redeclaration error.

    // Duplicate clientShow method removed to fix redeclaration error.

    public function viewDocument($id)
    {
        $document = PropertyDocument::findOrFail($id);
        $property = Property::find($document->property_id);
        $user = Auth::user();

        // ✅ Verificar permissões de acesso ao documento
        if (!$property || !$this->checkDocumentAccess($user, $property, $document)) {
            return $this->returnUnauthorizedError(
                'Você não tem permissão para visualizar este documento.',
                'document_access'
            );
        }

        // Verificar se o documento existe no BLOB
        if (!$document->file) {
            return $this->returnUnauthorizedError(
                'Arquivo do documento não encontrado.',
                'document_access'
            );
        }

        // Decodificar Base64
        $fileContent = base64_decode($document->file);
        if ($fileContent === false) {
            return $this->returnUnauthorizedError(
                'Erro ao processar arquivo do documento.',
                'document_access'
            );
        }

        // Determinar MIME type baseado na extensão
        $mimeType = $document->getMimeType();

        return Response::make($fileContent, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"'
        ]);
    }

    public function clientsProperty($id = null)
    {
        $user = Auth::user();

        // Apenas prestadores de serviço podem acessar propriedades de clientes
    if (!($user instanceof \App\Models\User) || !$user->hasProfile('prestador')) {
            return $this->returnUnauthorizedError(
                'Apenas prestadores de serviço podem acessar propriedades de clientes.',
                'service_provider'
            );
        }

         $owner = User::findOrFail($id);

        // Verificar autorizações usando DB::table
        $authorizations = DB::table('authorizations')
            ->where('service_provider_id', $user->id)
            ->where('owner_id', $id)
            ->get();

        $canView = $authorizations->contains('can_view_documents', 1);
        $canCreateOwners = $authorizations->where('can_create_properties', 1)->pluck('owner_id')->toArray();

        $canCreate = !empty($canCreateOwners) ? [
            'can_create' => true,
            'owners' => $canCreateOwners
        ] : [
            'can_create' => false,
            'owners' => []
        ];

        if (!$canView && !$canCreate['can_create']) {
            return $this->returnUnauthorizedError(
                'Você não tem permissão para acessar as propriedades deste cliente. Solicite autorização ao proprietário.',
                'service_provider'
            );
        }

        $properties = Property::whereHas('owners', function ($query) use ($id) {
            $query->where('user_id', $id);
        })->with(['owners'])->get();

        // Usar o componente correto para propriedades de clientes
        return Inertia::render('Clients/IndexProperty', [
            'properties' => $properties,
            'owner' => $owner,
            'canView' => $canView,
            'canCreate' => $canCreate
        ]);
    }


    /**
     * Verifica se o usuário tem acesso ao documento
     */
    private function checkDocumentAccess($user, $property, $document)
    {
        // Se o documento não deve ser mostrado, apenas proprietários podem ver
        if (!$document->show) {
            return $this->isOwnerOfProperty($user, $property);
        }

        // Verificar baseado no perfil do usuário
    if ($user instanceof \App\Models\User && $user->hasProfile('proprietario') && !$user->hasProfile('prestador')) {
            // Proprietário puro
            return $this->isOwnerOfProperty($user, $property);
        }

    if ($user instanceof \App\Models\User && $user->hasProfile('prestador') && !$user->hasProfile('proprietario')) {
            // Prestador puro
            return $this->hasServiceProviderAccess($user, $property);
        }

    if ($user instanceof \App\Models\User && $user->hasProfile('proprietario') && $user->hasProfile('prestador')) {
            // Proprietário/Prestador: verifica ambos
            return $this->isOwnerOfProperty($user, $property) ||
                    $this->hasServiceProviderAccess($user, $property);
        }

        return false;
    }

    /**
     * Verifica se é proprietário da propriedade
     */
    private function isOwnerOfProperty($user, $property)
    {
        return PropertyUser::where('property_id', $property->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Verifica se prestador de serviço tem acesso
     */
    private function hasServiceProviderAccess($user, $property)
    {
    return DB::table('authorizations')
            ->where('service_provider_id', $user->id)
            ->where('can_view_documents', 1)
            ->whereExists(function ($query) use ($property) {
                $query->select(DB::raw(1))
                    ->from('property_user')
                    ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                    ->where('property_user.property_id', $property->id);
            })
            ->exists();
    }

    /**
     * Determina o MIME type baseado na extensão do arquivo
     */
    private function getMimeType($fileName)
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $mimeTypes = [
            'kml' => 'application/vnd.google-earth.kml+xml',
            'kmz' => 'application/vnd.google-earth.kmz',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'txt' => 'text/plain',
            'xml' => 'application/xml',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Verifica se é arquivo KML
     */
    private function isKmlFile($fileName)
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($extension, ['kml', 'kmz']);
    }

    /**
     * Endpoint específico para servir KML com headers CORS
     */
    public function serveKml($id)
    {
        try {
            Log::info("📋 Servindo KML para documento ID: {$id}");

            $document = PropertyDocument::findOrFail($id);
            Log::info("📄 Documento encontrado:", [
                'id' => $document->id,
                'name' => $document->name,
                'file_name' => $document->file_name,
                'property_id' => $document->property_id,
                'file_path' => $document->file_path,
                'mime_type' => $document->mime_type
            ]);

            // Verificar se é arquivo KML
            if (!$this->isKmlFile($document->file_name)) {
                Log::warning("❌ Arquivo não é KML: {$document->file_name}");
                return response('Este endpoint é apenas para arquivos KML.', 400, [
                    'Content-Type' => 'text/plain'
                ]);
            }

            Log::info("✅ Arquivo é KML válido: {$document->file_name}");

            // Verificar autorização
            $user = Auth::user();
            $property = Property::find($document->property_id);

            if (!$user || !$property || !$this->checkDocumentAccess($user, $property, $document)) {
                Log::warning("❌ Acesso negado ao KML", [
                    'user_id' => $user ? $user->id : 'não autenticado',
                    'property_id' => $property ? $property->id : 'não encontrada',
                    'document_id' => $document->id
                ]);
                return response('Você não tem permissão para acessar este arquivo KML.', 403, [
                    'Content-Type' => 'text/plain'
                ]);
            }

            Log::info("✅ Acesso autorizado ao KML");

            // Verificar se o documento tem arquivo BLOB
            if (!$document->file) {
                Log::error("❌ Arquivo KML não encontrado no banco");
                return response('Arquivo KML não encontrado.', 404, [
                    'Content-Type' => 'text/plain'
                ]);
            }

            // Decodificar Base64
            $fileData = base64_decode($document->file);
            if ($fileData === false) {
                Log::error("❌ Erro ao decodificar Base64 do arquivo KML");
                return response('Erro ao processar arquivo KML.', 500, [
                    'Content-Type' => 'text/plain'
                ]);
            }

            Log::info("✅ Arquivo KML decodificado do Base64", [
                'decoded_size' => strlen($fileData),
                'content_preview' => substr($fileData, 0, 200)
            ]);

            if (!$fileData) {
                Log::error("❌ Arquivo KML não encontrado nem no storage nem em Base64");
                return response('Arquivo KML não encontrado.', 404, [
                    'Content-Type' => 'text/plain'
                ]);
            }

            // Headers específicos para KML com CORS
            return Response::make($fileData, 200, [
                'Content-Type' => 'application/vnd.google-earth.kml+xml',
                'Content-Length' => strlen($fileData),
                'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
                'Cache-Control' => 'public, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao servir KML ID: {$id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Erro ao carregar arquivo KML.', 500, [
                'Content-Type' => 'text/plain'
            ]);
        }
    }

    /**
     * Handle preflight OPTIONS requests
     */
    public function handleKmlOptions()
    {
        return response('', 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            'Access-Control-Max-Age' => '86400',
        ]);
    }

    /**
     * Método alternativo para KML - se necessário manter compatibilidade
     */
    public function getKmlDocument($id)
    {
        return $this->serveKml($id);
    }

    /**
     * Método getDocument - alias para viewDocument para manter compatibilidade
     */
    public function getDocument($id)
    {
        return $this->viewDocument($id);
    }

    /**
     * Mostra uma propriedade específica de um cliente
     */
    public function clientShow(string $id)
    {
        $user = Auth::user();

        // Pegue apenas UMA propriedade específica
        $property = Property::whereHas('owners', function ($query) use ($id) {
            $query->where('user_id', $id);
        })->with([
            'owners.typeOwnership', 
            'coOwners.typeOwnership', // ✅ NOVO: Incluir co-proprietários
            'documents'
        ])->first();

        if (!$property) {
            return $this->returnUnauthorizedError(
                'Propriedade não encontrada para este cliente.',
                'property_access'
            );
        }

        $typeOwnership = TypeOwnership::all();

        $canView = false;
        $canCreate = false;

        if ($user instanceof \App\Models\User && $user->hasProfile('proprietario')) {
            $canView = PropertyUser::where('user_id', $id)
                ->where('property_id', $property->id)
                ->exists();
        } else {
            $canView = DB::table('authorizations')
                ->where('service_provider_id', $user->id)
                ->where('can_view_documents', 1)
                ->whereExists(function ($query) use ($property) {
                    $query->select(DB::raw(1))
                        ->from('property_user')
                        ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                        ->where('property_user.property_id', $property->id);
                })
                ->exists();

            $canCreate = ($user instanceof \App\Models\User && $user->hasProfile('prestador')) &&
                DB::table('authorizations')
                ->where('service_provider_id', $user->id)
                ->where('can_create_properties', 1)
                ->whereExists(function ($query) use ($property) {
                    $query->select(DB::raw(1))
                        ->from('property_user')
                        ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                        ->where('property_user.property_id', $property->id);
                })
                ->exists();
        }

        if (!$canView && !$canCreate) {
            return $this->returnUnauthorizedError(
                'Você não tem permissão para visualizar esta propriedade do cliente.',
                'service_provider'
            );
        }

        return Inertia::render('Properties/ShowProperty', [
            'property' => $property,
            'documents' => $property->documents,
            'owners' => $property->owners,
            'coOwners' => $property->coOwners, // ✅ NOVO: Incluir co-proprietários
            'success' => session('success'),
            'isServiceProvider' => ($user instanceof \App\Models\User && $user->hasProfile('prestador') && !$user->hasProfile('proprietario')),
            'typeOwnership' => $typeOwnership,
            'canView' => $canView,
            'canCreate' => $canCreate,
        ]);
    }

    // Duplicate clientsProperty method removed to fix redeclaration error.

}
