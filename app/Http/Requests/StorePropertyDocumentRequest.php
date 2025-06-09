<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'date' => 'nullable|date',
            'show' => 'boolean',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'file_name' => 'nullable|string|max:255',
            'property_id' => 'required|exists:properties,id'
        ];
    }
}
