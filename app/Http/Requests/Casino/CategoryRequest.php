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
            'cover_url' => ['nullable','image','mimes:jpeg,png,webp,gif','max:2048'],
            'verticals' => ['nullable', 'array'],
            'verticals.*' => ['string', Rule::in(['slots','live'])],
            'type'     => ['nullable', 'string', Rule::in(['game-list','recent-games','mais-premiados','winners-list','top-10-list','providers-carousel'])],
            'status'   => ['required', Rule::in(['active','inactive'])],
            'position' => ['nullable','integer','min:0'],
            'meta'     => ['nullable','array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $verticals = $this->input('verticals');
        if (empty($verticals) && $this->has('vertical')) {
            $verticals = [$this->input('vertical')];
        }

        $this->merge([
            'slug' => $this->input('slug') ?: \Str::slug($this->input('name', '')),
            'verticals' => is_array($verticals) ? $verticals : ['slots'],
            'type' => $this->input('type', 'game-list'),
            'status' => $this->input('status', 'active'),
        ]);
    }
}