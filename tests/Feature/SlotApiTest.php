<?php

namespace Tests\Feature;

use App\Models\Domain\Casino\Slot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_validates_rtp_range()
    {
        $response = $this->postJson('/api/v1/slots', [
            'title' => 'Test Slot',
            'provider' => 'Test Provider',
            'provider_game_id' => 'test-game-1',
            'status' => 'active',
            'rtp' => 150, // Invalid: > 100
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('rtp');
    }

    public function test_store_validates_volatility_enum()
    {
        $response = $this->postJson('/api/v1/slots', [
            'title' => 'Test Slot',
            'provider' => 'Test Provider',
            'provider_game_id' => 'test-game-1',
            'status' => 'active',
            'volatility' => 'ultra-high', // Invalid
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('volatility');
    }

    public function test_store_validates_min_bet_greater_than_zero()
    {
        $response = $this->postJson('/api/v1/slots', [
            'title' => 'Test Slot',
            'provider' => 'Test Provider',
            'provider_game_id' => 'test-game-1',
            'status' => 'active',
            'min_bet' => -1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('min_bet');
    }

    public function test_store_validates_max_bet_gte_min_bet()
    {
        $response = $this->postJson('/api/v1/slots', [
            'title' => 'Test Slot',
            'provider' => 'Test Provider',
            'provider_game_id' => 'test-game-1',
            'status' => 'active',
            'min_bet' => 10.00,
            'max_bet' => 5.00, // Invalid: < min_bet
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('max_bet');
    }

    public function test_store_accepts_valid_betting_data()
    {
        $response = $this->postJson('/api/v1/slots', [
            'title' => 'Test Slot',
            'provider' => 'Test Provider',
            'provider_game_id' => 'test-game-1',
            'status' => 'active',
            'rtp' => 96.50,
            'volatility' => 'medium',
            'min_bet' => 0.01,
            'max_bet' => 100.00,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('slots', [
            'title' => 'Test Slot',
            'rtp' => 96.50,
            'volatility' => 'medium',
            'min_bet' => 0.01,
            'max_bet' => 100.00,
        ]);
    }

    public function test_store_allows_nullable_betting_fields()
    {
        $response = $this->postJson('/api/v1/slots', [
            'title' => 'Test Slot',
            'provider' => 'Test Provider',
            'provider_game_id' => 'test-game-1',
            'status' => 'active',
        ]);

        $response->assertStatus(201);
        $slot = Slot::where('provider_game_id', 'test-game-1')->first();

        $this->assertNull($slot->rtp);
        $this->assertNull($slot->volatility);
        $this->assertNull($slot->min_bet);
        $this->assertNull($slot->max_bet);
    }

    public function test_update_validates_betting_data()
    {
        $slot = Slot::factory()->create();

        $response = $this->putJson("/api/v1/slots/{$slot->id}", [
            'title' => 'Updated Slot',
            'provider' => $slot->provider,
            'provider_game_id' => $slot->provider_game_id,
            'status' => 'active',
            'rtp' => 150, // Invalid
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('rtp');
    }

    public function test_slot_has_volatility_constants()
    {
        $this->assertEquals('low', Slot::VOLATILITY_LOW);
        $this->assertEquals('medium', Slot::VOLATILITY_MEDIUM);
        $this->assertEquals('high', Slot::VOLATILITY_HIGH);
        $this->assertContains('low', Slot::VOLATILITY_TYPES);
        $this->assertContains('medium', Slot::VOLATILITY_TYPES);
        $this->assertContains('high', Slot::VOLATILITY_TYPES);
    }

    public function test_index_returns_slot_betting_data()
    {
        $slot = Slot::factory()->create([
            'rtp' => 95.5,
            'volatility' => 'high',
            'min_bet' => 0.10,
            'max_bet' => 500.00,
        ]);

        $response = $this->getJson('/api/v1/slots');

        $response->assertStatus(200);
        $data = $response->json();

        $slotData = collect($data['data'])->firstWhere('id', $slot->id);
        $this->assertNotNull($slotData);
        $this->assertEquals(95.5, $slotData['rtp']);
        $this->assertEquals('high', $slotData['volatility']);
        $this->assertEquals(0.10, $slotData['min_bet']);
        $this->assertEquals(500.00, $slotData['max_bet']);
    }
}
