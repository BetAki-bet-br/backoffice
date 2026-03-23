<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EarningsReportLog extends Model
{
    protected $fillable = [
        'player_id',
        'cpf',
        'email',
        'username',
        'year',
        'status',
        'zeroed',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'zeroed' => 'boolean',
        'sent_at' => 'datetime',
    ];
}
