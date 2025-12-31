<?php

namespace App\Models\Domain\Navigation;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Footer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'key',
        'status',
        'country',
        'brand',
        'publish_at',
        'published_at',
        'created_by',
        'updated_by',
        'published_by',
    ];

    protected $casts = [
        'publish_at'   => 'datetime',
        'published_at' => 'datetime',
    ];

    // Relações
    public function links()
    {
        return $this->hasMany(FooterLink::class)->orderBy('position');
    }

    public function translations()
    {
        return $this->hasMany(FooterTranslation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    // Helpers de status
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}