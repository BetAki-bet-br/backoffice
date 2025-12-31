<?php

namespace App\Http\Requests\Casino;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $category = $this->route('category');
        $id = is_object($category) ? $category->id : $category;

        return [
            'name'     => ['required','string','max:120'],
            'slug'     => [
                'nullable','string','max:150',
                Rule::unique('categories','slug')->ignore($id),
            ],
            'status'   => ['required', Rule::in(['active','inactive'])],
            'position' => ['nullable','integer','min:0'],
            'meta'     => ['nullable','array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->input('slug') ?: \Str::slug($this->input('name', '')),
            'status' => $this->input('status', 'active'),
        ]);
    }
}