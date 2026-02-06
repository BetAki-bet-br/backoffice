<?php

namespace App\Models\Domain\Carousels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarouselSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'carousel_id',
        'href',
        'image_url',
        'alt',
        'duration',
        'order',
        'is_active',
        'publish_at',
        'expire_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'publish_at' => 'datetime',
        'expire_at' => 'datetime',
    ];

    /**
     * Get the carousel that owns the slide.
     */
    public function carousel()
    {
        return $this->belongsTo(Carousel::class);
    }
}
