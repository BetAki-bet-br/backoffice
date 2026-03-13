<?php

namespace Tests\Feature;

use App\Models\Domain\Casino\AwardedGameBatch;
use App\Models\Domain\Casino\TopList;
use App\Models\Domain\Casino\TopWinnerBatch;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LobbyLayoutControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_awarded_section_does_not_show_non_published_batch_by_id()
    {
        $draftBatch = AwardedGameBatch::factory()->create(['status' => 'draft']);

        Setting::factory()->create([
            'key' => 'lobby.layout.slots',
            'value' => [
                'sections' => [
                    ['type' => 'mais-premiados', 'batchId' => $draftBatch->id],
                ],
            ],
        ]);

        $response = $this->getJson('/api/v1/lobbies/casino');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'sections');
    }

    public function test_top_list_section_does_not_show_non_published_list_by_id()
    {
        $draftList = TopList::factory()->create(['status' => 'draft']);

        Setting::factory()->create([
            'key' => 'lobby.layout.slots',
            'value' => [
                'sections' => [
                    ['type' => 'top-10-list', 'topListId' => $draftList->id],
                ],
            ],
        ]);

        $response = $this->getJson('/api/v1/lobbies/casino');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'sections');
    }

    public function test_winners_section_does_not_use_non_published_batch_by_id()
    {
        $draftBatch = TopWinnerBatch::factory()->create(['status' => 'draft', 'title' => 'Draft Winners']);

        Setting::factory()->create([
            'key' => 'lobby.layout.slots',
            'value' => [
                'sections' => [
                    ['type' => 'winners-list', 'batchId' => $draftBatch->id],
                ],
            ],
        ]);

        $response = $this->getJson('/api/v1/lobbies/casino');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'sections');
        $response->assertJsonPath('sections.0.title', 'Vencedores'); // Should fall back to default title
        $response->assertJsonPath('sections.0.metadata.batchId', null); // batchId should be null
    }
}
