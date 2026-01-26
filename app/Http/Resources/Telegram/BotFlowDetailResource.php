<?php

declare(strict_types=1);

namespace App\Http\Resources\Telegram;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BotFlowDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'status' => $this->status,
            'is_default' => $this->is_default,
            'flow_data' => $this->flow_data,
            'steps' => $this->steps->map(fn($step) => [
                'id' => $step->id,
                'type' => $step->type,
                'order' => $step->order,
                'data' => $step->data,
                'metadata' => $step->metadata,
            ]),
            'created_by' => $this->createdBy?->name,
            'updated_by' => $this->updatedBy?->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
