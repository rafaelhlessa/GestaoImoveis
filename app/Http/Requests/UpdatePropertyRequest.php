<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return true;
        $property = $this->route('property');

        return $property && $this->user()->can('update', $property);
    }

    public function rules(): array
    {
        return [
            'is_active' => 'boolean',
            'title_deed' => 'nullable|string|max:255',
            'title_deed_number' => 'nullable|string|max:100',
            'other' => 'nullable|string|max:255',
            'area' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'type_property' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'city_id' => 'nullable|integer|exists:cities,id',
            'district' => 'nullable|string|max:100',
            'locality' => 'nullable|string|max:100',
            'nickname' => 'nullable|string|max:100',
            'about' => 'nullable|string',
            'file_photo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'documents' => 'nullable|array',
            'documents.*.name' => 'required|string|max:255',
            'documents.*.file' => 'required|string', // Alterado para aceitar Base64
            'documents.*.file_name' => 'required|string|max:255',
            'owners' => 'required|array|min:1',
            // 'owners.*.id' => 'integer', // Garante que o usuário existe
            'owners.*.type_ownership_id' => 'required|integer',
            'owners.*.percentage' => 'required|integer|min:0|max:100',
        ];
    }
}
