<?php

namespace App\Models\Domain\Casino;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name','slug','cover_url','verticals','type','status','position','meta','created_by','updated_by',
    ];

    protected $casts = [
        'meta' => 'array',
        'verticals' => 'array',
    ];

    protected $attributes = [
        'verticals' => '["slots"]',
        'type' => 'game-list',
    ];

    // Category Types Constants
    const TYPE_GAME_LIST = 'game-list';
    const TYPE_RECENT_GAMES = 'recent-games';
    const TYPE_MAIS_PREMIADOS = 'mais-premiados';
    const TYPE_WINNERS_LIST = 'winners-list';
    const TYPE_TOP_10_LIST = 'top-10-list';
    const TYPE_PROVIDERS_CAROUSEL = 'providers-carousel';

    const TYPES = [
        'game-list' => 'Lista de Jogos',
        'recent-games' => 'Jogos Recentes',
        'mais-premiados' => 'Mais Premiados',
        'winners-list' => 'Vencedores',
        'top-10-list' => 'Top 10',
        'providers-carousel' => 'Carousel de Produtoras',
    ];

    public function scopeForVertical($query, string $vertical)
    {
        return $query->whereJsonContains('verticals', $vertical);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public function hasVertical(string $vertical): bool
    {
        return is_array($this->verticals) && in_array($vertical, $this->verticals);
    }

    public function slots()
    {
        return $this->belongsToMany(Slot::class, 'category_slot')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('category_slot.position')
            ->orderByDesc('category_slot.id');
    }
}