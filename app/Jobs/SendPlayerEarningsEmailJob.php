<?php

namespace App\Jobs;

use App\Mail\AnnualEarningsReportMail;
use App\Services\BaseApi\BasePortalApiClient;
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

    public function handle(BasePortalApiClient $client): void
    {
        $playerId = $this->playerData['player_id'];
        $username = $this->playerData['username'];

        $email = $client->getPlayerEmail($playerId);

        if (empty($email)) {
            Log::warning("SendPlayerEarningsEmailJob: No email found for player {$playerId} ({$username}), skipping.");

            return;
        }

        Mail::to($email)->send(new AnnualEarningsReportMail($this->playerData, $this->year));

        Log::info("SendPlayerEarningsEmailJob: Earnings report sent to player {$playerId} ({$username}) at {$email}.");
    }

    public function failed(\Throwable $exception): void
    {
        $playerId = $this->playerData['player_id'] ?? 'unknown';
        $username = $this->playerData['username'] ?? 'unknown';

        Log::error("SendPlayerEarningsEmailJob: Failed for player {$playerId} ({$username}): {$exception->getMessage()}");
    }
}
