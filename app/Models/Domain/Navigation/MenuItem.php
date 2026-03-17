<?php

namespace App\Models\Domain\Navigation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'menu_id', 'parent_id', 'depth',
        'title', 'icon',
        'is_external', 'url', 'route_name', 'route_params', 'target',
        'status', 'position',
        'visible_roles', 'visible_permissions',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'route_params' => 'array',
        'visible_roles' => 'array',
        'visible_permissions' => 'array',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MenuItem::class, 'parent_id')
            ->orderBy('position')->orderBy('id');
    }

    public function scopeVisibleTo($query, bool $isPublic): void
    {
        if ($isPublic) {
            $query->where('status',
                ActiveStatus::Active);
        }
    }
}
