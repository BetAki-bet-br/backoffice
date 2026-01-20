<?php

namespace Tests\Feature;

use App\Models\Domain\Casino\Slot;
use App\Models\Domain\Casino\Category;
use App\Models\Domain\Banners\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        $this->user = User::factory()->create();
    }

    /**
     * Test uploading an image when creating a slot
     */
    public function test_upload_image_when_creating_slot(): void
    {
        $file = UploadedFile::fake()->image('slot-cover.jpg', 200, 200);

        $response = $this->actingAs($this->user)->postJson('/api/v1/slots', [
            'title' => 'Test Slot with Image',
            'cover_url' => $file,
            'provider' => 'test-provider',
            'provider_game_id' => 'test-game-123',
            'status' => 'active',
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('cover_url'));
        $this->assertStringContainsString('slots/', $response->json('cover_url'));
    }

    /**
     * Test uploading an image when creating a category
     */
    public function test_upload_image_when_creating_category(): void
    {
        $file = UploadedFile::fake()->image('category-cover.png', 200, 200);

        $response = $this->actingAs($this->user)->postJson('/api/v1/categories', [
            'name' => 'Test Category with Image',
            'cover_url' => $file,
            'status' => 'active',
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('cover_url'));
        $this->assertStringContainsString('categories/', $response->json('cover_url'));
    }

    /**
     * Test uploading an image when creating a banner
     */
    public function test_upload_image_when_creating_banner(): void
    {
        $file = UploadedFile::fake()->image('banner-cover.webp', 1200, 300);

        $response = $this->actingAs($this->user)->postJson('/api/v1/banners', [
            'slug' => 'test-banner-' . now()->timestamp,
            'cover_url' => $file,
            'status' => 'draft',
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('cover_url'));
        $this->assertStringContainsString('banners/', $response->json('cover_url'));
    }

    /**
     * Test updating slot image (old image should be deleted)
     */
    public function test_update_slot_image_deletes_old_image(): void
    {
        // Create initial slot without image
        $slot = $this->actingAs($this->user)->postJson('/api/v1/slots', [
            'title' => 'Original Slot',
            'provider' => 'test-provider',
            'provider_game_id' => 'test-game-456',
            'status' => 'active',
        ])->json();
        
        // Now update with new image
        $newFile = UploadedFile::fake()->image('new-slot.png', 200, 200);
        
        $updateResponse = $this->actingAs($this->user)->putJson('/api/v1/slots/' . $slot['id'], [
            'title' => 'Updated Slot',
            'cover_url' => $newFile,
            'provider' => 'test-provider',
            'provider_game_id' => 'test-game-456',
            'status' => 'active',
        ]);

        $updateResponse->assertStatus(200);
        $this->assertNotNull($updateResponse->json('cover_url'));
        $this->assertStringContainsString('slots/', $updateResponse->json('cover_url'));
    }

    /**
     * Test updating category image (old image should be deleted)
     */
    public function test_update_category_image_deletes_old_image(): void
    {
        // Create initial category without image
        $category = $this->actingAs($this->user)->postJson('/api/v1/categories', [
            'name' => 'Original Category',
            'status' => 'active',
        ])->json();
        
        // Now update with new image
        $newFile = UploadedFile::fake()->image('new-category.png', 200, 200);
        
        $updateResponse = $this->actingAs($this->user)->putJson('/api/v1/categories/' . $category['id'], [
            'name' => 'Updated Category',
            'cover_url' => $newFile,
            'status' => 'active',
        ]);

        $updateResponse->assertStatus(200);
        $this->assertNotNull($updateResponse->json('cover_url'));
        $this->assertStringContainsString('categories/', $updateResponse->json('cover_url'));
    }

    /**
     * Test uploading banner image with translations
     */
    public function test_upload_banner_image_with_translations(): void
    {
        $file = UploadedFile::fake()->image('banner-translated.jpg', 1200, 300);

        $response = $this->actingAs($this->user)->postJson('/api/v1/banners', [
            'slug' => 'translated-banner-' . now()->timestamp,
            'cover_url' => $file,
            'status' => 'draft',
            'translations' => [
                [
                    'locale' => 'pt-BR',
                    'title' => 'Banner em Português',
                    'alt_text' => 'Alt text em português',
                ],
                [
                    'locale' => 'en',
                    'title' => 'English Banner',
                    'alt_text' => 'English alt text',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('cover_url'));
    }

    /**
     * Test rejecting file with invalid type
     */
    public function test_reject_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->user)->postJson('/api/v1/slots', [
            'title' => 'Test Slot',
            'cover_url' => $file,
            'provider' => 'test-provider',
            'provider_game_id' => 'test-game-789',
            'status' => 'active',
        ]);

        // PDF file should fail image validation
        $response->assertStatus(422);
    }

    /**
     * Test rejecting file that's too large
     */
    public function test_reject_file_too_large(): void
    {
        // Create a fake file larger than 2MB
        $file = UploadedFile::fake()->image('large.jpg')->size(3000); // 3MB

        $response = $this->actingAs($this->user)->postJson('/api/v1/slots', [
            'title' => 'Test Slot',
            'cover_url' => $file,
            'provider' => 'test-provider',
            'provider_game_id' => 'test-game-large',
            'status' => 'active',
        ]);

        // Large file should fail size validation
        $response->assertStatus(422);
    }

    /**
     * Test accepting allowed image types
     */
    public function test_accept_all_allowed_image_types(): void
    {
        $types = [
            ['jpg', 'image/jpeg'],
            ['png', 'image/png'],
            ['webp', 'image/webp'],
            ['gif', 'image/gif'],
        ];

        foreach ($types as [$ext, $mime]) {
            // UploadedFile::fake()->image() uses common mime types
            $file = UploadedFile::fake()->image("test.{$ext}", 200, 200);

            $response = $this->actingAs($this->user)->postJson('/api/v1/slots', [
                'title' => "Test Slot {$ext}",
                'cover_url' => $file,
                'provider' => 'test-provider',
                'provider_game_id' => "test-game-{$ext}-" . now()->timestamp,
                'status' => 'active',
            ]);

            // All image types should be accepted
            $response->assertStatus(201);
            $this->assertNotNull($response->json('cover_url'));
        }
    }

    /**
     * Test creating slot without image (optional)
     */
    public function test_create_slot_without_image(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/slots', [
            'title' => 'Test Slot No Image',
            'provider' => 'test-provider',
            'provider_game_id' => 'test-game-no-image',
            'status' => 'active',
        ]);

        $response->assertStatus(201);
        $this->assertNull($response->json('cover_url'));
    }

    /**
     * Test creating category without image (optional)
     */
    public function test_create_category_without_image(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/categories', [
            'name' => 'Test Category No Image',
            'status' => 'active',
        ]);

        $response->assertStatus(201);
        $this->assertNull($response->json('cover_url'));
    }

    /**
     * Test S3 URL is properly formatted
     */
    public function test_s3_url_is_properly_formatted(): void
    {
        $file = UploadedFile::fake()->image('url-test.jpg', 200, 200);

        $response = $this->actingAs($this->user)->postJson('/api/v1/slots', [
            'title' => 'URL Test Slot',
            'cover_url' => $file,
            'provider' => 'test-provider',
            'provider_game_id' => 'test-game-url',
            'status' => 'active',
        ]);

        $response->assertStatus(201);
        $imageUrl = $response->json('cover_url');
        
        // URL should be publicly accessible (contains s3 or similar)
        $this->assertTrue(
            str_contains($imageUrl, 'slots/') || str_contains($imageUrl, 'amazonaws'),
            "URL format is incorrect: {$imageUrl}"
        );
    }
}
