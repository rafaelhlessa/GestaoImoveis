<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class StorePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'cpf_cnpj' => preg_replace('/\D+/', '', $this->cpf_cnpj),
            'phone' => preg_replace('/\D+/', '', $this->phone),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => ['required','email','max:255',Rule::unique(User::class)->ignore($this->user()->id)],
            'cpf_cnpj' => 'required|digits_between:11,14',
            'phone' => 'required|digits_between:10,11',
        ];
    }

}