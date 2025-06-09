<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id' => 'required|exists:properties,id',
            'user_id' => 'required|exists:users,id',
            'appraiser' => 'nullable|string|max:255',
            'valuation' => 'required|numeric|min:0',
            'comments' => 'nullable|string'
        ];
    }
}
