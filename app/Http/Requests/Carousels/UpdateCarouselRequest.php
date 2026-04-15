<?php

namespace App\Http\Requests\Carousels;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarouselRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $carouselId = $this->route('carousel')->id;

        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('carousels')->ignore($carouselId),
            ],
            'slides' => 'sometimes|array',
            'slides.*.id' => 'nullable|integer|exists:carousel_slides,id',
            'slides.*.href' => 'required_with:slides|string|max:255',
            'slides.*.alt' => 'required_with:slides|string|max:255',
            'slides.*.duration' => 'nullable|string|max:50',
            'slides.*.order' => 'required_with:slides|integer|min:0',
            'slides.*.is_active' => 'nullable|boolean',
            'slides.*.image_url' => 'nullable|string|max:255',
            'slides.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:10240',
            'slides.*.publish_at' => 'nullable|date',
            'slides.*.expire_at' => 'nullable|date|after:slides.*.publish_at',
        ];
    }
}
