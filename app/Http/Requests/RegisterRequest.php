<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:255'],
            'cpf_cnpj'              => ['required', 'string', 'min:11', 'max:14', 'unique:users,cpf_cnpj'],
            'phone'                 => ['required', 'string', 'min:10', 'max:11'],
            'address'               => ['required', 'string', 'max:255'],
            'city'                 => ['required', 'string', 'max:255'],
            'city_id'               => ['required', 'integer'],
            'profile_id'            => ['required', 'integer'],
            'activity_id'           => ['nullable', 'integer', 'exists:activities,id'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }
}
