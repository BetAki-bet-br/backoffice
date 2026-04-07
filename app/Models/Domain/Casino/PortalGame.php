<?php

namespace App\Models\Domain\Casino;

use Illuminate\Database\Eloquent\Model;

class PortalGame extends Model
{
    protected $table = 'portal_games';

    protected $fillable = [
        'portal_id',
        'external_id',
        'name',
        'product_name',
        'supplier_name',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
