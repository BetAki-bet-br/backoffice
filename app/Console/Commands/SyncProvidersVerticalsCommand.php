<?php

namespace App\Console\Commands;

use App\Services\BaseApi\CategorySyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncProvidersVerticalsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'providers:sync-verticals {portalId? : The ID of the portal to sync. If not provided, all portals will be synced.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates provider verticals by syncing the game lobby for one or all portals.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $portalId = $this->argument('portalId');
        $portalsToSync = $portalId ? [$portalId] : [5, 6]; // Default to Desktop (5) and Mobile (6) portals

        $this->info('Starting provider verticals synchronization...');

        $syncService = CategorySyncService::make();

        foreach ($portalsToSync as $id) {
            $this->info("Syncing portal ID: {$id}...");
            try {
                // The syncPortal method already updates providers as part of its process
                $stats = $syncService->syncPortal($id);
                $this->info("Portal {$id} sync complete.");
                $this->line("  - Fetched Categories: {$stats['fetched']}");
                $this->line("  - Categories Created: {$stats['created']}");
                $this->line("  - Categories Updated: {$stats['updated']}");
                $this->line("  - Games Synced: {$stats['games_synced']}");
                $this->line("  - Providers Synced/Updated: {$stats['providers_synced']}");
            } catch (\Exception $e) {
                $this->error("An error occurred while syncing portal ID {$id}: ".$e->getMessage());
                Log::error('SyncProvidersVerticalsCommand failed for portal '.$id, [
                    'exception' => $e,
                ]);
            }
        }

        $this->info('Provider verticals synchronization finished successfully.');
    }
}
