<?php

namespace App\Http\Requests\Banners;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $banner = $this->route('banner');
        $bannerId = is_object($banner) ? $banner->id : $banner;

        return [
            // identificadores/estado
            'slug' => [
                'required', 'string', 'max:140',
                Rule::unique('banners', 'slug')->ignore($bannerId),
            ],
            'status' => ['required', Rule::in(['draft', 'review', 'scheduled', 'published', 'archived'])],

            // janela de publicação
            'publish_at' => ['nullable', 'date'],
            'expire_at' => ['nullable', 'date', 'after:publish_at'],

            // targeting por país (ISO 3166-1 alpha-2)
            'countries' => ['nullable', 'array'],
            'countries.*' => ['string', 'regex:/^[A-Z]{2}$/'],

            // link + UTM
            'link_url' => ['nullable', 'url', 'max:2000'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:120'],

            // mídia (por breakpoint)
            'cover_url' => ['nullable', 'image', 'mimes:jpeg,png,webp,gif', 'max:2048'],
            'remove_cover_url' => ['nullable', 'boolean'],
            'media' => ['nullable', 'array'],
            'media.desktop' => ['nullable', 'url', 'max:2000'],
            'media.mobile' => ['nullable', 'url', 'max:2000'],

            // traduções
            'translations' => ['nullable', 'array'],
            'translations.*.locale' => ['required_with:translations', 'string', 'regex:/^[a-z]{2}(-[A-Z]{2})?$/'],
            'translations.*.title' => ['nullable', 'string', 'max:140'],
            'translations.*.alt_text' => ['nullable', 'string', 'max:140'],
            'translations.*.media' => ['nullable', 'array'],
            'translations.*.media.desktop' => ['nullable', 'url', 'max:2000'],
            'translations.*.media.mobile' => ['nullable', 'url', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'countries.*.regex' => 'Cada país deve ser ISO-3166-1 alpha-2 (ex.: BR, US).',
            'translations.*.locale.regex' => 'Locale deve ser "pt-BR" ou "en" (aa ou aa-BB).',
            'expire_at.after' => 'expire_at deve ser posterior a publish_at.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // normaliza maiúsculas de countries
        if (is_array($this->countries)) {
            $this->merge(['countries' => array_map(fn ($c) => strtoupper($c), $this->countries)]);
        }
    }
}
