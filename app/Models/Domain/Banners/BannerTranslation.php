<?php

namespace App\Models\Domain\Banners;

use Illuminate\Database\Eloquent\Model;

class BannerTranslation extends Model
{
    protected $fillable = ['banner_id','locale','title','alt_text','media'];
    protected $casts = ['media' => 'array'];

    public function banner()
    {
        return $this->belongsTo(Banner::class);
    }
}