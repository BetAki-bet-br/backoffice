<?php

namespace App\Models\Traits;

use Illuminate\Http\UploadedFile;
use App\Services\FileUploadService;

trait HasS3FileUpload
{
    /**
     * Boot the trait
     */
    protected static function bootHasS3FileUpload()
    {
        static::updating(function ($model) {
            // Delete old image if a new one is provided
            foreach ($model->s3Attributes() as $attribute) {
                if ($model->isDirty($attribute)) {
                    if ($model->getOriginal($attribute)) {
                        FileUploadService::deleteImageByUrl($model->getOriginal($attribute));
                    }
                }
            }
        });

        static::deleting(function ($model) {
            // Delete image when model is deleted
            foreach ($model->s3Attributes() as $attribute) {
                if ($model->$attribute) {
                    FileUploadService::deleteImageByUrl($model->$attribute);
                }
            }
        });
    }

    /**
     * Get attributes that should be handled as S3 uploads
     * Override in model to specify which attributes
     */
    public function s3Attributes(): array
    {
        return [];
    }

    /**
     * Get the S3 upload method for a given attribute
     * Can be overridden in model
     */
    public function getS3UploadMethod(string $attribute): ?callable
    {
        return null;
    }
}
