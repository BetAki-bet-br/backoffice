<?php

namespace App\Console\Commands;

use App\Models\Domain\Banners\Banner;
use App\Models\Domain\Casino\Category;
use App\Models\Domain\Casino\Slot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateImagesToS3 extends Command
{
    protected $signature = 'migrate:images-to-s3 {--dry-run : Don\'t actually upload, just show what would be done}';

    protected $description = 'Migrate existing images to S3 storage';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Starting image migration to S3...');
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will actually be uploaded');
        }

        // Migrate Banner images
        $this->migrateBanners($dryRun);

        // Migrate Slot images
        $this->migrateSlots($dryRun);

        // Migrate Category images
        $this->migrateCategories($dryRun);

        $this->info('Migration completed!');
    }

    private function migrateBanners(bool $dryRun): void
    {
        $this->info('');
        $this->info('Migrating Banner images...');

        $banners = Banner::whereNotNull('cover_url')
            ->where('cover_path', null)
            ->get();

        $count = 0;
        foreach ($banners as $banner) {
            if ($this->downloadAndUploadImage($banner->cover_url, 'banners')) {
                if (! $dryRun) {
                    $banner->update([
                        'cover_path' => $this->getPathFromUrl($banner->cover_url),
                    ]);
                }
                $count++;
                $this->line("✓ Migrated Banner #{$banner->id}");
            } else {
                $this->warn("✗ Failed to migrate Banner #{$banner->id}");
            }
        }

        $this->info("Migrated {$count} banner images");
    }

    private function migrateSlots(bool $dryRun): void
    {
        $this->info('');
        $this->info('Migrating Slot images...');

        $slots = Slot::whereNotNull('cover_url')
            ->where('cover_path', null)
            ->get();

        $count = 0;
        foreach ($slots as $slot) {
            if ($this->downloadAndUploadImage($slot->cover_url, 'slots')) {
                if (! $dryRun) {
                    $slot->update([
                        'cover_path' => $this->getPathFromUrl($slot->cover_url),
                    ]);
                }
                $count++;
                $this->line("✓ Migrated Slot #{$slot->id}");
            } else {
                $this->warn("✗ Failed to migrate Slot #{$slot->id}");
            }
        }

        $this->info("Migrated {$count} slot images");
    }

    private function migrateCategories(bool $dryRun): void
    {
        $this->info('');
        $this->info('Migrating Category images...');

        $categories = Category::get();

        $count = 0;
        foreach ($categories as $category) {
            if (! empty($category->meta['cover_url'])) {
                if ($this->downloadAndUploadImage($category->meta['cover_url'], 'categories')) {
                    if (! $dryRun) {
                        $meta = $category->meta ?? [];
                        $meta['cover_path'] = $this->getPathFromUrl($category->meta['cover_url']);
                        $category->update(['meta' => $meta]);
                    }
                    $count++;
                    $this->line("✓ Migrated Category #{$category->id}");
                } else {
                    $this->warn("✗ Failed to migrate Category #{$category->id}");
                }
            }
        }

        $this->info("Migrated {$count} category images");
    }

    private function downloadAndUploadImage(string $imageUrl, string $folder): bool
    {
        try {
            // Download image from URL
            $imageContent = @file_get_contents($imageUrl);
            if ($imageContent === false) {
                return false;
            }

            // Generate filename
            $extension = pathinfo($imageUrl, PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg';
            }
            $filename = Str::random(32).'.'.$extension;

            // Upload to S3
            $path = "{$folder}/".now()->format('Y/m/d')."/{$filename}";
            Storage::disk('s3')->put($path, $imageContent, 'public');

            return true;
        } catch (\Exception $e) {
            $this->error('Error uploading image: '.$e->getMessage());

            return false;
        }
    }

    private function getPathFromUrl(string $url): string
    {
        $baseUrl = config('filesystems.disks.s3.url');
        if (str_starts_with($url, $baseUrl)) {
            return str_replace($baseUrl.'/', '', $url);
        }

        return $url;
    }
}
