<?php

namespace App\Jobs;

use App\Models\SyncJob;
use App\Services\BaseApi\CategorySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600;

    public function __construct(
        protected int $portalId,
        protected ?int $levelId,
        protected int $syncJobId
    ) {
        Log::info('SyncCategoriesJob constructed', ['portalId' => $this->portalId, 'levelId' => $this->levelId, 'syncJobId' => $this->syncJobId]);
    }

    public function handle(): void
    {
        $job = SyncJob::find($this->syncJobId);
        if (!$job) {
            Log::error('SyncCategoriesJob failed: SyncJob not found', ['syncJobId' => $this->syncJobId]);
            return;
        }

        Log::info('SyncCategoriesJob handle method called');
        $job->update(['status' => 'running', 'started_at' => now()]);

        try {
            $service = CategorySyncService::make();
            Log::info('CategorySyncService instantiated');
            $stats = $service->syncPortal($this->portalId, $this->levelId);

            $job->update([
                'status' => 'completed',
                'message' => json_encode($stats),
                'completed_at' => now(),
            ]);
            Log::info('SyncCategoriesJob finished successfully', ['stats' => $stats]);
        } catch (\Exception $e) {
            $job->update([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            Log::error('SyncCategoriesJob failed', [
                'portalId' => $this->portalId,
                'levelId' => $this->levelId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        Log::info('SyncCategoriesJob handle method finished');
    }
}

