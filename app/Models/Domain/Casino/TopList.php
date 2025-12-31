<?php

namespace App\Models\Domain\Casino;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TopList extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title','slug','vertical','type','status','position',
        'valid_from','valid_until','criteria',
        'created_by','updated_by','published_by',
    ];

    protected $casts = [
        'criteria'   => 'array',
        'valid_from' => 'datetime',
        'valid_until'=> 'datetime',
    ];

    public function slots()
    {
        return $this->belongsToMany(Slot::class, 'top_list_slot')
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('top_list_slot.position');
    }

    public function scopeActiveWindow($q)
    {
        return $q->where(function($w){
            $w->whereNull('valid_from')->orWhere('valid_from','<=',now());
        })->where(function($w){
            $w->whereNull('valid_until')->orWhere('valid_until','>=',now());
        });
    }
}