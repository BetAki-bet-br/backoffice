<?php

namespace App\Http\Requests\Navigation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'depth' => ['nullable', 'integer', 'min:0', 'max:5'],

            'title' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:120'],

            'is_external' => ['boolean'],
            'url' => ['nullable', 'string', 'max:2000'],
            'route_name' => ['nullable', 'string', 'max:150'],
            'route_params' => ['nullable', 'array'],
            'target' => ['nullable', Rule::in(['_self', '_blank'])],

            'status' => ['required', Rule::in(['active', 'inactive'])],
            'position' => ['nullable', 'integer', 'min:0'],

            'visible_roles' => ['nullable', 'array'],
            'visible_roles.*' => ['string', 'max:100'],

            'visible_permissions' => ['nullable', 'array'],
            'visible_permissions.*' => ['string', 'max:150'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_external' => (bool) $this->input('is_external', false),
            'status' => $this->input('status', 'active'),
            'depth' => $this->input('parent_id') ? (int) $this->input('depth', 1) : 0,
        ]);
    }
}
