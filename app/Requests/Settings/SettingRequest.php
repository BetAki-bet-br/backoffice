<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $settingParam = $this->route('setting');
        $id = is_object($settingParam) ? $settingParam->id : $settingParam;

        return [
            'key'        => ['required','string','max:190', Rule::unique('settings','key')->ignore($id)],
            'group'      => ['nullable','string','max:100'],
            'type'       => ['required', Rule::in(['string','number','boolean','json'])],
            'value'      => ['nullable'],
            'is_public'  => ['boolean'],
            'description'=> ['nullable','string','max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_public' => filter_var($this->input('is_public', false), FILTER_VALIDATE_BOOLEAN),
            'type'      => $this->input('type', 'json'),
        ]);
    }
}