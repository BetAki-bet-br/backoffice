<?php

declare(strict_types=1);

namespace App\Models\Domain\Telegram;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotStatistic extends Model
{
    protected $fillable = [
        'bot_id',
        'date',
        'messages_sent',
        'messages_received',
        'users_new',
        'validations_success',
        'validations_failed',
    ];

    protected $casts = [
        'date' => 'date',
        'messages_sent' => 'integer',
        'messages_received' => 'integer',
        'users_new' => 'integer',
        'validations_success' => 'integer',
        'validations_failed' => 'integer',
    ];

    // ===== Relacionamentos =====

    /**
     * Bot ao qual pertence esta estatística
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'bot_id');
    }

    // ===== Scopes =====

    /**
     * Estatísticas de um bot específico
     */
    public function scopeForBot($query, int $botId)
    {
        return $query->where('bot_id', $botId);
    }

    /**
     * Estatísticas de um período
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Estatísticas dos últimos N dias
     */
    public function scopeLastDays($query, int $days)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }

    // ===== Métodos auxiliares =====

    /**
     * Total de mensagens
     */
    public function getTotalMessages(): int
    {
        return $this->messages_sent + $this->messages_received;
    }

    /**
     * Taxa de sucesso de validação
     */
    public function getValidationSuccessRate(): float
    {
        $total = $this->validations_success + $this->validations_failed;

        if ($total === 0) {
            return 0;
        }

        return round(($this->validations_success / $total) * 100, 2);
    }

    /**
     * Incrementa contadores
     */
    public function increment(string $field, int $amount = 1): self
    {
        parent::increment($field, $amount);
        return $this;
    }

    /**
     * Obtém ou cria estatística para hoje
     */
    public static function getTodayOrCreate(int $botId): self
    {
        return static::firstOrCreate(
            [
                'bot_id' => $botId,
                'date' => now()->date(),
            ],
            [
                'messages_sent' => 0,
                'messages_received' => 0,
                'users_new' => 0,
                'validations_success' => 0,
                'validations_failed' => 0,
            ]
        );
    }
}
