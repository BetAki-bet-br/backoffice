<?php

namespace App\Models\Domain\Casino;

use Illuminate\Database\Eloquent\Model;

class TopWinner extends Model
{
    protected $fillable = [
        'batch_id','player_ref','display_name','country',
        'rank','position',
        'wins_count','prize_sum','max_prize','avg_prize','meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(TopWinnerBatch::class, 'batch_id');
    }
}