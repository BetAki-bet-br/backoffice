<?php

namespace Tests\Feature;

use App\Jobs\SendPlayerEarningsEmailJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendAnnualEarningsReportsCommandTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturePath = base_path('tests/fixtures/earnings_sample.xlsx');
    }

    public function test_dry_run_does_not_dispatch_jobs(): void
    {
        Queue::fake();

        $this->artisan('app:send-annual-earnings-reports', [
            'file' => $this->fixturePath,
            '--dry-run' => true,
        ])->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_dispatches_jobs_for_all_players(): void
    {
        Queue::fake();

        $this->artisan('app:send-annual-earnings-reports', [
            'file' => $this->fixturePath,
        ])->assertSuccessful();

        // 3 data rows in fixture
        Queue::assertPushed(SendPlayerEarningsEmailJob::class, 3);
    }

    public function test_respects_limit_option(): void
    {
        Queue::fake();

        $this->artisan('app:send-annual-earnings-reports', [
            'file' => $this->fixturePath,
            '--limit' => 1,
        ])->assertSuccessful();

        Queue::assertPushed(SendPlayerEarningsEmailJob::class, 1);
    }

    public function test_fails_with_nonexistent_file(): void
    {
        $this->artisan('app:send-annual-earnings-reports', [
            'file' => '/tmp/nonexistent.xlsx',
        ])->assertFailed();
    }

    public function test_dispatched_job_contains_correct_player_data(): void
    {
        Queue::fake();

        $this->artisan('app:send-annual-earnings-reports', [
            'file' => $this->fixturePath,
        ])->assertSuccessful();

        Queue::assertPushed(SendPlayerEarningsEmailJob::class, function ($job) {
            return $job->playerData['player_id'] === '10310001'
                && $job->playerData['username'] === '00000427063'
                && abs($job->playerData['bets'] - 183.70) < 0.01
                && abs($job->playerData['wins'] - 83.67) < 0.01
                && abs($job->playerData['net_income'] - 100.03) < 0.01
                && $job->playerData['currency'] === 'BRL'
                && $job->year === 2025;
        });
    }
}
