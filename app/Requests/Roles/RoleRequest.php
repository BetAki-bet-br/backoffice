<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('role');
        $roleId = is_object($id) ? $id->id : $id;

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'guard_name' => ['nullable', 'in:api,web'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:190'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'guard_name' => $this->input('guard_name', 'api'),
        ]);
    }
}