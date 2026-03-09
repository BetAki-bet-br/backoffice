<?php

namespace Tests\Feature;

use App\Jobs\SendPlayerEarningsEmailJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendAnnualEarningsReportsCommandTest extends TestCase
{
    private string $earningsPath;

    private string $playerInfoPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->earningsPath = base_path('tests/fixtures/earnings_sample.xlsx');
        $this->playerInfoPath = base_path('tests/fixtures/player_info_sample.xlsx');
    }

    public function test_dry_run_does_not_dispatch_jobs(): void
    {
        Queue::fake();

        $this->artisan('app:send-annual-earnings-reports', [
            'file' => $this->earningsPath,
            'player-info' => $this->playerInfoPath,
            '--dry-run' => true,
        ])->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_dispatches_jobs_only_for_players_with_emails(): void
    {
        Queue::fake();

        $this->artisan('app:send-annual-earnings-reports', [
            'file' => $this->earningsPath,
            'player-info' => $this->playerInfoPath,
        ])->assertSuccessful();

        // Only 2 of 3 players have emails in the player info fixture
        Queue::assertPushed(SendPlayerEarningsEmailJob::class, 2);
    }

    public function test_respects_limit_option(): void
    {
        Queue::fake();

        $this->artisan('app:send-annual-earnings-reports', [
            'file' => $this->earningsPath,
            'player-info' => $this->playerInfoPath,
            '--limit' => 1,
        ])->assertSuccessful();

        Queue::assertPushed(SendPlayerEarningsEmailJob::class, 1);
    }

    public function test_fails_with_nonexistent_earnings_file(): void
    {
        $this->artisan('app:send-annual-earnings-reports', [
            'file' => '/tmp/nonexistent.xlsx',
            'player-info' => $this->playerInfoPath,
        ])->assertFailed();
    }

    public function test_fails_with_nonexistent_player_info_file(): void
    {
        $this->artisan('app:send-annual-earnings-reports', [
            'file' => $this->earningsPath,
            'player-info' => '/tmp/nonexistent.xlsx',
        ])->assertFailed();
    }

    public function test_dispatched_job_contains_correct_player_data_and_email(): void
    {
        Queue::fake();

        $this->artisan('app:send-annual-earnings-reports', [
            'file' => $this->earningsPath,
            'player-info' => $this->playerInfoPath,
        ])->assertSuccessful();

        Queue::assertPushed(SendPlayerEarningsEmailJob::class, function ($job) {
            return $job->playerData['player_id'] === '10310001'
                && $job->playerData['username'] === '00000427063'
                && $job->playerData['email'] === 'joao@example.com'
                && abs($job->playerData['bets'] - 183.70) < 0.01
                && abs($job->playerData['net_income'] - 100.03) < 0.01
                && $job->playerData['currency'] === 'BRL'
                && $job->year === 2025;
        });
    }
}
