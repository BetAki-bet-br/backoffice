<?php

namespace App\Jobs;

use App\Services\BaseApi\ProviderSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProvidersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600; // 10 minutes timeout

    public function __construct(protected int $portalId)
    {
    }

    public function handle(): void
    {
        Log::info('SyncProvidersJob started', ['portalId' => $this->portalId]);
        
        try {
            $service = ProviderSyncService::make();
            $stats = $service->syncPortal($this->portalId);
            Log::info('SyncProvidersJob finished successfully', ['stats' => $stats]);
        } catch (\Exception $e) {
            Log::error('SyncProvidersJob failed', [
                'portalId' => $this->portalId, 
                'error' => $e->getMessage()
            ]);
        }
    }
}
