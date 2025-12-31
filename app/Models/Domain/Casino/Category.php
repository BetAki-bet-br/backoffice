<?php

namespace App\Models\Domain\Casino;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name','slug','status','position','meta','created_by','updated_by',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function slots()
    {
        return $this->belongsToMany(Slot::class, 'category_slot')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('category_slot.position')
            ->orderByDesc('category_slot.id');
    }
}