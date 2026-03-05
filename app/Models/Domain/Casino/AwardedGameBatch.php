<?php

namespace App\Models\Domain\Casino;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\BatchStatus;

class AwardedGameBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title','status','period_start','period_end','criteria','top_n','vertical',
        'published_at','published_by','created_by','updated_by',
    ];

    protected $casts = [
        'criteria'     => 'array',
        'period_start' => 'datetime',
        'period_end'   => 'datetime',
        'published_at' => 'datetime',
        'status'       => BatchStatus::class,
    ];

    public function results()
    {
        return $this->hasMany(AwardedGame::class, 'batch_id')
            ->orderBy('position');
    }

    public function scopeReadyToPublish($q)
    {
        return $q->where('status', BatchStatus::Review)->whereHas('results');
    }
}