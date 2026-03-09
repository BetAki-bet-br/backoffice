<?php

namespace App\Jobs;

use App\Mail\AnnualEarningsReportMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPlayerEarningsEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly array $playerData,
        public readonly int $year,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $playerId = $this->playerData['player_id'];
        $username = $this->playerData['username'];
        $email = $this->playerData['email'];

        Log::channel('earnings')->info("Sending earnings report to player {$playerId} ({$username}) at {$email}...");

        Mail::to($email)->send(new AnnualEarningsReportMail($this->playerData, $this->year));

        Log::channel('earnings')->info("SUCCESS: Earnings report sent to player {$playerId} ({$username}) at {$email}.");
    }

    public function failed(\Throwable $exception): void
    {
        $playerId = $this->playerData['player_id'] ?? 'unknown';
        $username = $this->playerData['username'] ?? 'unknown';
        $email = $this->playerData['email'] ?? 'unknown';

        Log::channel('earnings')->error("FAILED: Player {$playerId} ({$username}) at {$email} — {$exception->getMessage()}");
    }
}
