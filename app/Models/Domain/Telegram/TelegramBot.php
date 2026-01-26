<?php

declare(strict_types=1);

namespace App\Models\Domain\Telegram;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TelegramBot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'bot_token',
        'username',
        'description',
        'api_url',
        'api_key',
        'portal_id',
        'group_chat_id',
        'group_invite_link',
        'register_url',
        'webhook_secret',
        'status',
        'debug_mode',
        'webhook_set_at',
        'webhook_tested_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'portal_id' => 'integer',
        'debug_mode' => 'boolean',
        'webhook_set_at' => 'datetime',
        'webhook_tested_at' => 'datetime',
    ];

    // ===== Relacionamentos =====

    /**
     * Usuário que criou o bot
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Usuário que atualizou o bot
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Fluxos deste bot
     */
    public function flows(): HasMany
    {
        return $this->hasMany(BotFlow::class, 'bot_id');
    }

    /**
     * Usuários que interagiram com o bot
     */
    public function botUsers(): HasMany
    {
        return $this->hasMany(BotUser::class, 'bot_id');
    }

    /**
     * Mensagens do bot
     */
    public function messages(): HasMany
    {
        return $this->hasMany(BotMessage::class, 'bot_id');
    }

    /**
     * Estatísticas
     */
    public function statistics(): HasMany
    {
        return $this->hasMany(BotStatistic::class, 'bot_id');
    }

    // ===== Scopes =====

    /**
     * Apenas bots ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Apenas bots inativos
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    // ===== Métodos auxiliares =====

    /**
     * Gera um secret único para o webhook
     */
    public static function generateWebhookSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Verifica se webhook foi configurado
     */
    public function hasWebhookConfigured(): bool
    {
        return $this->webhook_set_at !== null;
    }

    /**
     * Verifica se webhook foi testado com sucesso
     */
    public function hasWebhookTested(): bool
    {
        return $this->webhook_tested_at !== null;
    }
}
