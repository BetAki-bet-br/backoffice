<?php

declare(strict_types=1);

namespace App\Models\Domain\Telegram;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id',
        'telegram_user_id',
        'first_name',
        'username',
        'email',
        'status',
        'first_interaction_at',
        'validated_at',
        'metadata',
    ];

    protected $casts = [
        'telegram_user_id' => 'integer',
        'first_interaction_at' => 'datetime',
        'validated_at' => 'datetime',
        'metadata' => 'array',
    ];

    const STATUS_NEW = 'new';
    const STATUS_VALIDATING = 'validating';
    const STATUS_VALIDATED = 'validated';
    const STATUS_FAILED = 'failed';

    const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_VALIDATING,
        self::STATUS_VALIDATED,
        self::STATUS_FAILED,
    ];

    // ===== Relacionamentos =====

    /**
     * Bot ao qual este usuário pertence
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'bot_id');
    }

    /**
     * Mensagens deste usuário
     */
    public function messages(): HasMany
    {
        return $this->hasMany(BotMessage::class, 'bot_user_id');
    }

    // ===== Scopes =====

    /**
     * Usuários de um bot específico
     */
    public function scopeForBot($query, int $botId)
    {
        return $query->where('bot_id', $botId);
    }

    /**
     * Usuários validados
     */
    public function scopeValidated($query)
    {
        return $query->where('status', self::STATUS_VALIDATED);
    }

    /**
     * Usuários que falharam na validação
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Usuários em processo de validação
     */
    public function scopeValidating($query)
    {
        return $query->where('status', self::STATUS_VALIDATING);
    }

    /**
     * Usuários novos (nunca validaram)
     */
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    /**
     * Usuários com e-mail
     */
    public function scopeWithEmail($query)
    {
        return $query->whereNotNull('email');
    }

    // ===== Métodos auxiliares =====

    /**
     * Marca como validado
     */
    public function markAsValidated(): self
    {
        $this->update([
            'status' => self::STATUS_VALIDATED,
            'validated_at' => now(),
        ]);

        return $this;
    }

    /**
     * Marca como falhado
     */
    public function markAsFailed(string $reason = ''): self
    {
        $metadata = $this->metadata ?? [];
        $metadata['failure_reason'] = $reason;
        $metadata['failed_at'] = now()->toIso8601String();

        $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => $metadata,
        ]);

        return $this;
    }

    /**
     * Marca como validando
     */
    public function markAsValidating(): self
    {
        $this->update([
            'status' => self::STATUS_VALIDATING,
        ]);

        return $this;
    }

    /**
     * Verifica se está validado
     */
    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    /**
     * Verifica se falhou
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Obtém o tempo decorrido desde a primeira interação
     */
    public function getInteractionDuration(): \Carbon\CarbonInterval
    {
        return $this->first_interaction_at->diff(now());
    }
}
