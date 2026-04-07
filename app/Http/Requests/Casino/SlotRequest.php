<?php

namespace App\Http\Requests\Casino;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $slot = $this->route('slot');
        $slotId = is_object($slot) ? $slot->id : $slot;

        return [
            'title' => ['required', 'string', 'max:180'],
            'cover_url' => ['nullable', 'image', 'mimes:jpeg,png,webp,gif', 'max:2048'],
            'status' => ['required', Rule::in(['active', 'inactive'])],

            'provider' => ['required', 'string', 'max:100'],
            'provider_game_id' => [
                'required', 'string', 'max:150',
                Rule::unique('slots', 'provider_game_id')
                    ->where(fn ($q) => $q->where('provider', $this->input('provider')))
                    ->ignore($slotId),
            ],

            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],

            'position' => ['nullable', 'integer', 'min:0'],

            // New betting data fields
            'rtp' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'volatility' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'min_bet' => ['nullable', 'numeric', 'min:0.01'],
            'max_bet' => ['nullable', 'numeric', 'gte:min_bet'],
        ];
    }
}
