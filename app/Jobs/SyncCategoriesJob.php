<?php

namespace App\Jobs;

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

    public $tries = 1; // Sync jobs should probably not be retried automatically
    public $timeout = 600; // 10 minutes timeout

    public function __construct(protected int $portalId, protected ?int $levelId)
    {
        Log::info('SyncCategoriesJob constructed', ['portalId' => $this->portalId, 'levelId' => $this->levelId]);
    }

    public function handle(): void
    {
        Log::info('SyncCategoriesJob handle method called');
        Log::info('SyncCategoriesJob started', ['portalId' => $this->portalId, 'levelId' => $this->levelId]);
        
        try {
            $service = CategorySyncService::make();
            Log::info('CategorySyncService instantiated');
            $stats = $service->syncPortal($this->portalId, $this->levelId);
            Log::info('SyncCategoriesJob finished successfully', ['stats' => $stats]);
        } catch (\Exception $e) {
            Log::error('SyncCategoriesJob failed', [
                'portalId' => $this->portalId, 
                'levelId' => $this->levelId, 
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Optionally, re-throw the exception if you want the job to be marked as failed
            // throw $e;
        }
        Log::info('SyncCategoriesJob handle method finished');
    }
}
