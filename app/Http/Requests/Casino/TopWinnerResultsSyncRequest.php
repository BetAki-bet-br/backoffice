<?php

namespace App\Http\Requests\Casino;

use Illuminate\Foundation\Http\FormRequest;

class TopWinnerResultsSyncRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'items' => ['required','array','min:1'],
            'items.*.player_ref'   => ['nullable','string','max:191'], // hash/opaque id
            'items.*.display_name' => ['required','string','max:80'],  // "Jogador #1234" (já anonimizado)
            'items.*.country'      => ['nullable','string','size:2'],

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