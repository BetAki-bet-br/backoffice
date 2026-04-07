<?php

namespace Tests\Feature;

use App\Services\FileUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    public function test_upload_banner_image()
    {
        $file = UploadedFile::fake()->image('banner.jpg');

        $url = FileUploadService::uploadBannerImage($file);

        $this->assertStringContainsString('banners/', $url);
        $this->assertStringContainsString('.jpg', $url);
        Storage::disk('s3')->assertExists(FileUploadService::getPathFromUrl($url));
    }

    public function test_upload_slot_image()
    {
        $file = UploadedFile::fake()->image('slot.png');

        $url = FileUploadService::uploadSlotImage($file);

        $this->assertStringContainsString('slots/', $url);
        $this->assertStringContainsString('.png', $url);
        Storage::disk('s3')->assertExists(FileUploadService::getPathFromUrl($url));
    }

    public function test_upload_category_image()
    {
        $file = UploadedFile::fake()->image('category.webp');

        $url = FileUploadService::uploadCategoryImage($file);

        $this->assertStringContainsString('categories/', $url);
        $this->assertStringContainsString('.webp', $url);
        Storage::disk('s3')->assertExists(FileUploadService::getPathFromUrl($url));
    }

    public function test_delete_image_by_url()
    {
        $file = UploadedFile::fake()->image('banner.jpg');
        $url = FileUploadService::uploadBannerImage($file);

        $path = FileUploadService::getPathFromUrl($url);
        Storage::disk('s3')->assertExists($path);

        $result = FileUploadService::deleteImageByUrl($url);

        $this->assertTrue($result);
        Storage::disk('s3')->assertMissing($path);
    }

    public function test_delete_image_by_path()
    {
        $file = UploadedFile::fake()->image('banner.jpg');
        $url = FileUploadService::uploadBannerImage($file);
        $path = FileUploadService::getPathFromUrl($url);

        Storage::disk('s3')->assertExists($path);

        $result = FileUploadService::deleteImageByPath($path);

        $this->assertTrue($result);
        Storage::disk('s3')->assertMissing($path);
    }

    public function test_delete_nonexistent_image_returns_true()
    {
        $result = FileUploadService::deleteImageByUrl('https://s3.example.com/nonexistent/image.jpg');

        $this->assertTrue($result);
    }

    public function test_get_path_from_url()
    {
        $baseUrl = config('filesystems.disks.s3.url');
        $path = 'banners/2026/01/19/abcd1234.jpg';
        $url = "{$baseUrl}/{$path}";

        $extractedPath = FileUploadService::getPathFromUrl($url);

        $this->assertEquals($path, $extractedPath);
    }

    public function test_get_path_from_non_s3_url_returns_null()
    {
        $url = 'https://example.com/image.jpg';

        $path = FileUploadService::getPathFromUrl($url);

        $this->assertNull($path);
    }
}
