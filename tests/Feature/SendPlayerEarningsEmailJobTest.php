<?php

namespace Tests\Feature;

use App\Jobs\SendPlayerEarningsEmailJob;
use App\Mail\AnnualEarningsReportMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendPlayerEarningsEmailJobTest extends TestCase
{
    private array $playerData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->playerData = [
            'currency' => 'BRL',
            'player_id' => '10310001',
            'bets' => 183.70,
            'username' => '00000427063',
            'bet_count' => 271,
            'wins' => 83.67,
            'redeemed_bonuses' => 0.0,
            'net_income' => 100.03,
            'deposits' => 100.0,
            'withdrawals' => 0.0,
            'balance_start_real' => 0.0,
            'balance_end_real' => 0.33,
            'balance_start_bonus' => 0.0,
            'balance_end_bonus' => 0.0,
            'email' => 'player@example.com',
        ];
    }

    public function test_sends_email_to_player(): void
    {
        Mail::fake();

        $job = new SendPlayerEarningsEmailJob($this->playerData, 2025);
        $job->handle();

        Mail::assertSent(AnnualEarningsReportMail::class, function ($mail) {
            return $mail->hasTo('player@example.com')
                && $mail->year === 2025
                && $mail->playerData['player_id'] === '10310001';
        });
    }

    public function test_job_is_queued_on_emails_queue(): void
    {
        $job = new SendPlayerEarningsEmailJob($this->playerData, 2025);

        $this->assertEquals('emails', $job->queue);
    }
}
