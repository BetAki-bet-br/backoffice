<?php

namespace App\Models\Domain\Casino;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TopWinnerBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'status', 'period_start', 'period_end', 'criteria', 'top_n', 'vertical',
        'published_at', 'published_by', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'criteria' => 'array',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'published_at' => 'datetime',
        'status' => BatchStatus::class,
    ];

    public function winners()
    {
        return $this->hasMany(TopWinner::class, 'batch_id')->orderBy('rank');
    }

    public function scopeReadyToPublish($q)
    {
        return $q->where('status', BatchStatus::Review)->whereHas('winners');
    }
}
