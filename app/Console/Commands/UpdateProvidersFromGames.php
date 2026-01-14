<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Domain\Casino\PortalGame;
use App\Models\Domain\Casino\Provider;
use App\Models\Domain\Casino\Slot;

class UpdateProvidersFromGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-providers-from-games';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan PortalGame table and populate Providers table based on payload data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Scanning PortalGames to extract Providers...");
        
        $stats = ['created' => 0, 'updated' => 0, 'scanned' => 0, 'slots_linked' => 0];
        $providersMap = [];

        PortalGame::chunk(500, function($games) use (&$stats, &$providersMap) {
            foreach ($games as $game) {
                $stats['scanned']++;
                $payload = $game->payload;
                
                if (!$payload || !is_array($payload)) continue;

                // Use productId and productName as per new logic (consistent with ImportCategories)
                $pId = $payload['productId'] ?? null;
                $pName = $payload['productName'] ?? null;
                $externalId = $payload['externalId'] ?? null;

                if (!$pId || !$pName) continue;

                // Sync Slot tags to link with Provider (productId)
                if ($externalId) {
                    // Try to find the slot matching this portal game
                    $slot = Slot::where('provider_game_id', $externalId)
                        ->where('provider', $pName) // Ensure strict match with PortalGame product_name/payload name
                        ->first();

                    if ($slot) {
                        $tags = $slot->tags ?? [];
                        if (!isset($tags['productId']) || $tags['productId'] != $pId) {
                            $tags['productId'] = (int)$pId;
                            $slot->tags = $tags;
                            $slot->save();
                            $stats['slots_linked']++;
                        }
                    }
                }
                
                $idStr = (string)$pId;

                if (!isset($providersMap[$idStr])) {
                    $providersMap[$idStr] = [
                        'external_id' => $idStr,
                        'name' => $pName,
                        'games' => [] // Use array to track unique games
                    ];
                }
                
                if ($externalId) {
                    $providersMap[$idStr]['games'][$externalId] = true;
                }
            }
            $this->output->write('.');
        });

        $this->newLine();
        $this->info("Found " . count($providersMap) . " unique providers. Updating DB...");

        $bar = $this->output->createProgressBar(count($providersMap));
        $bar->start();

        foreach ($providersMap as $data) {
            $provider = Provider::where('external_id', $data['external_id'])->first();
            $gameCount = count($data['games']);

            if (!$provider) {
                Provider::create([
                    'external_id' => $data['external_id'],
                    'name' => $data['name'],
                    'game_count' => $gameCount,
                    'status' => 'active'
                ]);
                $stats['created']++;
            } else {
                $provider->update([
                    'name' => $data['name'],
                    'game_count' => $gameCount
                ]);
                $stats['updated']++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Scanned: {$stats['scanned']}. Created: {$stats['created']}, Updated: {$stats['updated']}, Slots Linked: {$stats['slots_linked']}");
        
        return 0;
    }
}