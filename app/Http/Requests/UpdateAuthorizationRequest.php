<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_id' => 'required|exists:users,id',
            'service_provider_id' => 'required|exists:users,id',
            'can_view_documents' => 'boolean',
            'can_create_properties' => 'boolean'
        ];
    }
}
