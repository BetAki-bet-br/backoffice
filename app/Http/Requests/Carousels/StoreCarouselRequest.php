<?php

namespace App\Http\Requests\Carousels;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarouselRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Assuming authorization is handled by middleware (e.g., Sanctum with permissions)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:carousels,slug',
            'slides' => 'nullable|array',
            'slides.*.href' => 'required_with:slides|string|max:255',
            'slides.*.alt' => 'required_with:slides|string|max:255',
            'slides.*.duration' => 'nullable|string|max:50',
            'slides.*.order' => 'required_with:slides|integer|min:0',
            'slides.*.is_active' => 'nullable|boolean',
            'slides.*.image_url' => 'nullable|string|max:255',
            'slides.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'slides.*.publish_at' => 'nullable|date',
            'slides.*.expire_at' => 'nullable|date|after:slides.*.publish_at',
        ];
    }
}
