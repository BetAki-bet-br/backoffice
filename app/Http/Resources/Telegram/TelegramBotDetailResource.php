<?php

declare(strict_types=1);

namespace App\Http\Resources\Telegram;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TelegramBotDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'bot_token' => substr($this->bot_token, 0, 20) . '...', // Não expor token completo
            'username' => $this->username,
            'description' => $this->description,
            'api_url' => $this->api_url,
            'portal_id' => $this->portal_id,
            'group_chat_id' => $this->group_chat_id,
            'group_invite_link' => $this->group_invite_link,
            'register_url' => $this->register_url,
            'webhook_secret' => substr($this->webhook_secret, 0, 20) . '...', // Não expor secret completo
            'status' => $this->status,
            'debug_mode' => $this->debug_mode,
            'webhook_configured' => $this->hasWebhookConfigured(),
            'webhook_tested' => $this->hasWebhookTested(),
            'webhook_set_at' => $this->webhook_set_at,
            'webhook_tested_at' => $this->webhook_tested_at,
            'flows' => BotFlowResource::collection($this->flows),
            'created_by' => $this->createdBy?->name,
            'updated_by' => $this->updatedBy?->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
