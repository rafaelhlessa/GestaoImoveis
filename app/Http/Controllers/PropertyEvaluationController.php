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
        switch ($user->profile_id) {
            case 1: // Proprietário puro
                return PropertyUser::where('property_id', $property->id)
                    ->where('user_id', $user->id)
                    ->exists();

            case 2: // Prestador de serviço
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

            case 3: // Proprietário/Prestador
                $isOwner = PropertyUser::where('property_id', $property->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($isOwner) return true;

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

            default:
                return false;
        }
    }

    /**
     * Verificar se o usuário pode criar avaliações para a propriedade
     */
    private function canCreateEvaluation(Property $property, $user)
    {
        // Carregar o usuário com atividade se necessário
        if (!$user->activity) {
            $user = \App\Models\User::with('activity')->find($user->id);
        }

        switch ($user->profile_id) {
            case 1: // Proprietário puro - NUNCA pode avaliar
                return false;

            case 2: // Prestador de serviço
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

                return $hasAuthorization && 
                       $user->activity && 
                       (bool) $user->activity->evaluation_permission;

            case 3: // Proprietário/Prestador
                $isOwner = PropertyUser::where('property_id', $property->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($isOwner) {
                    return $user->activity && (bool) $user->activity->evaluation_permission;
                }

                // Se não é proprietário, verifica como prestador
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

                return $hasAuthorization && 
                       $user->activity && 
                       (bool) $user->activity->evaluation_permission;

            default:
                return false;
        }
    }

    // GET /properties/{property}/evaluations
    public function index(Property $property)
    {
        try {
            $user = Auth::user();
            
            if (!$this->canAccessProperty($property, $user)) {
                abort(403, 'Acesso não autorizado.');
            }
            
            $evaluations = PropertyEvaluation::with('user')
                ->where('property_id', $property->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return Inertia::render('Properties/PropertyEvaluationList', [
                'property' => $property,
                'evaluations' => $evaluations
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
            
            // VERIFICAÇÃO SIMPLIFICADA: só verifica se é prestador de serviço
            if ($user->profile_id !== 2 && $user->profile_id !== 3) {
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
                // Adicionar validações para os novos campos rurais
                'animals' => 'nullable|array',
                'animals.*.type' => 'required|string',
                'animals.*.category' => 'nullable|string',
                'animals.*.quantity' => 'required|integer|min:1',
                'animals.*.unit_price' => 'required|numeric|min:0',
                'animals.*.observations' => 'nullable|string',
                'machinery' => 'nullable|array',
                'machinery.*.type' => 'required|string',
                'machinery.*.brand' => 'nullable|string',
                'machinery.*.model' => 'nullable|string',
                'machinery.*.year' => 'nullable|integer',
                'machinery.*.condition' => 'nullable|string',
                'machinery.*.quantity' => 'required|integer|min:1',
                'machinery.*.unit_price' => 'required|numeric|min:0',
                'machinery.*.observations' => 'nullable|string',
                'structures' => 'nullable|array',
                'structures.*.type' => 'required|string',
                'structures.*.quantity' => 'required|integer|min:1',
                'structures.*.area' => 'nullable|numeric|min:0',
                'structures.*.condition' => 'nullable|string',
                'structures.*.description' => 'nullable|string',
                'structures.*.specifications' => 'nullable|string',
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

            // Extrair dados dos relacionamentos antes de criar a avaliação
            $animalsData = $validated['animals'] ?? [];
            $machineryData = $validated['machinery'] ?? [];
            $structuresData = $validated['structures'] ?? [];
            
            // Remover os arrays dos dados da avaliação principal
            unset($validated['animals'], $validated['machinery'], $validated['structures']);

            DB::beginTransaction();

            $evaluation = PropertyEvaluation::create($validated);

            Log::info('Avaliação criada!', ['evaluation_id' => $evaluation->id]);

            // Salvar animais se houver
            if (!empty($animalsData)) {
                foreach ($animalsData as $animalData) {
                    $evaluation->animals()->create($animalData);
                }
                Log::info('Animais salvos', ['count' => count($animalsData)]);
            }

            // Salvar maquinário se houver  
            if (!empty($machineryData)) {
                foreach ($machineryData as $machineData) {
                    $evaluation->machinery()->create($machineData);
                }
                Log::info('Maquinário salvo', ['count' => count($machineryData)]);
            }

            // Salvar estruturas se houver
            if (!empty($structuresData)) {
                foreach ($structuresData as $structureData) {
                    $evaluation->structures()->create($structureData);
                }
                Log::info('Estruturas salvas', ['count' => count($structuresData)]);
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

            $evaluation->load(['user', 'property', 'animals', 'machinery', 'structures']);

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
            
            // Validação expandida
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
                // Validações para os novos campos rurais
                'animals' => 'nullable|array',
                'animals.*.type' => 'required|string',
                'animals.*.category' => 'nullable|string',
                'animals.*.quantity' => 'required|integer|min:1',
                'animals.*.unit_price' => 'required|numeric|min:0',
                'animals.*.observations' => 'nullable|string',
                'machinery' => 'nullable|array',
                'machinery.*.type' => 'required|string',
                'machinery.*.brand' => 'nullable|string',
                'machinery.*.model' => 'nullable|string',
                'machinery.*.year' => 'nullable|integer',
                'machinery.*.condition' => 'nullable|string',
                'machinery.*.quantity' => 'required|integer|min:1',
                'machinery.*.unit_price' => 'required|numeric|min:0',
                'machinery.*.observations' => 'nullable|string',
                'structures' => 'nullable|array',
                'structures.*.type' => 'required|string',
                'structures.*.quantity' => 'required|integer|min:1',
                'structures.*.area' => 'nullable|numeric|min:0',
                'structures.*.condition' => 'nullable|string',
                'structures.*.description' => 'nullable|string',
                'structures.*.specifications' => 'nullable|string',
            ]);
            
            // Garantir que não altere property_id e user_id
            $validated['user_id'] = $evaluation->user_id;

            // Extrair dados dos relacionamentos antes de atualizar a avaliação principal
            $animalsData = $validated['animals'] ?? [];
            $machineryData = $validated['machinery'] ?? [];
            $structuresData = $validated['structures'] ?? [];
            
            // Remover os arrays dos dados da avaliação principal
            unset($validated['animals'], $validated['machinery'], $validated['structures']);

            Log::info('Atualizando avaliação', ['validated' => $validated]);

            DB::beginTransaction();

            $evaluation->update($validated);

            // Atualizar relacionamentos rurais - remover existentes e recriar
            $evaluation->animals()->delete();
            $evaluation->machinery()->delete();
            $evaluation->structures()->delete();

            // Recriar animais se houver
            if (!empty($animalsData)) {
                foreach ($animalsData as $animalData) {
                    $evaluation->animals()->create($animalData);
                }
                Log::info('Animais atualizados', ['count' => count($animalsData)]);
            }

            // Recriar maquinário se houver  
            if (!empty($machineryData)) {
                foreach ($machineryData as $machineData) {
                    $evaluation->machinery()->create($machineData);
                }
                Log::info('Maquinário atualizado', ['count' => count($machineryData)]);
            }

            // Recriar estruturas se houver
            if (!empty($structuresData)) {
                foreach ($structuresData as $structureData) {
                    $evaluation->structures()->create($structureData);
                }
                Log::info('Estruturas atualizadas', ['count' => count($structuresData)]);
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
            
            Log::info('Excluindo avaliação', [
                'property_id' => $property->id,
                'evaluation_id' => $evaluation->id,
                'user_id' => Auth::id()
            ]);

            $evaluation->delete();

            return response()->json([
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

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover avaliação: ' . $e->getMessage()
            ], 500);
        }
    }
}