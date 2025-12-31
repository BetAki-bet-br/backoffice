<?php

namespace App\Http\Requests\Casino;

use Illuminate\Foundation\Http\FormRequest;

class TopListSlotsSyncRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'items' => ['required','array','min:1'],
            'items.*.slot_id' => ['required','integer','exists:slots,id'],
            'items.*.position' => ['nullable','integer','min:0'],
        ];
    }
}