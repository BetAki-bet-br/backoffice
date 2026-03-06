<?php

namespace App\Models\Domain\Navigation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ActiveStatus;

class Menu extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name','slug','status','position','meta','created_by','updated_by',
    ];

    protected $casts = [
        'meta' => 'array',
        'status' => ActiveStatus::class,
    ];

    public function items()
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')
            ->orderBy('position')->orderBy('id');
    }

    public function allItems()
    {
        return $this->hasMany(MenuItem::class)->orderBy('position')->orderBy('id');
    }
}