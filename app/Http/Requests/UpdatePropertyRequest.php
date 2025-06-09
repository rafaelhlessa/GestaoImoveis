<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use App\Models\PropertyUser;
use App\Models\Authorization;
use Illuminate\Support\Facades\DB;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        Log::info('=== UpdatePropertyRequest::authorize ===', [
            'user_id' => $this->user()->id,
            'user_profile' => $this->user()->profile_id,
            'property_route' => $this->route('property'),
            'route_params' => $this->route()->parameters()
        ]);

        // ✅ CORREÇÃO: Buscar o objeto Property pelo ID da rota
        $propertyId = $this->route('property');
        $user = $this->user();

        if (!$propertyId || !$user) {
            Log::warning('UpdatePropertyRequest - Dados faltando');
            return false;
        }

        // ✅ Buscar o objeto Property
        $property = \App\Models\Property::find($propertyId);
        if (!$property) {
            Log::warning('UpdatePropertyRequest - Property não encontrada:', ['id' => $propertyId]);
            return false;
        }

        // ✅ Usar a mesma lógica do controller
        $canEdit = $this->canEditProperty($user, $property);
        
        Log::info('UpdatePropertyRequest - Resultado:', ['can_edit' => $canEdit]);
        return $canEdit;
    }

    /**
     * ✅ MÉTODO CUSTOMIZADO: Mesma lógica do PropertyController
     */
    private function canEditProperty($user, $property)
    {
        Log::info('UpdatePropertyRequest::canEditProperty', [
            'user_id' => $user->id,
            'user_profile' => $user->profile_id,
            'property_id' => $property->id,
            'property_owner_id' => $property->owner_id
        ]);

        switch ($user->profile_id) {
            case 1: // Proprietário
                // Verifica ownership direto primeiro
                $isDirectOwner = $property->owner_id == $user->id;
                if ($isDirectOwner) {
                    Log::info('Request Profile 1 - AUTORIZADO por ownership direto');
                    return true;
                }

                // Verifica na tabela property_user
                $isOwner = \App\Models\PropertyUser::where('property_id', $property->id)
                    ->where('user_id', $user->id)
                    ->exists();
                
                if ($isOwner) {
                    Log::info('Request Profile 1 - AUTORIZADO por property_user');
                    return true;
                }

                Log::warning('Request Profile 1 - NEGADO');
                return false;

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

                Log::info('Request Profile 2 - Resultado:', ['can_edit' => $canEdit]);
                return $canEdit;

            case 3: // Proprietário/Prestador
                // Primeiro verifica se é proprietário direto
                $isDirectOwner = $property->owner_id == $user->id;
                if ($isDirectOwner) {
                    Log::info('Request Profile 3 - AUTORIZADO por ownership direto');
                    return true;
                }

                // Verifica na property_user
                $isOwner = \App\Models\PropertyUser::where('property_id', $property->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($isOwner) {
                    Log::info('Request Profile 3 - AUTORIZADO por property_user');
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

                Log::info('Request Profile 3 - Como prestador:', ['can_edit' => $canEditAsProvider]);
                return $canEditAsProvider;

            default:
                Log::warning('Request Profile desconhecido:', ['profile' => $user->profile_id]);
                return false;
        }
    }

    public function rules(): array
    {
        return [
            'is_active' => 'boolean',
            'title_deed' => ['required', 'integer'],
            'title_deed_number' => 'nullable|string|max:100',
            'other' => 'nullable|string|max:255',
            'area' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'type_property' => ['required', 'integer'],
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'city_id' => 'nullable|integer',
            'district' => 'nullable|string|max:100',
            'locality' => 'nullable|string|max:100',
            'nickname' => 'nullable|string|max:100',
            'about' => 'nullable|string',
            'file_photo' => 'string|nullable',
            'documents' => 'nullable|array',
            'documents.*.name' => 'required|string|max:255',
            'documents.*.file' => 'required|string', // Base64
            'documents.*.file_name' => 'required|string|max:255',
            'owners' => 'required|array|min:1',
            'owners.*.type_ownership_id' => 'required|integer',
            'owners.*.percentage' => 'required|integer|min:0|max:100',
        ];
    }

    /**
     * ✅ Mensagens de erro customizadas
     */
    public function messages(): array
    {
        return [
            'owners.required' => 'Pelo menos um proprietário deve ser adicionado.',
            'owners.min' => 'Pelo menos um proprietário deve ser adicionado.',
            'owners.*.type_ownership_id.required' => 'O tipo de propriedade é obrigatório.',
            'owners.*.percentage.required' => 'O percentual é obrigatório.',
            'owners.*.percentage.min' => 'O percentual deve ser maior que 0.',
            'owners.*.percentage.max' => 'O percentual não pode ser maior que 100.',
        ];
    }
}