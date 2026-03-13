<?php

namespace App\Http\Requests\Casino;

use Illuminate\Foundation\Http\FormRequest;

class AwardedResultsSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.slot_id' => ['required', 'integer', 'exists:slots,id'],
            'items.*.position' => ['nullable', 'integer', 'min:0'],
            'items.*.wins_count' => ['nullable', 'integer', 'min:0'],
            'items.*.prize_sum' => ['nullable', 'numeric', 'min:0'],
            'items.*.max_prize' => ['nullable', 'numeric', 'min:0'],
            'items.*.avg_prize' => ['nullable', 'numeric', 'min:0'],
            'items.*.meta' => ['nullable', 'array'],
            'items.*.prize_sum_initial' => ['nullable', 'numeric', 'min:0'],
            'items.*.prize_sum_final' => ['nullable', 'numeric', 'min:0', 'gte:items.*.prize_sum_initial'],
            'items.*.increment_interval_minutes' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
