<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyEvaluation;
use App\Models\PropertyUser;
use App\Http\Requests\PropertyEvaluationRequest;
use App\Http\Requests\StorePropertyEvaluationRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\PropertyEvaluationMedia;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class PropertyEvaluationController extends BaseController
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Verificar se o usuário pode acessar a propriedade
     */
    private function canAccessProperty(Property $property, $user)
    {
        // Garantir que $user é instância de App\Models\User e perfis estão carregados
        if (!($user instanceof \App\Models\User)) {
            $user = \App\Models\User::with('profiles')->find($user->id);
        } elseif (!$user->relationLoaded('profiles')) {
            $user->load('profiles');
        }
        // Proprietário: acesso se for dono direto ou via property_user
        if ($user->hasProfile('proprietario')) {
            $isOwner = PropertyUser::where('property_id', $property->id)
                ->where('user_id', $user->id)
                ->exists();
            if ($isOwner) return true;
        }
    // Prestador: acesso se tiver autorização
    if ($user->hasProfile('prestador')) {
            $hasAuth = DB::table('authorizations')
                ->where('service_provider_id', $user->id)
                ->where('can_view_documents', 1)
                ->whereExists(function ($query) use ($property) {
                    $query->select(DB::raw(1))
                        ->from('property_user')
                        ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                        ->where('property_user.property_id', $property->id);
                })
                ->exists();
            if ($hasAuth) return true;
        }
        return false;
    }

    /**
     * Verificar se o usuário pode criar avaliações para a propriedade
     */
    private function canCreateEvaluation(Property $property, $user)
    {
        // Garantir que $user é instância de App\Models\User e perfis estão carregados
        if (!($user instanceof \App\Models\User)) {
            $user = \App\Models\User::with(['activity', 'profiles'])->find($user->id);
        } else {
            if (!$user->relationLoaded('activity')) {
                $user->load('activity');
            }
            if (!$user->relationLoaded('profiles')) {
                $user->load('profiles');
            }
        }
        // Proprietário puro: nunca pode avaliar
        if ($user->hasProfile('proprietario') && !$user->hasProfile('prestador')) {
            return false;
        }
    // Prestador: pode avaliar se tiver autorização e permissão
    if ($user->hasProfile('prestador')) {
            $hasAuthorization = DB::table('authorizations')
                ->where('service_provider_id', $user->id)
                ->where('evaluation_permission', 1)
                ->whereExists(function ($query) use ($property) {
                    $query->select(DB::raw(1))
                        ->from('property_user')
                        ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                        ->where('property_user.property_id', $property->id);
                })
                ->exists();
            if ($hasAuthorization && $user->activity && (bool) $user->activity->evaluation_permission) {
                return true;
            }
        }
    // Proprietário/Prestador: se for dono, precisa permissão de activity
    if ($user->hasProfile('proprietario') && $user->hasProfile('prestador')) {
            $isOwner = PropertyUser::where('property_id', $property->id)
                ->where('user_id', $user->id)
                ->exists();
            if ($isOwner) {
                return $user->activity && (bool) $user->activity->evaluation_permission;
            }
        }
        return false;
    }

    // GET /properties/{property}/evaluations
    public function index(Property $property)
    {
        try {
            $user = Auth::user();

            if (!$this->canAccessProperty($property, $user)) {
                abort(403, 'Acesso não autorizado.');
            }

            $evaluations = PropertyEvaluation::with(['user','media'])
                ->where('property_id', $property->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($e){
                    return array_merge($e->toArray(), [
                        'pdf_url' => $e->pdf_url,
                    ]);
                });

            $isOwner = \App\Models\PropertyUser::where('property_id', $property->id)
                ->where('user_id', $user->id)
                ->exists();

            return Inertia::render('Properties/PropertyEvaluationList', [
                'property' => $property,
                'evaluations' => $evaluations,
                'isOwner' => $isOwner
            ]);

        } catch (\Exception $e) {
            Log::error('Error in PropertyEvaluationController@index', [
                'error' => $e->getMessage(),
                'property_id' => $property->id,
                'user_id' => Auth::id()
            ]);

            return back()->withErrors(['error' => 'Erro ao carregar avaliações: ' . $e->getMessage()]);
        }
    }

    // GET /my-evaluations - lista do avaliador com ações
    public function myEvaluations()
    {
        $user = Auth::user();
        // Base query scoped to current evaluator
        $baseQuery = PropertyEvaluation::query()->where('user_id', $user->id);

        // Stats via lightweight aggregates (avoid loading full rows into memory)
        $total = (clone $baseQuery)->count();
        $confirmed = (clone $baseQuery)->where('owner_acknowledged', true)->count();
        // Consider null/false as pending
        $pending = $total - $confirmed;
        $totalValue = (float) ((clone $baseQuery)->sum('valuation'));

        // Fetch evaluations with only required columns to reduce memory usage
        $evaluations = $baseQuery
            ->select([
                'id',
                'property_id',
                'user_id',
                'valuation',
                'appraiser',
                'created_at',
                'owner_acknowledged',
            ])
            ->with([
                'property:id,nickname,type_property',
                'user:id,name',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Properties/MyEvaluations', [
            'evaluations' => $evaluations,
            'stats' => [
                'total' => $total,
                'confirmed' => $confirmed,
                'pending' => $pending,
                'totalValue' => $totalValue,
            ],
        ]);
    }

    // GET /properties/{property}/evaluations/create
    public function create(Property $property)
    {
        try {
            $user = Auth::user();

            if (!$this->canAccessProperty($property, $user)) {
                abort(403, 'Acesso não autorizado.');
            }

            if (!$this->canCreateEvaluation($property, $user)) {
                abort(403, 'Você não tem permissão para criar avaliações desta propriedade.');
            }

            return Inertia::render('Properties/PropertyEvaluationForm', [
                'property' => $property,
                'isEditing' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Error in PropertyEvaluationController@create', [
                'error' => $e->getMessage(),
                'property_id' => $property->id,
                'user_id' => Auth::id()
            ]);

            return back()->withErrors(['error' => 'Erro ao acessar formulário: ' . $e->getMessage()]);
        }
    }

    // POST /properties/{property}/evaluations - VERSÃO SIMPLIFICADA PARA TESTE
    public function store(Request $request, Property $property)
    {
        Log::info('PropertyEvaluationController@store - VERSÃO SIMPLIFICADA', [
            'property_id' => $property->id,
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);

        try {
            $user = Auth::user();

            // Garantir que $user é instância de App\Models\User e perfis estão carregados
            if (!($user instanceof \App\Models\User)) {
                $user = \App\Models\User::with('profiles')->find($user->id);
            } elseif (!$user->relationLoaded('profiles')) {
                $user->load('profiles');
            }
            // VERIFICAÇÃO SIMPLIFICADA: só verifica se é prestador de serviço
            if (!$user->hasProfile('prestador')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Apenas prestadores de serviço podem criar avaliações.'
                ], 403);
            }

            // CRIAR AUTORIZAÇÃO SE NÃO EXISTIR (TEMPORÁRIO)
            $propertyOwner = PropertyUser::where('property_id', $property->id)->first();
            if ($propertyOwner) {
                $existingAuth = DB::table('authorizations')
                    ->where('service_provider_id', $user->id)
                    ->where('owner_id', $propertyOwner->user_id)
                    ->first();

                if (!$existingAuth) {
                    Log::info('Criando autorização automática');
                    DB::table('authorizations')->insert([
                        'owner_id' => $propertyOwner->user_id,
                        'service_provider_id' => $user->id,
                        'can_view_documents' => 1,
                        'can_create_properties' => 1,
                        'evaluation_permission' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    Log::info('Autorização criada com sucesso');
                }
            }

            // VALIDAÇÃO SIMPLIFICADA
            $validated = $request->validate([
                'appraiser' => 'required|string|max:255',
                'valuation' => 'required|numeric|min:0',
                'property_type' => 'required|string',
                'comments' => 'nullable|string',
                'observations' => 'nullable|string',
                'urban_subtype' => 'nullable|string',
                'property_condition' => 'nullable|string',
                'furniture_status' => 'nullable|string',
                'rooms' => 'nullable|integer',
                'bedrooms' => 'nullable|integer',
                'bathrooms' => 'nullable|integer',
                'garage_spaces' => 'nullable|integer',
                'floors' => 'nullable|integer',
                'built_area' => 'nullable|numeric',
                'total_area' => 'nullable|numeric',
                'office_rooms' => 'nullable|integer',
                'parking_spaces' => 'nullable|integer',
                'rural_total_area' => 'nullable|numeric',
                'has_construction' => 'nullable|boolean',
                'has_farming' => 'nullable|boolean',
                'water_source' => 'nullable|string',
                'water_source_details' => 'nullable|string',
                'construction_types' => 'nullable',
                'farming_types' => 'nullable',
                'details' => 'nullable|array',
            ]);

            Log::info('Validação passou, salvando...');

            // Adicionar IDs obrigatórios
            $validated['property_id'] = $property->id;
            $validated['user_id'] = $user->id;

            // Converter arrays vazios para null para evitar problemas JSON
            if (isset($validated['construction_types']) && empty($validated['construction_types'])) {
                $validated['construction_types'] = null;
            }
            if (isset($validated['farming_types']) && empty($validated['farming_types'])) {
                $validated['farming_types'] = null;
            }
            if (isset($validated['details']) && empty($validated['details'])) {
                $validated['details'] = null;
            }

            // Normalizar estrutura de rebanho para suportar múltiplas espécies/raças/faixas
            if (!empty($validated['details']) && isset($validated['details']['rebanho'])) {
                $herd = $validated['details']['rebanho'];

                // Se vier um único objeto legacy, encapsular
                if (is_array($herd) && !array_is_list($herd) && (isset($herd['especie']) || isset($herd['raca']) || isset($herd['faixa_etaria']))) {
                    $validated['details']['rebanho'] = [[
                        'especie' => $herd['especie'] ?? null,
                        'raca' => $herd['raca'] ?? null,
                        'faixas' => [[
                            'faixa_etaria' => $herd['faixa_etaria'] ?? null,
                            'quantidade_machos' => $herd['quantidade_machos'] ?? ($herd['machos'] ?? null),
                            'quantidade_femeas' => $herd['quantidade_femeas'] ?? ($herd['femeas'] ?? null),
                        ]],
                    ]];
                }

                // Se vier lista, pode ser: (a) já agrupada com 'faixas' OU (b) itens planos a agrupar
                if (is_array($validated['details']['rebanho']) && array_is_list($validated['details']['rebanho'])) {
                    $list = $validated['details']['rebanho'];
                    $isAlreadyGrouped = !empty($list) && collect($list)->every(function ($i) {
                        return is_array($i) && array_key_exists('faixas', $i);
                    });

                    if ($isAlreadyGrouped) {
                        // Apenas sanear/cast dos valores e manter estrutura
                        $normalized = [];
                        foreach ($list as $g) {
                            $faixas = [];
                            foreach (($g['faixas'] ?? []) as $f) {
                                $faixas[] = [
                                    'faixa_etaria' => $f['faixa_etaria'] ?? ($f['faixa'] ?? null),
                                    'quantidade_machos' => isset($f['quantidade_machos']) ? (int) $f['quantidade_machos'] : (isset($f['machos']) ? (int) $f['machos'] : null),
                                    'quantidade_femeas' => isset($f['quantidade_femeas']) ? (int) $f['quantidade_femeas'] : (isset($f['femeas']) ? (int) $f['femeas'] : null),
                                ];
                            }
                            $normalized[] = [
                                'especie' => $g['especie'] ?? null,
                                'raca' => $g['raca'] ?? null,
                                'faixas' => $faixas,
                            ];
                        }
                        $validated['details']['rebanho'] = $normalized;
                    } else {
                        // Itens planos: agrupar por especie/raca
                        $grouped = [];
                        foreach ($list as $item) {
                            if (!is_array($item)) continue;
                            $esp = $item['especie'] ?? null;
                            $rac = $item['raca'] ?? null;
                            $faixa = $item['faixa_etaria'] ?? ($item['faixa'] ?? null);
                            $machos = isset($item['quantidade_machos']) ? (int) $item['quantidade_machos'] : (isset($item['machos']) ? (int) $item['machos'] : null);
                            $femeas = isset($item['quantidade_femeas']) ? (int) $item['quantidade_femeas'] : (isset($item['femeas']) ? (int) $item['femeas'] : null);
                            $key = ($esp ?? '').'|'.($rac ?? '');
                            if (!isset($grouped[$key])) {
                                $grouped[$key] = [
                                    'especie' => $esp,
                                    'raca' => $rac,
                                    'faixas' => [],
                                ];
                            }
                            $grouped[$key]['faixas'][] = [
                                'faixa_etaria' => $faixa,
                                'quantidade_machos' => $machos,
                                'quantidade_femeas' => $femeas,
                            ];
                        }
                        $validated['details']['rebanho'] = array_values($grouped);
                    }
                }
            }

            DB::beginTransaction();

            $evaluation = PropertyEvaluation::create($validated);

            // Persistir rebanhos normalizados se fornecidos em details
            if (!empty($validated['details']['rebanho']) && is_array($validated['details']['rebanho'])) {
                foreach ($validated['details']['rebanho'] as $h) {
                    $herd = \App\Models\PropertyEvaluationHerd::create([
                        'evaluation_id' => $evaluation->id,
                        'especie' => $h['especie'] ?? null,
                        'raca' => $h['raca'] ?? null,
                    ]);
                    foreach (($h['faixas'] ?? []) as $f) {
                        $machos = $f['quantidade_machos'] ?? ($f['machos'] ?? null);
                        $femeas = $f['quantidade_femeas'] ?? ($f['femeas'] ?? null);
                        \App\Models\PropertyEvaluationHerdAge::create([
                            'herd_id' => $herd->id,
                            'faixa_etaria' => $f['faixa_etaria'] ?? ($f['faixa'] ?? null),
                            'quantidade_machos' => isset($machos) && $machos !== '' ? (int) $machos : null,
                            'quantidade_femeas' => isset($femeas) && $femeas !== '' ? (int) $femeas : null,
                        ]);
                    }
                }
            }

            Log::info('Avaliação criada!', ['evaluation_id' => $evaluation->id]);

            // Notificar o proprietário que existe nova avaliação
            try {
                $owner = PropertyUser::where('property_id', $property->id)->first();
                if ($owner) {
                    $ownerUser = User::find($owner->user_id);
                    if ($ownerUser && $ownerUser->email) {
                        Mail::to($ownerUser->email)->send(new \App\Mail\NewPropertyEvaluationNotification($property, $evaluation));
                    }
                }
            } catch (\Throwable $mailEx) {
                Log::warning('Falha ao enviar email de nova avaliação', ['error' => $mailEx->getMessage()]);
            }

            DB::commit();

            return back()->with([
                'success' => true,
                'message' => 'Avaliação criada com sucesso!',
                'evaluation_id' => $evaluation->id,
                'badge' => [
                    'type' => 'success',
                    'text' => 'Avaliação criada com sucesso!',
                    'timeout' => 5000
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            Log::error('Erro de validação', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro de validação: ' . json_encode($e->errors()),
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erro ao criar avaliação', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ], 500);
        }
    }

    // GET /properties/{property}/evaluations/{evaluation}
    public function show(Property $property, PropertyEvaluation $evaluation)
    {
        try {
            $user = Auth::user();

            if (!$this->canAccessProperty($property, $user)) {
                abort(403, 'Acesso não autorizado.');
            }

            // Verificar se a avaliação pertence à propriedade
            if ($evaluation->property_id !== $property->id) {
                abort(404);
            }

            $evaluation->load(['user', 'property']);

            return Inertia::render('Properties/PropertyEvaluationShow', [
                'property' => $property,
                'evaluation' => $evaluation
            ]);

        } catch (\Exception $e) {
            Log::error('Error in PropertyEvaluationController@show', [
                'error' => $e->getMessage(),
                'property_id' => $property->id,
                'evaluation_id' => $evaluation->id,
                'user_id' => Auth::id()
            ]);

            return back()->withErrors(['error' => 'Erro ao visualizar avaliação: ' . $e->getMessage()]);
        }
    }

    // PUT /properties/{property}/evaluations/{evaluation}
    public function update(Request $request, Property $property, PropertyEvaluation $evaluation)
    {
        Log::info('PropertyEvaluationController@update - Início', [
            'property_id' => $property->id,
            'evaluation_id' => $evaluation->id,
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);

        try {
            $user = Auth::user();

            if (!$this->canAccessProperty($property, $user)) {
                abort(403, 'Acesso não autorizado.');
            }

            // Verificar se a avaliação pertence à propriedade
            if ($evaluation->property_id !== $property->id) {
                abort(404);
            }

            // Validação simples
            $validated = $request->validate([
                'appraiser' => 'required|string|max:255',
                'valuation' => 'required|numeric|min:0',
                'comments' => 'nullable|string|max:1000',
                'observations' => 'nullable|string|max:2000',
                'details' => 'nullable|array',
            ]);

            // Garantir que não altere property_id e user_id
            $validated['user_id'] = $evaluation->user_id;

            Log::info('Atualizando avaliação', ['validated' => $validated]);

            DB::beginTransaction();

            // Normalizar estrutura de rebanho também no update
            if (!empty($validated['details']) && isset($validated['details']['rebanho'])) {
                $herd = $validated['details']['rebanho'];

                if (is_array($herd) && !array_is_list($herd) && (isset($herd['especie']) || isset($herd['raca']) || isset($herd['faixa_etaria']))) {
                    $validated['details']['rebanho'] = [[
                        'especie' => $herd['especie'] ?? null,
                        'raca' => $herd['raca'] ?? null,
                        'faixas' => [[
                            'faixa_etaria' => $herd['faixa_etaria'] ?? null,
                            'quantidade_machos' => $herd['quantidade_machos'] ?? ($herd['machos'] ?? null),
                            'quantidade_femeas' => $herd['quantidade_femeas'] ?? ($herd['femeas'] ?? null),
                        ]],
                    ]];
                }

                if (is_array($validated['details']['rebanho']) && array_is_list($validated['details']['rebanho'])) {
                    $list = $validated['details']['rebanho'];
                    $isAlreadyGrouped = !empty($list) && collect($list)->every(function ($i) {
                        return is_array($i) && array_key_exists('faixas', $i);
                    });

                    if ($isAlreadyGrouped) {
                        $normalized = [];
                        foreach ($list as $g) {
                            $faixas = [];
                            foreach (($g['faixas'] ?? []) as $f) {
                                $faixas[] = [
                                    'faixa_etaria' => $f['faixa_etaria'] ?? ($f['faixa'] ?? null),
                                    'quantidade_machos' => isset($f['quantidade_machos']) ? (int) $f['quantidade_machos'] : (isset($f['machos']) ? (int) $f['machos'] : null),
                                    'quantidade_femeas' => isset($f['quantidade_femeas']) ? (int) $f['quantidade_femeas'] : (isset($f['femeas']) ? (int) $f['femeas'] : null),
                                ];
                            }
                            $normalized[] = [
                                'especie' => $g['especie'] ?? null,
                                'raca' => $g['raca'] ?? null,
                                'faixas' => $faixas,
                            ];
                        }
                        $validated['details']['rebanho'] = $normalized;
                    } else {
                        $grouped = [];
                        foreach ($list as $item) {
                            if (!is_array($item)) continue;
                            $esp = $item['especie'] ?? null;
                            $rac = $item['raca'] ?? null;
                            $faixa = $item['faixa_etaria'] ?? ($item['faixa'] ?? null);
                            $machos = isset($item['quantidade_machos']) ? (int) $item['quantidade_machos'] : (isset($item['machos']) ? (int) $item['machos'] : null);
                            $femeas = isset($item['quantidade_femeas']) ? (int) $item['quantidade_femeas'] : (isset($item['femeas']) ? (int) $item['femeas'] : null);
                            $key = ($esp ?? '').'|'.($rac ?? '');
                            if (!isset($grouped[$key])) {
                                $grouped[$key] = [
                                    'especie' => $esp,
                                    'raca' => $rac,
                                    'faixas' => [],
                                ];
                            }
                            $grouped[$key]['faixas'][] = [
                                'faixa_etaria' => $faixa,
                                'quantidade_machos' => $machos,
                                'quantidade_femeas' => $femeas,
                            ];
                        }
                        $validated['details']['rebanho'] = array_values($grouped);
                    }
                }
            }

            $evaluation->update($validated);

            // Sincronizar rebanhos normalizados no update
            if (array_key_exists('details', $validated) && isset($validated['details']['rebanho']) && is_array($validated['details']['rebanho'])) {
                // Limpar e recriar para simplificar (poderia ser um sync incremental se necessário)
                $evaluation->herds()->delete();
                foreach ($validated['details']['rebanho'] as $h) {
                    $herd = \App\Models\PropertyEvaluationHerd::create([
                        'evaluation_id' => $evaluation->id,
                        'especie' => $h['especie'] ?? null,
                        'raca' => $h['raca'] ?? null,
                    ]);
                    foreach (($h['faixas'] ?? []) as $f) {
                        $machos = $f['quantidade_machos'] ?? ($f['machos'] ?? null);
                        $femeas = $f['quantidade_femeas'] ?? ($f['femeas'] ?? null);
                        \App\Models\PropertyEvaluationHerdAge::create([
                            'herd_id' => $herd->id,
                            'faixa_etaria' => $f['faixa_etaria'] ?? ($f['faixa'] ?? null),
                            'quantidade_machos' => isset($machos) && $machos !== '' ? (int) $machos : null,
                            'quantidade_femeas' => isset($femeas) && $femeas !== '' ? (int) $femeas : null,
                        ]);
                    }
                }
            }

            Log::info('Avaliação atualizada com sucesso');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Avaliação atualizada com sucesso!'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erro ao atualizar avaliação', [
                'property_id' => $property->id,
                'evaluation_id' => $evaluation->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar avaliação: ' . $e->getMessage()
            ], 500);
        }
    }

    // DELETE /properties/{property}/evaluations/{evaluation}
    public function destroy(Property $property, PropertyEvaluation $evaluation)
    {
        try {
            $user = Auth::user();

            if (!$this->canAccessProperty($property, $user)) {
                abort(403, 'Acesso não autorizado.');
            }

            // Verificar se a avaliação pertence à propriedade
            if ($evaluation->property_id !== $property->id) {
                abort(404);
            }

            // Permitir exclusão apenas pelo avaliador (autor do registro)
            if ($evaluation->user_id !== $user->id) {
                return back()->withErrors(['error' => 'Somente o avaliador que criou este registro pode excluí-lo.']);
            }

            Log::info('Excluindo avaliação', [
                'property_id' => $property->id,
                'evaluation_id' => $evaluation->id,
                'user_id' => Auth::id()
            ]);

            // Bloquear exclusão se já foi confirmado pelo proprietário
            if ($evaluation->owner_acknowledged_at) {
                return back()->withErrors(['error' => 'Esta avaliação já foi confirmada pelo proprietário e não pode ser excluída.']);
            }

            $evaluation->delete();

            return back()->with([
                'success' => true,
                'message' => 'Avaliação removida com sucesso!'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao excluir avaliação', [
                'property_id' => $property->id,
                'evaluation_id' => $evaluation->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Erro ao remover avaliação: ' . $e->getMessage()]);
        }
    }

    // PUT /properties/{property}/evaluations/{evaluation}/acknowledge
    public function acknowledge(Property $property, PropertyEvaluation $evaluation)
    {
        $user = Auth::user();
        // somente o proprietário cadastrado da propriedade pode confirmar
        $isOwner = PropertyUser::where('property_id', $property->id)
            ->where('user_id', $user->id)
            ->exists();
        if (!$isOwner) {
            abort(403, 'Apenas o proprietário pode confirmar o recebimento da avaliação.');
        }
        if ($evaluation->property_id !== $property->id) abort(404);

        $evaluation->update([
            'owner_acknowledged' => true,
            'owner_acknowledged_at' => now(),
            'owner_acknowledged_by' => $user->id,
        ]);

        return back()->with(['success' => true, 'message' => 'Avaliação confirmada pelo proprietário.']);
    }

    // POST /properties/{property}/evaluations/{evaluation}/media
    public function uploadMedia(Request $request, Property $property, PropertyEvaluation $evaluation)
    {
        $request->validate([
            'images.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:10240', // 10MB cada
        ]);

        if ($evaluation->property_id !== $property->id) abort(404);

        $totalSize = collect($request->file('images', []))->sum(fn($f) => $f->getSize());
        if ($totalSize > 10 * 1024 * 1024) {
            return back()->withErrors(['images' => 'O total de imagens excede 10MB.']);
        }

        $saved = [];
        foreach ($request->file('images', []) as $file) {
            $path = $file->store('evaluations/images', 'public');
            $saved[] = PropertyEvaluationMedia::create([
                'evaluation_id' => $evaluation->id,
                'path' => $path,
                'mime' => $file->getClientMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ]);
        }

        return back()->with(['success' => true, 'media_count' => count($saved)]);
    }

    // POST /properties/{property}/evaluations/{evaluation}/pdf
    public function generatePdf(Request $request, Property $property, PropertyEvaluation $evaluation)
    {
        if ($evaluation->property_id !== $property->id) abort(404);

        $data = $request->validate([
            'header_logo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'header_title' => 'nullable|string|max:255',
            'appraiser_name' => 'nullable|string|max:255',
            'appraiser_phone' => 'nullable|string|max:255',
            'appraiser_email' => 'nullable|email|max:255',
            'appraiser_registry' => 'nullable|string|max:255',
        ]);

        $logoPath = null;
        if (isset($data['header_logo'])) {
            $logoPath = $data['header_logo']->store('evaluations/logos', 'public');
        }

    // Recarregar a avaliação com relações e garantir details como array
    $evaluation->refresh()->loadMissing(['user', 'media', 'property', 'herds.ages']);

        // Spatie\LaravelPdf utiliza o Chrome (Puppeteer) via Browsershot. Forneça caminhos absolutos para imagens locais.
        $logoUrl = null;
        if ($logoPath) {
            $logoUrl = public_path('storage/'.ltrim($logoPath, '/'));
        }

        

        // Preparar details e, se necessário, preencher rebanho a partir das tabelas normalizadas
        $details = is_array($evaluation->details) ? $evaluation->details : (array) ($evaluation->details ?? []);
        if (empty($details['rebanho']) && $evaluation->relationLoaded('herds')) {
            $details['rebanho'] = $evaluation->herds->map(function($h){
                return [
                    'especie' => $h->especie,
                    'raca' => $h->raca,
                    'faixas' => $h->ages->map(fn($a) => [
                        'faixa_etaria' => $a->faixa_etaria,
                        'quantidade_machos' => $a->quantidade_machos,
                        'quantidade_femeas' => $a->quantidade_femeas,
                    ])->toArray(),
                ];
            })->toArray();
        }

        $html = view('pdfs.evaluation', [
            'property' => $property,
            'evaluation' => $evaluation,
            'details' => $details,
            'header' => [
                'logo_url' => $logoUrl, // caminho absoluto para o Chromium
                'title' => $data['header_title'] ?? 'Laudo de Avaliação',
                'appraiser' => [
                    'name' => $data['appraiser_name'] ?? $evaluation->appraiser,
                    'phone' => $data['appraiser_phone'] ?? null,
                    'email' => $data['appraiser_email'] ?? null,
                    'registry' => $data['appraiser_registry'] ?? null,
                ]
            ]
        ])->render();

        // dd($evaluation);

        // Gerar e salvar o PDF com Spatie\LaravelPdf diretamente no disco 'public'
        $pdfPath = 'evaluations/pdfs/evaluation_'.$evaluation->id.'.pdf';
        \Spatie\LaravelPdf\Facades\Pdf::html($html)
            ->format('a4')
            // Remover chamada inválida a showBackground() (já é aplicado internamente via Browsershot)
            // Definir margens (top, right, bottom, left) em mm
            ->margins(10, 10, 18, 10)
            // Persistir no storage público para disponibilizar via URL
            ->disk('public', 'public')
            ->save($pdfPath);

        $evaluation->update(['pdf_path' => $pdfPath]);

        return back()->with(['success' => true, 'pdf_url' => Storage::url($pdfPath)]);
    }

    // POST /properties/{property}/evaluations/{evaluation}/pdf
    public function testPdf(Request $request, Property $property, PropertyEvaluation $evaluation)
    {
         if ($evaluation->property_id !== $property->id) abort(404);

        $data = $request->validate([
            'header_logo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'header_title' => 'nullable|string|max:255',
            'appraiser_name' => 'nullable|string|max:255',
            'appraiser_phone' => 'nullable|string|max:255',
            'appraiser_email' => 'nullable|email|max:255',
            'appraiser_registry' => 'nullable|string|max:255',
        ]);

        $logoPath = null;
        if (isset($data['header_logo'])) {
            $logoPath = $data['header_logo']->store('evaluations/logos', 'public');
        }

        // Recarregar a avaliação com relações e garantir details como array
        $evaluation->refresh()->loadMissing(['user', 'media', 'property']);

        // Spatie\LaravelPdf utiliza o Chrome (Puppeteer) via Browsershot. Forneça caminhos absolutos para imagens locais.
        $logoUrl = null;
        if ($logoPath) {
            $logoUrl = public_path('storage/'.ltrim($logoPath, '/'));
        }

        
        return view('pdfs.evaluation', [
            'property' => $property,
            'evaluation' => $evaluation,
            'details' => is_array($evaluation->details) ? $evaluation->details : (array) ($evaluation->details ?? []),
            'header' => [
                'logo_url' => $logoPath ? Storage::url($logoPath) : null,
                'title' => $data['header_title'] ?? 'Laudo de Avaliação',
                'appraiser' => [
                    'name' => $data['appraiser_name'] ?? $evaluation->appraiser,
                    'phone' => $data['appraiser_phone'] ?? null,
                    'email' => $data['appraiser_email'] ?? null,
                    'registry' => $data['appraiser_registry'] ?? null,
                ]
            ]
        ]);
        // $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.test', [
        //     'property' => $property,
        //     'evaluation' => $evaluation,
        //     'details' => is_array($evaluation->details) ? $evaluation->details : (array) ($evaluation->details ?? []),
        //     'header' => [
        //         'logo_url' => $logoPath ? Storage::url($logoPath) : null,
        //         'title' => $data['header_title'] ?? 'Laudo de Avaliação',
        //         'appraiser' => [
        //             'name' => $data['appraiser_name'] ?? $evaluation->appraiser,
        //             'phone' => $data['appraiser_phone'] ?? null,
        //             'email' => $data['appraiser_email'] ?? null,
        //             'registry' => $data['appraiser_registry'] ?? null,
        //         ]
        //     ]
        // ]);

        // $pdfPath = 'evaluations/pdfs/evaluation_'.$evaluation->id.'.pdf';
        // Storage::disk('public')->put($pdfPath, $pdf->output());

        // $evaluation->update(['pdf_path' => $pdfPath]);

        // return back()->with(['success' => true, 'pdf_url' => Storage::url($pdfPath)]);
    }
}
