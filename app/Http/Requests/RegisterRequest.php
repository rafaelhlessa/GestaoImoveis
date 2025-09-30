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
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'cpf_cnpj' => 'required|string|max:18',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:255',
            'city_id' => 'required|integer',
            'profiles' => 'required|array|min:1',
            'profiles.*' => 'required|string|in:administrador,gestor,proprietario,prestador',
            'activity_id' => 'nullable|integer|exists:activity,id|required_if:profiles.*,prestador',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'name.max' => 'O nome não pode ter mais de 255 caracteres.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ter um formato válido.',
            'email.unique' => 'Este email já está sendo usado por outro usuário.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'cpf_cnpj.required' => 'O CPF ou CNPJ é obrigatório.',
            'phone.required' => 'O telefone é obrigatório.',
            'address.required' => 'O endereço é obrigatório.',
            'city.required' => 'A cidade é obrigatória.',
            'city_id.required' => 'Selecione uma cidade válida.',
            'city_id.integer' => 'O ID da cidade deve ser um número.',
            'profiles.required' => 'Selecione pelo menos um perfil.',
            'profiles.min' => 'Selecione pelo menos um perfil.',
            'profiles.*.in' => 'Perfil inválido selecionado.',
            'activity_id.required_if' => 'A atividade é obrigatória para prestadores de serviço.',
            'activity_id.exists' => 'A atividade selecionada não é válida.',
        ];
    }
}
