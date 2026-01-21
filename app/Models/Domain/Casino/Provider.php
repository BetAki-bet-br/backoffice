<?php

namespace App\Models\Domain\Casino;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'game_count',
        'status',
        'verticals',
    ];

    protected $casts = [
        'game_count' => 'integer',
        'verticals' => 'array',
    ];
}
