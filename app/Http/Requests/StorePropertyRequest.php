<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Authorization;
use App\Helpers\UserAccessHelper;

class StorePropertyRequest extends FormRequest
{
    private function estimateBase64Bytes(?string $dataUrl): int
    {
        if (!$dataUrl) return 0;
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
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $authUser = $this->user();

        // Se não há usuário autenticado, nega acesso
        if (!$authUser) {
            return false;
        }

        $ownerId = $this->input('owner_id') ?? $authUser->id;
        $owner = User::find($ownerId);

        if (!$owner) {
            return false;
        }

        // Verifica se tem o helper - se não, usa lógica básica
        if (class_exists(UserAccessHelper::class)) {
            return UserAccessHelper::canCreateForOwner($authUser, $owner);
        }

        // Lógica básica de fallback
        return $this->canCreateForOwnerBasic($authUser, $owner);

    }

    private function canCreateForOwnerBasic($authUser, $owner): bool
    {
        // Se está criando para si mesmo
        if ($authUser->id === $owner->id) {
            return $authUser->hasProfile('proprietario');
        }

        // Se é prestador de serviço tentando criar para outro
        if ($authUser->hasProfile('prestador')) {
            return Authorization::where('service_provider_id', $authUser->id)
                ->where('owner_id', $owner->id)
                ->where('can_create_properties', true)
                ->exists();
        }

        return false;
    }

    protected function prepareForValidation()
    {
        if (!$this->has('owner_id') && $this->user()) {
            $this->merge([
                'owner_id' => $this->user()->id,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'owner_id' => ['nullable', 'exists:users,id'],
            'is_active' => 'boolean',
            'title_deed' => ['required', 'integer'],
            'title_deed_number' => 'nullable|string|max:100',
            'type_property' => ['required', 'integer'],
            'other' => 'nullable|string|max:255',
            'area' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'type_property' => 'nullable|integer|max:100',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'city_id' => 'nullable|integer',
            'district' => 'nullable|string|max:100',
            'locality' => 'nullable|string|max:100',
            'nickname' => 'nullable|string|max:100',
            'about' => 'nullable|string',
            'file_photo' => ['nullable', 'string'],
            // Documentos (opcional no create)
            'documents' => 'nullable|array',
            'documents.*.name' => 'required_with:documents.*|string|max:255',
            'documents.*.file' => 'required_with:documents.*|string',
            'documents.*.file_name' => 'required_with:documents.*|string|max:255',
        ];
    }

}
