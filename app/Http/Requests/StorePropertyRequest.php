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
        // // return false;
        // $user = $this->user();
        // $ownerId = $this->input('owner_id');

        // if (!$ownerId) {
        //     return false;
        // }

        // if ($user->id === (int) $ownerId) {
        //     return true;
        // }

        // return \App\Models\Authorization::where('user_id', $ownerId)
        //     ->where('authorized_user_id', $user->id)
        //     ->where('ativo', true)
        //     ->exists();
        $user = $this->user();
        $ownerId = $this->input('owner_id');

        if (!$ownerId) {
            return false;
        }

        $owner = \App\Models\User::find($ownerId);
        if (!$owner) {
            return false;
        }

        return $user->id === $ownerId
            || $user->cpf === $owner->cpf
            || \App\Models\Authorization::where('user_id', $ownerId)
                ->where('authorized_user_id', $user->id)
                ->where('ativo', true)
                ->exists();
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
            'file_photo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ];
    }

}
