<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlotResource extends JsonResource
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
            'title' => $this->title,
            'cover_url' => $this->cover_url,
            'cover_path' => $this->cover_path,
            'status' => $this->status,
            'provider' => $this->provider,
            'provider_game_id' => $this->provider_game_id,
            'tags' => $this->tags,
            'position' => $this->position,
            'rtp' => $this->rtp,
            'volatility' => $this->volatility,
            'min_bet' => $this->min_bet,
            'max_bet' => $this->max_bet,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
