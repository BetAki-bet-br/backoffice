<?php

namespace App\Http\Requests\Casino;

use Illuminate\Foundation\Http\FormRequest;

class ShowcaseSlotsSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.slot_id' => ['required', 'integer', 'exists:slots,id'],
            'items.*.position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
