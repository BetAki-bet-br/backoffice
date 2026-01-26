<?php

declare(strict_types=1);

namespace App\Models\Domain\Telegram;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotMessage extends Model
{
    protected $fillable = [
        'bot_id',
        'bot_user_id',
        'telegram_message_id',
        'direction',
        'type',
        'content',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    const DIRECTION_INCOMING = 'incoming';
    const DIRECTION_OUTGOING = 'outgoing';

    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';

    // ===== Relacionamentos =====

    /**
     * Bot ao qual esta mensagem pertence
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'bot_id');
    }

    /**
     * Usuário que enviou/recebeu a mensagem
     */
    public function botUser(): BelongsTo
    {
        return $this->belongsTo(BotUser::class, 'bot_user_id');
    }

    // ===== Scopes =====

    /**
     * Mensagens de um bot
     */
    public function scopeForBot($query, int $botId)
    {
        return $query->where('bot_id', $botId);
    }

    /**
     * Mensagens de um usuário
     */
    public function scopeFromUser($query, int $botUserId)
    {
        return $query->where('bot_user_id', $botUserId);
    }

    /**
     * Mensagens recebidas
     */
    public function scopeIncoming($query)
    {
        return $query->where('direction', self::DIRECTION_INCOMING);
    }

    /**
     * Mensagens enviadas
     */
    public function scopeOutgoing($query)
    {
        return $query->where('direction', self::DIRECTION_OUTGOING);
    }

    /**
     * Mensagens com sucesso
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Mensagens falhadas
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // ===== Métodos auxiliares =====

    /**
     * Marca como entregue
     */
    public function markAsDelivered(): self
    {
        $this->update(['status' => self::STATUS_DELIVERED]);
        return $this;
    }

    /**
     * Marca como falhada
     */
    public function markAsFailed(string $error = ''): self
    {
        $metadata = $this->metadata ?? [];
        $metadata['error'] = $error;

        $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => $metadata,
        ]);

        return $this;
    }

    /**
     * Verifica se é entrante
     */
    public function isIncoming(): bool
    {
        return $this->direction === self::DIRECTION_INCOMING;
    }

    /**
     * Verifica se é sainte
     */
    public function isOutgoing(): bool
    {
        return $this->direction === self::DIRECTION_OUTGOING;
    }
}
