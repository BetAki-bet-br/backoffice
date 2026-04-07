<?php

namespace App\Models\Domain\Casino;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'cover_url', 'status',
        'provider', 'provider_game_id',
        'tags', 'position', 'created_by', 'updated_by',
        'rtp', 'volatility', 'min_bet', 'max_bet',
    ];

    protected $casts = [
        'tags' => 'array',
        'rtp' => 'float',
        'min_bet' => 'decimal:2',
        'max_bet' => 'decimal:2',
        'status' => ActiveStatus::class,
    ];

    // Slot Volatility Constants
    const VOLATILITY_LOW = 'low';

    const VOLATILITY_MEDIUM = 'medium';

    const VOLATILITY_HIGH = 'high';

    const VOLATILITY_TYPES = ['low', 'medium', 'high'];

    const VOLATILITY_LABELS = [
        'low' => 'Baixa',
        'medium' => 'Média',
        'high' => 'Alta',
    ];

    public function categories()
    {
        return $this->belongsToMany(\App\Models\Domain\Casino\Category::class, 'category_slot')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('category_slot.position');
    }

    public function topLists()
    {
        return $this->belongsToMany(\App\Models\Domain\Casino\TopList::class, 'top_list_slot')
            ->withPivot('position')
            ->withTimestamps();
    }

    public function extra()
    {
        return $this->hasOne(GameExtra::class, 'external_id', 'external_id');
    }
}
