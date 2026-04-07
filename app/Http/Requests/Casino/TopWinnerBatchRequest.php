<?php

namespace App\Http\Requests\Casino;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopWinnerBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'vertical' => ['required', Rule::in(['slots', 'live'])],
            'status' => ['required', Rule::in(['draft', 'review', 'published', 'archived'])],

            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after:period_start'],

            'top_n' => ['required', 'integer', 'min:1', 'max:100'],

            'criteria' => ['nullable', 'array'],
            'criteria.min_prize' => ['nullable', 'numeric', 'min:0'],
            'criteria.countries' => ['nullable', 'array'],
            'criteria.countries.*' => ['string', 'size:2'],
            'criteria.provider' => ['nullable', 'string', 'max:100'],
            'criteria.tags' => ['nullable', 'array'],
            'criteria.tags.*' => ['string', 'max:50'],
        ];
    }
}
