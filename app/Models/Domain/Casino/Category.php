<?php

namespace App\Models\Domain\Casino;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name','slug','verticals','type','status','position','meta','created_by','updated_by',
    ];

    protected $casts = [
        'meta' => 'array',
        'verticals' => 'array',
    ];

    protected $attributes = [
        'verticals' => '["slots"]',
    ];

    public function scopeForVertical($query, string $vertical)
    {
        return $query->whereJsonContains('verticals', $vertical);
    }

    public function hasVertical(string $vertical): bool
    {
        return is_array($this->verticals) && in_array($vertical, $this->verticals);
    }

    public function slots()
    {
        return $this->belongsToMany(Slot::class, 'category_slot')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('category_slot.position')
            ->orderByDesc('category_slot.id');
    }
}