<?php

namespace App\Http\Requests\Casino;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShowcaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $showcase = $this->route('showcase');
        $id = is_object($showcase) ? $showcase->id : $showcase;

        return [
            'title' => ['required', 'string', 'max:150'],
            'slug' => [
                'nullable', 'string', 'max:150',
                Rule::unique('showcases', 'slug')->ignore($id),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'type' => ['required', Rule::in(['manual', 'dynamic'])],
            'position' => ['nullable', 'integer', 'min:0'],
            'filters' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->input('slug') ?: Str::slug($this->input('title')),
        ]);
    }
}
