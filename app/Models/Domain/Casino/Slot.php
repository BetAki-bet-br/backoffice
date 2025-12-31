<?php

namespace App\Models\Domain\Casino;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Domain\Casino\GameExtra;

class Slot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title','cover_url','status',
        'provider','provider_game_id',
        'tags','position','created_by','updated_by',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function categories()
    {
        return $this->belongsToMany(\App\Models\Domain\Casino\Category::class, 'category_slot')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('category_slot.position');
    }

    public function topLists()
    {
        return $this->belongsToMany(\App\Models\Domain\Casino\TopList::class, 'top_list_slot')
            ->withPivot('position')
            ->withTimestamps();
    }

    public function extra()
    {
        return $this->hasOne(GameExtra::class, 'external_id', 'provider_game_id');
    }
}
