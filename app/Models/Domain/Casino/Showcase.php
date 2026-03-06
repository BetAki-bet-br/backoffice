<?php

namespace App\Models\Domain\Casino;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ActiveStatus;

class Showcase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'status', 'type', 'position', 'filters',
        'created_by', 'updated_by'
    ];

    protected $casts = [
        'filters' => 'array',
        'status' => ActiveStatus::class,
    ];

    public function slots()
    {
        return $this->belongsToMany(Slot::class, 'showcase_slot')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('showcase_slot.position');
    }
}