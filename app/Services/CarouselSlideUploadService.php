<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CarouselSlideUploadService
{
    /**
     * Upload carousel slide image to S3
     */
    public static function uploadImage(UploadedFile $file): string
    {
        $path = 'carousel-slides/' . now()->format('Y/m/d');
        $filename = Str::random(32) . '.' . $file->getClientOriginalExtension();
        
        Storage::disk('s3')->putFileAs($path, $file, $filename, 'public');
        return Storage::disk('s3')->url("{$path}/{$filename}");
    }
}
