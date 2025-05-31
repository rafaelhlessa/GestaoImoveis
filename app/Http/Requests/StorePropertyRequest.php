<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Authorization;
use App\Helpers\UserAccessHelper;

class StorePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        $authUser = $this->user();
        $ownerId = $this->input('owner_id') ?? $authUser->id;
        $owner = \App\Models\User::find($ownerId);

        if (!$owner) {
            return false;
        }

        return \App\Helpers\UserAccessHelper::canCreateForOwner($authUser, $owner);

    }

    protected function prepareForValidation()
    {
        if (!$this->has('owner_id') && auth()->check()) {
            $this->merge([
                'owner_id' => auth()->id(),
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
        ];
    }

}
