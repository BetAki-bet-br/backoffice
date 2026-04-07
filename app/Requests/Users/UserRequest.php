<?php

namespace App\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // já autenticado via Sanctum
        return true;
    }

    public function rules(): array
    {
        $userParam = $this->route('user');
        $userId = is_object($userParam) ? $userParam->id : $userParam;

        return [
            'name' => ['required', 'string', 'max:190'],
            'email' => [
                'required', 'email', 'max:190',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:8'],
            'status' => ['nullable', 'string', Rule::in(['active', 'suspended', 'disabled'])],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status', 'active'),
        ]);
    }
}
