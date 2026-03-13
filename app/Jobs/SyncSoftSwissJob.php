<?php

namespace App\Jobs;

use App\Models\SyncJob;
use App\Services\SoftSwiss\SoftSwissSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSoftSwissJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 600;

    /**
     * @param  array<string>|null  $providers  Optional filter for specific provider slugs.
     */
    public function __construct(
        protected int $syncJobId,
        protected ?array $providers = null,
    ) {
        Log::info('SyncSoftSwissJob constructed', [
            'syncJobId' => $this->syncJobId,
            'providers' => $this->providers,
        ]);
    }

    public function handle(): void
    {
        $job = SyncJob::find($this->syncJobId);
        if (! $job) {
            Log::error('SyncSoftSwissJob failed: SyncJob not found', ['syncJobId' => $this->syncJobId]);

            return;
        }

        Log::info('SyncSoftSwissJob handle method called');
        $job->update(['status' => 'running', 'started_at' => now()]);

        try {
            $service = SoftSwissSyncService::make();
            $stats = $service->sync($this->providers);

            $job->update([
                'status' => 'completed',
                'message' => json_encode($stats),
                'completed_at' => now(),
            ]);
            Log::info('SyncSoftSwissJob finished successfully', ['stats' => $stats]);
        } catch (\Exception $e) {
            $job->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            Log::error('SyncSoftSwissJob failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        Log::info('SyncSoftSwissJob handle method finished');
    }
}
