<?php

namespace App\Models\Domain\Casino;

use Illuminate\Database\Eloquent\Model;
use App\Enums\ActiveStatus;

class Provider extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'game_count',
        'status',
        'verticals',
        'position',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'game_count' => 'integer',
        'verticals' => 'array',
        'status' => ActiveStatus::class,
    ];
}
