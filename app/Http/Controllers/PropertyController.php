<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\User;
use App\Models\PropertyUser;
use App\Models\Authorization;
use App\Models\TypeOwnership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class PropertyController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('viewAny', Property::class);

        $user = Auth::user();
        
        // Perfis 1 e 3 podem acessar suas próprias propriedades
        if (in_array($user->profile_id, [1, 3])) {
            $properties = Property::whereHas('owners', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->with(['owners.typeOwnership'])->get();

            // Usar o componente correto para propriedades próprias
            return Inertia::render('Properties/IndexProperty', [
                'properties' => $properties,
                'can' => [
                    'update' => $properties->pluck('id')->mapWithKeys(function ($id) {
                        $property = Property::find($id);
                        return [$id => Gate::allows('update', $property)];
                    }),
                ],
            ]);
        } else {
            // Perfil 2 não deve acessar esta rota
            return $this->returnUnauthorizedError(
                'Prestadores de serviço devem acessar propriedades através da área de clientes.',
                'service_provider'
            );
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $currentUser = Auth::user();
        
        if (!in_array($currentUser->profile_id, [1, 2, 3])) {
            return $this->returnUnauthorizedError(
                'Você não tem permissão para criar propriedades.',
                'general'
            );
        }

        $users = $this->getAvailableUsers($currentUser);
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
        
        try {
            // ✅ CORREÇÃO: Proprietários (perfil 1) sempre podem adicionar a si mesmos
            if ($request->has('owners') && is_array($request->owners)) {
                // Só valida permissões se NÃO for proprietário puro (perfil 1)
                if ($currentUser->profile_id !== 1) {
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

                // Inserindo Proprietários
                if ($request->has('owners') && is_array($request->owners)) {
                    $owners = collect($request->owners)->map(function ($owner) use ($property) {
                        return [
                            'owner_id' => $owner['user_id'] ?? $owner['id'],
                            'user_id' => $owner['user_id'] ?? $owner['id'],
                            'type_ownership_id' => $owner['type_ownership_id'] ?? $owner['type_ownership'],
                            'percentage' => $owner['percentage'] ?? $owner['percent'],
                            'other' => $owner['observations'] ?? null,
                            'property_id' => $property->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })->toArray();

                    PropertyUser::insert($owners);
                } else if ($currentUser->profile_id === 1) {
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
                    $documents = collect($request->documents)->map(function ($doc) use ($property) {
                        return [
                            'name' => $doc['name'],
                            'date' => ($doc['date'] === "Sem Data" || empty($doc['date'])) ? null : $doc['date'],
                            'show' => $doc['show'] ?? true,
                            'file' => $this->cleanBase64($doc['file']),
                            'file_name' => $doc['file_name'],
                            'property_id' => $property->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })->toArray();

                    PropertyDocument::insert($documents);
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
        if ($user) {
            $user = \App\Models\User::with('activity')->find($user->id);
        }
        
        // Carrega a propriedade com todos os relacionamentos necessários
        $property = Property::with([
            'owners.typeOwnership', 
            'documents', 
            'evaluations' => function($query) {
                $query->with('user')->orderBy('created_at', 'desc');
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

        switch ($user->profile_id) {
            case 1: // Proprietário puro
                $hasAccess = $isOwnerOfProperty;
                // ✅ CORREÇÃO: Proprietários sempre podem editar suas propriedades
                $canEdit = $hasAccess;
                $canEvaluate = false; // Proprietários puros NUNCA podem avaliar
                break;

            case 2: // Prestador de serviço puro
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

                // Prestador só pode avaliar se tem autorização E activity permite
                $canEvaluate = $hasAuthorizationToEvaluate && 
                            $user->activity && 
                            (bool) $user->activity->evaluation_permission;
                break;

            case 3: // Proprietário/Prestador
                $hasAccess = $isOwnerOfProperty;
                // ✅ CORREÇÃO: Se é proprietário, sempre pode editar
                $canEdit = $hasAccess;
                
                // Se é proprietário da propriedade
                if ($hasAccess && $user->activity) {
                    $canEvaluate = (bool) $user->activity->evaluation_permission;
                } else {
                    // Se não é proprietário, verifica se tem autorização como prestador
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

                    if ($hasAuthorizationToEvaluate) {
                        $hasAccess = true; // Dar acesso se tiver autorização como prestador
                        $canEvaluate = $user->activity && (bool) $user->activity->evaluation_permission;
                        
                        // ✅ CORREÇÃO: Também pode editar se tiver autorização como prestador
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
                    }
                }
                break;

            default:
                $hasAccess = false;
                $canEdit = false;
                $canEvaluate = false;
                break;
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
            'user_profile' => $user->profile_id,
            'is_owner_of_property' => $isOwnerOfProperty,
            'route_name' => request()->route()->getName(),
            'permissions_calculated' => [
                'can_evaluate' => $canEvaluate,
                'can_edit' => $canEdit,
                'has_access' => $hasAccess
            ]
        ]);

        return Inertia::render('Properties/ShowProperty', [
            'property' => $property,
            'documents' => $property->documents->toArray(),
            'owners' => $property->owners->toArray(),
            'evaluations' => $property->evaluations->toArray(),
            'typeOwnership' => $typeOwnership->toArray(),
            'success' => session('success'),
            'isServiceProvider' => $user->profile_id === 2,
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

        // Verificar se existe relacionamento na tabela property_user
        $propertyUsers = PropertyUser::where('property_id', $property->id)->get();
        Log::info('Todos os proprietários desta propriedade:', [
            'property_users' => $propertyUsers->map(function($pu) {
                return [
                    'id' => $pu->id,
                    'user_id' => $pu->user_id,
                    'property_id' => $pu->property_id,
                    'type_ownership_id' => $pu->type_ownership_id,
                    'percentage' => $pu->percentage
                ];
            })->toArray()
        ]);

        // Verificar especificamente se o usuário atual é proprietário
        $isCurrentUserOwner = PropertyUser::where('property_id', $property->id)
            ->where('user_id', $currentUser->id)
            ->exists();

        $currentUserOwnership = PropertyUser::where('property_id', $property->id)
            ->where('user_id', $currentUser->id)
            ->first();

        // ✅ CORREÇÃO: Verificar permissões ANTES do Gate
        if (!$this->canEditProperty($currentUser, $property)) {
            Log::warning('EDIT NEGADO - canEditProperty retornou false');
            return $this->returnUnauthorizedError(
                'Você não tem permissão para editar esta propriedade.',
                'property_access'
            );
        }

        // ✅ IMPORTANTE: Só chama Gate::authorize APÓS verificar permissões customizadas
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
        
        $property = Property::with([
            'documents',
            'owners.typeOwnership'
        ])->findOrFail($property->id);

        // Carregando os owners com os dados do usuário manualmente
        $ownersWithUsers = PropertyUser::with(['typeOwnership'])
            ->where('property_id', $property->id)
            ->get()
            ->map(function ($owner) {
                $user = User::find($owner->user_id);
                $owner->user = $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'cpf_cnpj' => $user->cpf_cnpj,
                    'profile_id' => $user->profile_id,
                ] : null;
                return $owner;
            });

        Log::info('=== DEBUG EDIT PROPERTY - SUCESSO ===');

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
            'owners' => $ownersWithUsers,
            'documents' => PropertyDocument::where('property_id', $property->id)->get(),
        ]);
    }

    /**
     * ✅ CORREÇÃO: Método update sem validação desnecessária para proprietários
     */
    public function update(UpdatePropertyRequest $request, string $id)
    {
        $property = Property::findOrFail($id);
        $currentUser = Auth::user();

       if ($request->hasFile('file_photo') || $request->filled('file_photo')) {
            $propertyData['file_photo'] = $request->file_photo;
        }
        
        try {
            // ✅ CORREÇÃO PRINCIPAL: Só valida permissões se NÃO for proprietário puro (perfil 1)
            if ($request->has('owners') && is_array($request->owners)) {
                if ($currentUser->profile_id !== 1) {
                    Log::info('Validando permissões de proprietários para perfil não-1');
                    $this->validateOwnerPermissions($currentUser, $request->owners);
                } else {
                    Log::info('Pulando validação de permissões - usuário é proprietário puro (perfil 1)');
                }
                
                // Valida percentuais independente do perfil
                $this->validateOwnershipPercentages($request->owners);
            }

            DB::transaction(function () use ($request, $property) {
                // Atualizar dados básicos da propriedade
                $propertyData = $request->except(['documents', 'owners']);
                $property->update($propertyData);

                // Atualizar proprietários
                if ($request->has('owners') && is_array($request->owners)) {
                    PropertyUser::where('property_id', $property->id)->delete();

                    foreach ($request->owners as $owner) {
                        PropertyUser::create([
                            'user_id' => $owner['user_id'] ?? $owner['id'],
                            'type_ownership_id' => $owner['type_ownership_id'] ?? $owner['type_ownership'],
                            'percentage' => $owner['percentage'] ?? $owner['percent'],
                            'other' => $owner['observations'] ?? $owner['other'] ?? null,
                            'property_id' => $property->id,
                        ]);
                    }
                }

                // Atualizar documentos
                if ($request->has('documents') && is_array($request->documents)) {
                    $existingDocuments = PropertyDocument::where('property_id', $property->id)
                        ->pluck('id', 'file_name')->toArray();
                    $requestDocuments = collect($request->documents)->keyBy('file_name');

                    foreach ($existingDocuments as $fileName => $docId) {
                        if (!$requestDocuments->has($fileName)) {
                            PropertyDocument::where('id', $docId)->delete();
                        }
                    }

                    foreach ($request->documents as $document) {
                        $base64File = $this->cleanBase64($document['file']);

                        if (isset($existingDocuments[$document['file_name']])) {
                            PropertyDocument::where('id', $existingDocuments[$document['file_name']])
                                ->update([
                                    'name' => $document['name'],
                                    'date' => ($document['date'] === "Sem Data" || empty($document['date'])) ? null : $document['date'],
                                    'show' => $document['show'] ?? true,
                                    'file' => $base64File,
                                ]);
                        } else {
                            PropertyDocument::create([
                                'name' => $document['name'],
                                'date' => ($document['date'] === "Sem Data" || empty($document['date'])) ? null : $document['date'],
                                'show' => $document['show'] ?? true,
                                'file' => $base64File,
                                'file_name' => $document['file_name'],
                                'property_id' => $property->id,
                            ]);
                        }
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
        Log::info('=== canEditProperty - INÍCIO ===', [
            'user_id' => $user->id,
            'user_profile' => $user->profile_id,
            'property_id' => $property->id
        ]);

        switch ($user->profile_id) {
            case 1: // Proprietário
                $isOwner = PropertyUser::where('property_id', $property->id)
                    ->where('user_id', $user->id)
                    ->exists();
                
                Log::info('Profile 1 - Verificação proprietário:', [
                    'is_owner' => $isOwner,
                    'sql_query' => PropertyUser::where('property_id', $property->id)
                        ->where('user_id', $user->id)
                        ->toSql(),
                    'bindings' => [$property->id, $user->id]
                ]);
                
                return $isOwner;

            case 2: // Prestador de serviço
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

                Log::info('Profile 2 - Verificação prestador:', ['can_edit' => $canEdit]);
                return $canEdit;

            case 3: // Proprietário/Prestador
                // Primeiro verifica se é proprietário
                $isOwner = PropertyUser::where('property_id', $property->id)
                    ->where('user_id', $user->id)
                    ->exists();

                Log::info('Profile 3 - Verificação proprietário:', ['is_owner' => $isOwner]);

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

                Log::info('Profile 3 - Verificação prestador:', ['can_edit_as_provider' => $canEditAsProvider]);
                return $canEditAsProvider;

            default:
                Log::warning('Profile desconhecido:', ['profile' => $user->profile_id]);
                return false;
        }
    }

    /**
     * ✅ CORREÇÃO: validateOwnerPermissions melhorada
     */
    // private function validateOwnerPermissions($currentUser, array $owners)
    // {
    //     if (empty($owners)) return;

    //     Log::info('=== validateOwnerPermissions ===', [
    //         'user_profile' => $currentUser->profile_id,
    //         'owners_count' => count($owners)
    //     ]);

    //     // ✅ CORREÇÃO: Proprietário puro (perfil 1) só pode adicionar a si mesmo
    //     if ($currentUser->profile_id === 1) {
    //         foreach ($owners as $owner) {
    //             $userId = $owner['user_id'] ?? $owner['id'];
    //             if ($userId != $currentUser->id) {
    //                 Log::error('Proprietário tentando adicionar outro usuário:', [
    //                     'owner_id' => $userId,
    //                     'current_user_id' => $currentUser->id
    //                 ]);
    //                 throw new \Exception(
    //                     "Proprietários só podem adicionar a si mesmos como proprietário."
    //                 );
    //             }
    //         }
    //         return;
    //     }

    //     // Para perfis 2 e 3, verifica autorizações
    //     $availableUsers = collect($this->getAvailableUsers($currentUser));
    //     $availableUserIds = $availableUsers->pluck('id')->toArray();

    //     Log::info('Usuários disponíveis para este perfil:', [
    //         'available_user_ids' => $availableUserIds
    //     ]);

    //     foreach ($owners as $owner) {
    //         $userId = $owner['user_id'] ?? $owner['id'];
    //         if (!in_array($userId, $availableUserIds)) {
    //             Log::error('Usuário não autorizado:', [
    //                 'user_id' => $userId,
    //                 'available_ids' => $availableUserIds
    //             ]);
    //             throw new \Exception(
    //                 "Você não tem permissão para adicionar o usuário ID {$userId} como proprietário."
    //             );
    //         }
    //     }
    // }

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
        switch ($currentUser->profile_id) {
            case 1: // Proprietário - apenas ele mesmo
                return [
                    $this->formatUser($currentUser)
                ];

            case 2: // Prestador de serviço - apenas autorizados
                // Usando DB::table para evitar problemas com o modelo Authorization
                $authorizedOwnerIds = DB::table('authorizations')
                    ->where('service_provider_id', $currentUser->id)
                    ->where('can_create_properties', 1)
                    ->pluck('owner_id')
                    ->toArray();

                return User::whereIn('id', $authorizedOwnerIds)
                    ->select('id', 'name', 'cpf_cnpj', 'profile_id')
                    ->orderBy('name')
                    ->get()
                    ->map([$this, 'formatUser'])
                    ->toArray();

            case 3: // Proprietário/Prestador - ele mesmo + autorizados
                $authorizedOwnerIds = DB::table('authorizations')
                    ->where('service_provider_id', $currentUser->id)
                    ->where('can_create_properties', 1)
                    ->pluck('owner_id')
                    ->toArray();

                // Adiciona o próprio usuário
                $authorizedOwnerIds[] = $currentUser->id;
                $authorizedOwnerIds = array_unique($authorizedOwnerIds);

                return User::whereIn('id', $authorizedOwnerIds)
                    ->select('id', 'name', 'cpf_cnpj', 'profile_id')
                    ->orderBy('name')
                    ->get()
                    ->map([$this, 'formatUser'])
                    ->toArray();

            default:
                return [];
        }
    }

    /**
     * Retorna autorizações do usuário atual
     */
    private function getUserAuthorizations($currentUser)
    {
        if (in_array($currentUser->profile_id, [2, 3])) {
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
                    'users.cpf_cnpj as user_cpf_cnpj',
                    'users.profile_id as user_profile_id'
                )
                ->get()
                ->map(function ($auth) {
                    return [
                        'id' => $auth->id,
                        'owner_id' => $auth->owner_id,
                        'service_provider_id' => $auth->service_provider_id,
                        'can_create_properties' => $auth->can_create_properties,
                        'owner' => [
                            'id' => $auth->user_id,
                            'name' => $auth->user_name,
                            'cpf_cnpj' => $auth->user_cpf_cnpj,
                            'profile_id' => $auth->user_profile_id,
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

        Log::info('=== validateOwnerPermissions ===', [
            'user_profile' => $currentUser->profile_id,
            'owners_count' => count($owners)
        ]);

        // ✅ CORREÇÃO: Proprietário puro (perfil 1) só pode adicionar a si mesmo
        if ($currentUser->profile_id === 1) {
            foreach ($owners as $owner) {
                $userId = $owner['user_id'] ?? $owner['id'];
                if ($userId != $currentUser->id) {
                    Log::error('Proprietário tentando adicionar outro usuário:', [
                        'owner_id' => $userId,
                        'current_user_id' => $currentUser->id
                    ]);
                    throw new \Exception(
                        "Proprietários só podem adicionar a si mesmos como proprietário."
                    );
                }
            }
            return;
        }

        // Para perfis 2 e 3, verifica autorizações
        $availableUsers = collect($this->getAvailableUsers($currentUser));
        $availableUserIds = $availableUsers->pluck('id')->toArray();

        Log::info('Usuários disponíveis para este perfil:', [
            'available_user_ids' => $availableUserIds
        ]);

        foreach ($owners as $owner) {
            $userId = $owner['user_id'] ?? $owner['id'];
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

        // Verifica duplicatas
        $userIds = array_map(function($owner) {
            return $owner['user_id'] ?? $owner['id'];
        }, $owners);
        
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

        // Procura proprietário com 100%
        foreach ($owners as $owner) {
            $percentage = floatval($owner['percentage'] ?? $owner['percent']);
            $typeId = $owner['type_ownership_id'] ?? $owner['type_ownership'];
            
            if ($typeId == 1 && $percentage == 100) {
                return $owner['user_id'] ?? $owner['id'];
            }
        }

        // Se não há proprietário com 100%, pega o primeiro proprietário
        foreach ($owners as $owner) {
            $typeId = $owner['type_ownership_id'] ?? $owner['type_ownership'];
            if ($typeId == 1) {
                return $owner['user_id'] ?? $owner['id'];
            }
        }

        // Fallback: primeiro usuário da lista
        if (!empty($owners)) {
            return $owners[0]['user_id'] ?? $owners[0]['id'];
        }

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
            'profile_id' => $user->profile_id,
        ];
    }

    /**
     * Limpa dados base64
     */
    private function cleanBase64($base64Data)
    {
        return preg_replace('/^data:application\/[a-zA-Z0-9.+-]+;base64,/', '', $base64Data);
    }

    // ====================================
    // MÉTODOS EXISTENTES
    // ====================================


    private function returnUnauthorizedError($message = null, $type = 'general')
    {
        $user = Auth::user();
        
        // Determina o tipo de erro baseado no contexto
        if ($type === 'general' && $user) {
            switch ($user->profile_id) {
                case 1:
                    $type = 'property_access';
                    break;
                case 2:
                    $type = 'service_provider';
                    break;
                case 3:
                    $type = 'property_access';
                    break;
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
            'userProfile' => $user ? $user->profile_id : null,
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

    public function updateDocumentShow(Request $request, string $documentId)
    {
        $request->validate([
            'show' => 'required|boolean',
        ]);

        $document = PropertyDocument::findOrFail($documentId);
        $document->update(['show' => $request->show]);

        return redirect()->back()->with('success', 'Document visibility updated successfully.');
    }

    public function clientShow(string $id)
    {
        $user = Auth::user();
        
        // Pegue apenas UMA propriedade específica
        $property = Property::whereHas('owners', function ($query) use ($id) {
            $query->where('user_id', $id);
        })->with(['owners.typeOwnership', 'documents'])->first();

        if (!$property) {
            return $this->returnUnauthorizedError(
                'Propriedade não encontrada para este cliente.',
                'property_access'
            );
        }

        $typeOwnership = TypeOwnership::all();

        $canView = false;
        $canCreate = false;

        if ($user->profile_id === 1) {
            $canView = PropertyUser::where('user_id', $id)
                ->where('property_id', $property->id)
                ->exists();
        } else {
            $canView = DB::table('authorizations')
                ->where('service_provider_id', $user->id) // CORREÇÃO: Adicionar esta linha que estava faltando
                ->where('can_view_documents', 1)
                ->whereExists(function ($query) use ($property) {
                    $query->select(DB::raw(1))
                        ->from('property_user')
                        ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                        ->where('property_user.property_id', $property->id);
                })
                ->exists();

            $canCreate = $user->profile_id > 1 &&
                DB::table('authorizations')
                ->where('service_provider_id', $user->id)
                ->where('can_create_properties', 1)
                ->whereExists(function ($query) use ($property) { // CORREÇÃO: Usar $property ao invés de $id
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
            'documents' => $property->documents,  // CORREÇÃO: Remover flatMap
            'owners' => $property->owners,        // CORREÇÃO: Remover flatMap
            'success' => session('success'),
            'isServiceProvider' => $user->profile_id === 2,
            'typeOwnership' => $typeOwnership,
            'canView' => $canView,
            'canCreate' => $canCreate,
        ]);
    }

    public function viewDocument($id)
    {
        $document = PropertyDocument::findOrFail($id);
        $property = Property::find($document->property_id);
        $user = Auth::user();

        if (!$document || !$document->file) {
            return $this->returnUnauthorizedError(
                'Documento não encontrado.',
                'document_access'
            );
        }

        // ✅ Verificar permissões de acesso ao documento
        if (!$property || !$this->checkDocumentAccess($user, $property, $document)) {
            return $this->returnUnauthorizedError(
                'Você não tem permissão para visualizar este documento.',
                'document_access'
            );
        }

        $fileData = base64_decode($document->file);
        $mimeType = 'application/octet-stream';

        if (str_ends_with(strtolower($document->file_name), '.pdf')) {
            $mimeType = 'application/pdf';
        } elseif (str_ends_with(strtolower($document->file_name), '.kml')) {
            $mimeType = 'application/vnd.google-earth.kml+xml';
        }

        return Response::make($fileData, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"'
        ]);
    }

    public function clientsProperty($id = null)
    {
        $user = Auth::user();

        // Apenas perfis 2 e 3 podem acessar propriedades de clientes
        if (!in_array($user->profile_id, [2, 3])) {
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
        switch ($user->profile_id) {
            case 1: // Proprietário
                return $this->isOwnerOfProperty($user, $property);
                
            case 2: // Prestador de serviço
                return $this->hasServiceProviderAccess($user, $property);
                
            case 3: // Proprietário/Prestador
                return $this->isOwnerOfProperty($user, $property) || 
                    $this->hasServiceProviderAccess($user, $property);
                
            default:
                return false;
        }
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
            $document = PropertyDocument::findOrFail($id);
            
            // Verificar se é arquivo KML
            if (!$this->isKmlFile($document->file_name)) {
                return $this->returnUnauthorizedError(
                    'Este endpoint é apenas para arquivos KML.',
                    'document_access'
                );
            }
            
            // Verificar autorização
            $user = Auth::user();
            $property = Property::find($document->property_id);
            
            if (!$property || !$this->checkDocumentAccess($user, $property, $document)) {
                return $this->returnUnauthorizedError(
                    'Você não tem permissão para acessar este arquivo KML.',
                    'document_access'
                );
            }
            
            $fileData = base64_decode($document->file);
            
            if ($fileData === false) {
                return $this->returnUnauthorizedError(
                    'Erro ao processar arquivo KML.',
                    'document_access'
                );
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
            
            return $this->returnUnauthorizedError(
                'Erro ao carregar arquivo KML.',
                'document_access'
            );
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
        // Redirecionar para o método principal
        return $this->serveKml($id);
    }

    /**
     * Método getDocument - alias para viewDocument para manter compatibilidade
     */
    public function getDocument($id)
    {
        return $this->viewDocument($id);
    }

}