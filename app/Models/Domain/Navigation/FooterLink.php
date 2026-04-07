<?php

namespace App\Models\Domain\Navigation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FooterLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'footer_id',
        'block',
        'label',
        'url',
        'icon',
        'target',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function footer()
    {
        return $this->belongsTo(Footer::class);
    }
}
