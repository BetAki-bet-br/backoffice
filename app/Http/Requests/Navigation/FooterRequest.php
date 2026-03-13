<?php

namespace App\Http\Requests\Navigation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FooterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ajusta pra tua policy (ex: return $this->user()->can('manage footers');)
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $footerId = $this->route('footer')?->id ?? null;

        return [
            'key' => [
                'required',
                'string',
                'max:191',
                Rule::unique('footers', 'key')->ignore($footerId),
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['draft', 'published', 'archived']),
            ],
            'country' => ['nullable', 'string', 'size:2'],
            'brand' => ['nullable', 'string', 'max:191'],
            'publish_at' => ['nullable', 'date'],

            'translations' => ['sometimes', 'array'],
            'translations.*.locale' => ['required_with:translations', 'string', 'max:5'],
            'translations.*.legal_title' => ['nullable', 'string', 'max:255'],
            'translations.*.legal_text' => ['nullable', 'string'],
            'translations.*.disclaimer' => ['nullable', 'string'],
        ];
    }
}
