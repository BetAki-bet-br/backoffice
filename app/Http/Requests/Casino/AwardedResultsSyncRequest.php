<?php

namespace App\Http\Requests\Casino;

use Illuminate\Foundation\Http\FormRequest;

class AwardedResultsSyncRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'items' => ['required','array','min:1'],
            'items.*.slot_id'    => ['required','integer','exists:slots,id'],
            'items.*.rank'       => ['required','integer','min:1'],
            'items.*.position'   => ['nullable','integer','min:0'],
            'items.*.wins_count' => ['nullable','integer','min:0'],
            'items.*.prize_sum'  => ['nullable','numeric','min:0'],
            'items.*.max_prize'  => ['nullable','numeric','min:0'],
            'items.*.avg_prize'  => ['nullable','numeric','min:0'],
            'items.*.meta'       => ['nullable','array'],
        ];
    }
}