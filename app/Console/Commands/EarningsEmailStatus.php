<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EarningsEmailStatus extends Command
{
    protected $signature = 'app:earnings-email-status
        {--failed-only : Show only failed jobs}';

    protected $description = 'Show the status of dispatched annual earnings report emails';

    public function handle(): int
    {
        // Failed jobs
        $failedJobs = DB::table('failed_jobs')
            ->where('payload', 'like', '%SendPlayerEarningsEmailJob%')
            ->orderByDesc('failed_at')
            ->get();

        if ($failedJobs->isEmpty()) {
            $this->info('No failed earnings email jobs found.');
        } else {
            $this->error("Failed jobs: {$failedJobs->count()}");
            $this->newLine();

            $rows = [];
            foreach ($failedJobs as $job) {
                $payload = json_decode($job->payload, true);
                $command = unserialize($payload['data']['command'] ?? '');

                $playerData = $command->playerData ?? [];
                $playerId = $playerData['player_id'] ?? 'N/A';
                $username = $playerData['username'] ?? 'N/A';
                $email = $playerData['email'] ?? 'N/A';

                // Extract the core error message (first line)
                $exception = $job->exception ?? '';
                $errorLine = strtok($exception, "\n");
                // Trim long error messages
                if (strlen($errorLine) > 120) {
                    $errorLine = substr($errorLine, 0, 120) . '...';
                }

                $rows[] = [
                    $job->id,
                    $playerId,
                    $username,
                    $email,
                    $job->failed_at,
                    $errorLine,
                ];
            }

            $this->table(
                ['ID', 'Player ID', 'Username', 'Email', 'Failed At', 'Error'],
                $rows,
            );
        }

        if ($this->option('failed-only')) {
            return self::SUCCESS;
        }

        // Pending jobs in queue
        $pendingCount = DB::table('jobs')
            ->where('payload', 'like', '%SendPlayerEarningsEmailJob%')
            ->count();

        $this->newLine();
        $this->info("Pending in queue: {$pendingCount}");

        return self::SUCCESS;
    }
}
