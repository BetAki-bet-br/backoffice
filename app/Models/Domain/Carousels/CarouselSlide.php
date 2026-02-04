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
    ];

    /**
     * Get the carousel that owns the slide.
     */
    public function carousel()
    {
        return $this->belongsTo(Carousel::class);
    }
}
