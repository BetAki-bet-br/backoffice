<?php

namespace Tests\Feature;

use App\Models\Domain\Casino\Category;
use App\Models\Domain\Casino\Slot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_by_vertical_slots()
    {
        $category = Category::factory()->create(['verticals' => ['slots']]);
        $liveCategory = Category::factory()->create(['verticals' => ['live']]);

        $response = $this->getJson('/api/v1/categories?vertical=slots');

        $response->assertStatus(200);
        $data = $response->json();

        $ids = array_column($data, 'id');
        $this->assertContains($category->id, $ids);
        $this->assertNotContains($liveCategory->id, $ids);
    }

    public function test_index_filters_by_vertical_live()
    {
        $slotsCategory = Category::factory()->create(['verticals' => ['slots']]);
        $liveCategory = Category::factory()->create(['verticals' => ['live']]);

        $response = $this->getJson('/api/v1/categories?vertical=live');

        $response->assertStatus(200);
        $data = $response->json();

        $ids = array_column($data, 'id');
        $this->assertContains($liveCategory->id, $ids);
        $this->assertNotContains($slotsCategory->id, $ids);
    }

    public function test_index_filters_by_type()
    {
        $gameListCategory = Category::factory()->create(['type' => 'game-list']);
        $recentCategory = Category::factory()->create(['type' => 'recent-games']);

        $response = $this->getJson('/api/v1/categories?type=game-list');

        $response->assertStatus(200);
        $data = $response->json();

        $ids = array_column($data, 'id');
        $this->assertContains($gameListCategory->id, $ids);
        $this->assertNotContains($recentCategory->id, $ids);
    }

    public function test_show_includes_slots_with_pagination()
    {
        $category = Category::factory()->create();
        $slots = Slot::factory()->count(15)->create();

        $category->slots()->attach(
            $slots->pluck('id')->take(10)->toArray(),
            ['position' => 0]
        );

        $response = $this->getJson("/api/v1/categories/{$category->id}?slots_limit=5&slots_page=1");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals($category->id, $data['id']);
        $this->assertIsArray($data['slots']);
        $this->assertLessThanOrEqual(5, count($data['slots']));
    }

    public function test_show_returns_slots_count()
    {
        $category = Category::factory()->create();
        $slots = Slot::factory()->count(5)->create();

        $category->slots()->attach(
            $slots->pluck('id')->toArray(),
            ['position' => 0]
        );

        $response = $this->getJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals(5, $data['slots_count']);
    }

    public function test_store_validates_type()
    {
        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Test Category',
            'type' => 'invalid-type',
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('type');
    }

    public function test_store_accepts_valid_type()
    {
        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Test Category',
            'type' => 'game-list',
            'verticals' => ['slots'],
            'status' => 'active',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'type' => 'game-list',
        ]);
    }

    public function test_store_defaults_type_to_game_list()
    {
        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Test Category',
            'status' => 'active',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'type' => 'game-list',
        ]);
    }

    public function test_category_has_type_constants()
    {
        $this->assertEquals('game-list', Category::TYPE_GAME_LIST);
        $this->assertEquals('recent-games', Category::TYPE_RECENT_GAMES);
        $this->assertEquals('mais-premiados', Category::TYPE_MAIS_PREMIADOS);
        $this->assertEquals('winners-list', Category::TYPE_WINNERS_LIST);
        $this->assertEquals('top-10-list', Category::TYPE_TOP_10_LIST);
        $this->assertEquals('providers-carousel', Category::TYPE_PROVIDERS_CAROUSEL);
    }
}
