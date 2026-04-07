<?php

namespace App\Models\Domain\Casino;

use Illuminate\Database\Eloquent\Model;

class GameExtra extends Model
{
    protected $table = 'game_extras';

    protected $fillable = [
        'external_id',
        'rtp',
        'volatility',
        'min_bet',
        'source',
    ];

    protected $casts = [
        'rtp' => 'decimal:2',
        'min_bet' => 'decimal:4',
    ];
}
