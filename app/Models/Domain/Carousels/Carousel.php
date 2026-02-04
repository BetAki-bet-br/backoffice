<?php

namespace App\Models\Domain\Carousels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carousel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get the slides for the carousel.
     */
    public function slides()
    {
        return $this->hasMany(CarouselSlide::class)->orderBy('order');
    }
}
