<?php

namespace App\Http\Requests\Casino;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameExtraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $extra = $this->route('game_extra') ?? $this->route('gameExtra') ?? null;
        $id = is_object($extra) ? $extra->id : $extra;

        return [
            'external_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('game_extras', 'external_id')->ignore($id),
            ],
            'rtp' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'volatility' => ['nullable', 'integer', 'min:1', 'max:5'],
            'min_bet' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:30'],
        ];
    }
}
