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
            'href'      => $this->href,
            'imageUrl'  => $this->image_url,
            'alt'       => $this->alt,
            'duration'  => $this->duration,
        ];
    }
}
