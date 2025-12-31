<?php

namespace App\Models\Domain\Navigation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FooterTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'footer_id',
        'locale',
        'legal_title',
        'legal_text',
        'disclaimer',
    ];

    public function footer()
    {
        return $this->belongsTo(Footer::class);
    }
}