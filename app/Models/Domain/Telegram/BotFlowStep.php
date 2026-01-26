<?php

declare(strict_types=1);

namespace App\Models\Domain\Telegram;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BotFlowStep extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'flow_id',
        'type',
        'order',
        'data',
        'metadata',
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'order' => 'integer',
    ];

    const TYPE_MESSAGE = 'message';
    const TYPE_BUTTONS = 'buttons';
    const TYPE_INPUT = 'input';
    const TYPE_VALIDATION = 'validation';
    const TYPE_CONDITION = 'condition';
    const TYPE_ACTION = 'action';

    const TYPES = [
        self::TYPE_MESSAGE,
        self::TYPE_BUTTONS,
        self::TYPE_INPUT,
        self::TYPE_VALIDATION,
        self::TYPE_CONDITION,
        self::TYPE_ACTION,
    ];

    // ===== Relacionamentos =====

    /**
     * Fluxo ao qual pertence este step
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(BotFlow::class, 'flow_id');
    }

    // ===== Scopes =====

    /**
     * Steps de um fluxo específico ordenados
     */
    public function scopeForFlow($query, int $flowId)
    {
        return $query->where('flow_id', $flowId)->orderBy('order');
    }

    /**
     * Steps de um tipo específico
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ===== Métodos auxiliares =====

    /**
     * Verifica se é um step de mensagem
     */
    public function isMessage(): bool
    {
        return $this->type === self::TYPE_MESSAGE;
    }

    /**
     * Verifica se é um step de botões
     */
    public function isButtons(): bool
    {
        return $this->type === self::TYPE_BUTTONS;
    }

    /**
     * Verifica se é um step de entrada
     */
    public function isInput(): bool
    {
        return $this->type === self::TYPE_INPUT;
    }

    /**
     * Verifica se é um step de validação
     */
    public function isValidation(): bool
    {
        return $this->type === self::TYPE_VALIDATION;
    }

    /**
     * Verifica se é um step de condição
     */
    public function isCondition(): bool
    {
        return $this->type === self::TYPE_CONDITION;
    }

    /**
     * Verifica se é um step de ação
     */
    public function isAction(): bool
    {
        return $this->type === self::TYPE_ACTION;
    }

    /**
     * Obtém o próximo step
     */
    public function getNextStep(): ?self
    {
        return static::forFlow($this->flow_id)
            ->where('order', '>', $this->order)
            ->first();
    }

    /**
     * Obtém o step anterior
     */
    public function getPreviousStep(): ?self
    {
        return static::forFlow($this->flow_id)
            ->where('order', '<', $this->order)
            ->orderByDesc('order')
            ->first();
    }
}
