<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use App\Models\PropertyUser;
use App\Models\Authorization;
use Illuminate\Support\Facades\DB;

class UpdatePropertyRequest extends FormRequest
{
    private function estimateBase64Bytes(?string $dataUrl): int
    {
        if (!$dataUrl) return 0;
        // Remove prefixo data:*;base64,
        $clean = preg_replace('/^data:[^;]+;base64,/', '', (string) $dataUrl);
        return (int) floor(strlen($clean) * 0.75);
    }

    protected function withValidator($validator)
    {
        $validator->after(function ($v) {
            $docs = $this->input('documents', []);
            if (!is_array($docs)) return;
            foreach ($docs as $idx => $doc) {
                if (!isset($doc['file'])) continue;
                $bytes = $this->estimateBase64Bytes($doc['file']);
                if ($bytes > 6 * 1024 * 1024) {
                    $v->errors()->add("documents.$idx.file", 'Arquivo do documento excede 6MB.');
                }
            }
        });
    }
    public function authorize(): bool
    {
        Log::info('=== UpdatePropertyRequest::authorize ===', [
            'user_id' => $this->user()->id,
            'user_profiles' => $this->user()->profiles->pluck('slug')->toArray(),
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
            'user_profiles' => $user->profiles->pluck('slug')->toArray(),
            'property_id' => $property->id,
            'property_owner_id' => $property->owner_id
        ]);

        // Proprietário: pode editar se for dono direto ou via property_user
        if ($user->hasProfile('proprietario')) {
            $isDirectOwner = $property->owner_id == $user->id;
            if ($isDirectOwner) {
                Log::info('Request Proprietario - AUTORIZADO por ownership direto');
                return true;
            }
            $isOwner = \App\Models\PropertyUser::where('property_id', $property->id)
                ->where('user_id', $user->id)
                ->exists();
            if ($isOwner) {
                Log::info('Request Proprietario - AUTORIZADO por property_user');
                return true;
            }
        }

        // Prestador: pode editar se tiver autorização
        if ($user->hasProfile('prestador')) {
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
            Log::info('Request Prestador - Resultado:', ['can_edit' => $canEdit]);
            if ($canEdit) {
                return true;
            }
        }

        Log::warning('Request - NEGADO para perfis do usuário', ['profiles' => $user->profiles->pluck('slug')->toArray()]);
        return false;
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
            'documents.*.name' => 'required_with:documents.*|string|max:255',
            'documents.*.file' => 'required_with:documents.*|string', // Base64 - obrigatório apenas se o documento for fornecido
            'documents.*.file_name' => 'required_with:documents.*|string|max:255',
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
