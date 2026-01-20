<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload banner image to S3
     */
    public static function uploadBannerImage(UploadedFile $file): string
    {
        $path = 'banners/' . now()->format('Y/m/d');
        $filename = Str::random(32) . '.' . $file->getClientOriginalExtension();
        
        Storage::disk('s3')->putFileAs($path, $file, $filename, 'public');
        return Storage::disk('s3')->url("{$path}/{$filename}");
    }

    /**
     * Upload slot image to S3
     */
    public static function uploadSlotImage(UploadedFile $file): string
    {
        $path = 'slots/' . now()->format('Y/m/d');
        $filename = Str::random(32) . '.' . $file->getClientOriginalExtension();
        
        Storage::disk('s3')->putFileAs($path, $file, $filename, 'public');
        return Storage::disk('s3')->url("{$path}/{$filename}");
    }

    /**
     * Upload category image to S3
     */
    public static function uploadCategoryImage(UploadedFile $file): string
    {
        $path = 'categories/' . now()->format('Y/m/d');
        $filename = Str::random(32) . '.' . $file->getClientOriginalExtension();
        
        Storage::disk('s3')->putFileAs($path, $file, $filename, 'public');
        return Storage::disk('s3')->url("{$path}/{$filename}");
    }

    /**
     * Delete image from S3 by URL
     */
    public static function deleteImageByUrl(string $url): bool
    {
        if (empty($url)) {
            return true;
        }

        $baseUrl = config('filesystems.disks.s3.url');
        if (empty($baseUrl) || !str_starts_with($url, $baseUrl)) {
            return true; // URL não é do S3, ignorar
        }

        $path = str_replace($baseUrl . '/', '', $url);
        return Storage::disk('s3')->delete($path);
    }

    /**
     * Delete image from S3 by path
     */
    public static function deleteImageByPath(string $path): bool
    {
        if (empty($path)) {
            return true;
        }

        return Storage::disk('s3')->delete($path);
    }

    /**
     * Get file path from S3 URL
     */
    public static function getPathFromUrl(string $url): ?string
    {
        $baseUrl = config('filesystems.disks.s3.url');
        if (empty($baseUrl) || !str_starts_with($url, $baseUrl)) {
            return null;
        }

        return str_replace($baseUrl . '/', '', $url);
    }
}
