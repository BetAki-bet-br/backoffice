<?php

namespace App\Models\Domain\Casino;

use Illuminate\Database\Eloquent\Model;

class AwardedGame extends Model
{
    protected $fillable = [
        'batch_id','slot_id','position',
        'wins_count','prize_sum','max_prize','avg_prize','meta',
        'prize_sum_initial', 'prize_sum_final', 'increment_interval_minutes',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(AwardedGameBatch::class, 'batch_id');
    }

    public function slot()
    {
        return $this->belongsTo(\App\Models\Domain\Casino\Slot::class);
    }
}