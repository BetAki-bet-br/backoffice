<?php

namespace App\Http\Requests\Navigation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class MenuRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $menu = $this->route('menu');
        $id   = is_object($menu) ? $menu->id : $menu;

        return [
            'name'     => ['required','string','max:120'],
            'slug'     => ['nullable','string','max:100', Rule::unique('menus','slug')->ignore($id)],
            'status'   => ['required', Rule::in(['active','inactive'])],
            'position' => ['nullable','integer','min:0'],
            'meta'     => ['nullable','array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->input('slug') ?: Str::slug($this->input('name','')),
            'status' => $this->input('status','active'),
        ]);
    }
}