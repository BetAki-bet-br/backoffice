<?php

namespace App\Http\Requests\Casino;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class TopListRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $topList = $this->route('toplist') ?? $this->route('top_list') ?? $this->route('topList');
        $id = is_object($topList) ? $topList->id : $topList;

        return [
            'title'     => ['required','string','max:150'],
            'slug'      => ['nullable','string','max:150', Rule::unique('top_lists','slug')->ignore($id)],
            'vertical'  => ['required', Rule::in(['slots','live'])],
            'type'      => ['required', Rule::in(['manual','auto'])],
            'status'    => ['required', Rule::in(['draft','scheduled','published','archived'])],
            'position'  => ['nullable','integer','min:0'],

            'valid_from'=> ['nullable','date'],
            'valid_until'=>['nullable','date','after_or_equal:valid_from'],

            'criteria'  => ['nullable','array'],
            'criteria.metric'  => ['nullable','string','max:50'],
            'criteria.period'  => ['nullable','string','max:20'],
            'criteria.countries' => ['nullable','array'],
            'criteria.countries.*' => ['string','size:2'],
            'criteria.provider' => ['nullable','string','max:100'],
            'criteria.tags' => ['nullable','array'],
            'criteria.tags.*' => ['string','max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->input('slug') ?: Str::slug($this->input('title','')),
        ]);
    }
}