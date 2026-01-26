<?php

declare(strict_types=1);

namespace App\Models\Domain\Telegram;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BotFlow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bot_id',
        'name',
        'description',
        'version',
        'based_on_flow_id',
        'flow_data',
        'status',
        'is_default',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'flow_data' => 'array',
        'version' => 'integer',
        'is_default' => 'boolean',
    ];

    // ===== Relacionamentos =====

    /**
     * Bot ao qual este fluxo pertence
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'bot_id');
    }

    /**
     * Fluxo base (se for versão)
     */
    public function basedOnFlow(): BelongsTo
    {
        return $this->belongsTo(BotFlow::class, 'based_on_flow_id');
    }

    /**
     * Versões derivadas deste fluxo
     */
    public function derivedFlows(): HasMany
    {
        return $this->hasMany(BotFlow::class, 'based_on_flow_id');
    }

    /**
     * Steps deste fluxo
     */
    public function steps(): HasMany
    {
        return $this->hasMany(BotFlowStep::class, 'flow_id')->orderBy('order');
    }

    /**
     * Usuário que criou o fluxo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Usuário que atualizou o fluxo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    // ===== Scopes =====

    /**
     * Fluxos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Fluxos em rascunho
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Fluxos padrão
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Fluxos de um bot específico
     */
    public function scopeForBot($query, int $botId)
    {
        return $query->where('bot_id', $botId);
    }

    // ===== Métodos auxiliares =====

    /**
     * Obtém a versão anterior deste fluxo
     */
    public function getPreviousVersion(): ?self
    {
        return static::where('bot_id', $this->bot_id)
            ->where('version', '<', $this->version)
            ->orderByDesc('version')
            ->first();
    }

    /**
     * Obtém a próxima versão disponível
     */
    public function getNextVersion(): int
    {
        return static::where('bot_id', $this->bot_id)
            ->max('version') + 1;
    }

    /**
     * Cria uma cópia (nova versão) deste fluxo
     */
    public function createVersion(array $data = []): self
    {
        return static::create([
            'bot_id' => $this->bot_id,
            'name' => $data['name'] ?? $this->name . ' v' . $this->getNextVersion(),
            'description' => $data['description'] ?? $this->description,
            'version' => $this->getNextVersion(),
            'based_on_flow_id' => $this->id,
            'flow_data' => $data['flow_data'] ?? $this->flow_data,
            'status' => 'draft',
            'is_default' => false,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Publica este fluxo (torna ativo)
     */
    public function publish(): self
    {
        // Arquiva versão anterior
        static::where('bot_id', $this->bot_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'active')
            ->update(['status' => 'archived']);

        $this->update(['status' => 'active']);

        return $this;
    }
}
