<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarouselSlideResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'href' => $this->href,
            'imageUrl' => $this->image_url,
            'alt' => $this->alt,
            'duration' => $this->duration,
            'is_active' => (bool) $this->is_active,
            'publish_at' => $this->publish_at ? $this->publish_at->toIso8601String() : null,
            'expire_at' => $this->expire_at ? $this->expire_at->toIso8601String() : null,
        ];
    }
}
