<?php

namespace App\Models\Domain\Banners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ContentStatus;

class Banner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vertical',
        'slug','cover_url','status','countries','publish_at','expire_at',
        'link_url','utm_source','utm_medium','utm_campaign','media',
        'created_by','updated_by','published_by'
    ];

    protected $casts = [
        'countries'  => 'array',
        'media'      => 'array',
        'publish_at' => 'datetime',
        'expire_at'  => 'datetime',
        'status'     => ContentStatus::class,
    ];

    public function translations()
    {
        return $this->hasMany(BannerTranslation::class);
    }
}