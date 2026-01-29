<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'productTypeName' => $this->product_type_name,
            'verticals' => $this->verticals,
            'status' => $this->status,
            'position' => $this->position,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            $this->mergeWhen($this->relationLoaded('slots'), [
                'slots' => SlotResource::collection($this->slots),
                'slots_count' => $this->slots->count(),
            ]),
        ];
    }
}
