<?php

namespace Tests\Feature;

use App\Services\ProductIncomeService;
use Tests\TestCase;

class ProductIncomeServiceTest extends TestCase
{
    private ProductIncomeService $service;

    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductIncomeService;
        $this->fixturePath = base_path('tests/fixtures/income_by_product_sample.xlsx');
    }

    public function test_find_by_player_id_returns_single_entry(): void
    {
        $entries = $this->service->findByPlayerId($this->fixturePath, '10310001');

        $this->assertCount(1, $entries);
        $this->assertEquals('Casino', $entries[0]['product_type']);
        $this->assertEquals('10310001', $entries[0]['player_id']);
        $this->assertEquals('00000427063', $entries[0]['username']);
        $this->assertEqualsWithDelta(100.03, $entries[0]['income'], 0.01);
        $this->assertEqualsWithDelta(0.33, $entries[0]['balance_end'], 0.01);
    }

    public function test_find_by_player_id_returns_multiple_entries(): void
    {
        $entries = $this->service->findByPlayerId($this->fixturePath, '10310002');

        $this->assertCount(2, $entries);

        $types = array_column($entries, 'product_type');
        $this->assertContains('Casino', $types);
        $this->assertContains('Sportsbook', $types);
    }

    public function test_find_by_player_id_returns_empty_for_missing_player(): void
    {
        $entries = $this->service->findByPlayerId($this->fixturePath, '99999999');

        $this->assertCount(0, $entries);
    }

    public function test_find_by_player_ids_groups_by_player(): void
    {
        $results = $this->service->findByPlayerIds($this->fixturePath, ['10310001', '10310002']);

        $this->assertArrayHasKey('10310001', $results);
        $this->assertArrayHasKey('10310002', $results);
        $this->assertCount(1, $results['10310001']);
        $this->assertCount(2, $results['10310002']);
    }

    public function test_find_by_player_ids_omits_missing_players(): void
    {
        $results = $this->service->findByPlayerIds($this->fixturePath, ['10310001', '99999999']);

        $this->assertArrayHasKey('10310001', $results);
        $this->assertArrayNotHasKey('99999999', $results);
    }

    public function test_parse_br_number_handles_comma_decimal(): void
    {
        $this->assertEqualsWithDelta(100.03, $this->service->parseBrNumber('100,03'), 0.01);
        $this->assertEqualsWithDelta(3785.95, $this->service->parseBrNumber('3785,95'), 0.01);
        $this->assertEqualsWithDelta(1086.50, $this->service->parseBrNumber('1.086,50'), 0.01);
    }

    public function test_parse_br_number_handles_numeric_values(): void
    {
        $this->assertEqualsWithDelta(100.03, $this->service->parseBrNumber(100.03), 0.01);
        $this->assertEqualsWithDelta(0.0, $this->service->parseBrNumber(''), 0.01);
        $this->assertEqualsWithDelta(0.0, $this->service->parseBrNumber('-'), 0.01);
    }
}
