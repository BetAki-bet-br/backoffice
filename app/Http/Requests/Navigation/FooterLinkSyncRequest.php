<?php

namespace App\Http\Requests\Navigation;

use Illuminate\Foundation\Http\FormRequest;

class FooterLinkSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ajusta pra tua policy
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'links'   => ['required', 'array'],
            'links.*.id'       => ['nullable', 'integer', 'exists:footer_links,id'],
            'links.*.block'    => ['nullable', 'string', 'max:191'],
            'links.*.label'    => ['required', 'string', 'max:191'],
            'links.*.url'      => ['required', 'string', 'max:2048'],
            'links.*.icon'     => ['nullable', 'string', 'max:191'],
            'links.*.target'   => ['nullable', 'string', 'max:20'],
            'links.*.position' => ['nullable', 'integer', 'min:0'],
            'links.*.is_active'=> ['nullable', 'boolean'],
        ];
    }
}
