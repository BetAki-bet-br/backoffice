<?php

declare(strict_types=1);

namespace App\Http\Resources\Telegram;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TelegramBotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'status' => $this->status,
            'portal_id' => $this->portal_id,
            'debug_mode' => $this->debug_mode,
            'webhook_configured' => $this->hasWebhookConfigured(),
            'webhook_tested' => $this->hasWebhookTested(),
            'flows_count' => $this->flows_count ?? $this->flows()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
