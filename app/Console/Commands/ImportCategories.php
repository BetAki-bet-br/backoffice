<?php

namespace App\Console\Commands;

use App\Models\Domain\Casino\Category;
use App\Models\Domain\Casino\PortalGame;
use App\Models\Domain\Casino\Provider;
use App\Models\Domain\Casino\Slot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportCategories extends Command
{
    protected $providersCache = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-categories {file=categories.json} {--vertical=slots} {--portal=5}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import categories and games from a JSON file into the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $vertical = $this->option('vertical');
        $portalId = (int) $this->option('portal');

        if (! File::exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        $this->info("Reading file: {$filePath} (Vertical: {$vertical}, Portal: {$portalId})");

        $jsonContent = File::get($filePath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('JSON Error: '.json_last_error_msg());

            return 1;
        }

        if (! is_array($data)) {
            $this->error('Invalid JSON structure. Expected an array of categories.');

            return 1;
        }

        $count = count($data);
        $this->info("Found {$count} entries. Starting import...");

        $imported = 0;
        $skipped = 0;

        foreach ($data as $index => $item) {
            // Type checking for item structure
            if (! is_array($item) || ! isset($item['data']) || ! is_array($item['data'])) {
                $this->warn("Skipping index {$index}: Invalid structure (missing 'data' array).");
                $skipped++;

                continue;
            }

            $catData = $item['data'];

            // Type checking for required fields
            if (empty($catData['name']) || ! is_string($catData['name'])) {
                $this->warn("Skipping index {$index}: Missing or invalid 'name'.");
                $skipped++;

                continue;
            }

            $name = trim($catData['name']);
            // Generate slug from name.
            $slug = Str::slug($name);

            if (empty($slug)) {
                $this->warn("Skipping index {$index}: Could not generate slug from name '{$name}'.");
                $skipped++;

                continue;
            }

            // Determine Vertical based on parentId
            $currentVertical = $vertical; // Default from CLI
            if (isset($catData['parentId'])) {
                $pid = (int) $catData['parentId'];
                if ($pid === 500) {
                    $currentVertical = 'slots';
                } elseif ($pid === 520) {
                    $currentVertical = 'live';
                }
            }

            try {
                // Find existing category by slug
                $category = Category::where('slug', $slug)->first();

                if (! $category) {
                    $category = new Category;
                    $category->slug = $slug;
                    $category->verticals = [$currentVertical];
                } else {
                    $verticals = $category->verticals ?? [];
                    if (! in_array($currentVertical, $verticals)) {
                        $verticals[] = $currentVertical;
                        $category->verticals = $verticals;
                    }
                }

                $category->name = $name;
                $category->status = 'active';
                $category->type = isset($item['type']) ? (string) $item['type'] : 'game-list';

                // Prepare meta data
                $meta = $category->meta ?? [];

                // Store external identifiers and other properties in meta
                $meta['external_id'] = isset($catData['id']) ? (string) $catData['id'] : null;
                $meta['original_type'] = isset($item['type']) ? (string) $item['type'] : null;
                $meta['parent_id'] = isset($catData['parentId']) ? (int) $catData['parentId'] : null;
                $meta['title'] = isset($catData['title']) ? (string) $catData['title'] : null;
                $meta['level_type'] = isset($catData['levelType']) ? (string) $catData['levelType'] : null;
                $meta['sub_level'] = isset($catData['subLevel']) ? $catData['subLevel'] : [];

                $category->meta = $meta;
                $category->save();

                // Import Games (Slots and PortalGames)
                $gameMains = $catData['gameMains'] ?? [];
                $slotIds = [];

                if (is_array($gameMains)) {
                    foreach ($gameMains as $gameIndex => $gameData) {
                        if (! is_array($gameData) || empty($gameData['externalId']) || empty($gameData['name'])) {
                            continue;
                        }

                        // Determine provider: prefer productName, fallback to productSupplierName
                        $providerName = $gameData['productName'] ?? $gameData['productSupplierName'] ?? 'Unknown';
                        $externalId = (string) $gameData['externalId'];
                        $gameTitle = trim($gameData['name']);
                        $supplierId = $gameData['productSupplierId'] ?? null;

                        // New Provider Extraction Logic (based on productId/productName)
                        $pId = $gameData['productId'] ?? null;
                        $pName = $gameData['productName'] ?? null;

                        if ($pId && $pName) {
                            $pIdStr = (string) $pId;
                            if (! isset($this->providersCache[$pIdStr])) {
                                $this->providersCache[$pIdStr] = [
                                    'name' => $pName,
                                    'games' => [],
                                ];
                            }
                            // Store externalId to count unique games later
                            $this->providersCache[$pIdStr]['games'][$externalId] = true;
                        }

                        // 1. Update/Create Slot
                        $slot = Slot::updateOrCreate(
                            [
                                'provider' => $providerName,
                                'provider_game_id' => $externalId,
                            ],
                            [
                                'title' => $gameTitle,
                                'status' => 'active',
                                'tags' => [
                                    'gameTypeName' => $gameData['gameTypeName'] ?? null,
                                    'gameTypeId' => $gameData['gameTypeId'] ?? null,
                                    'productId' => $gameData['productId'] ?? null,
                                    'productSupplierId' => $supplierId,
                                    'id' => $gameData['id'] ?? null, // The integer ID from JSON
                                ],
                            ]
                        );

                        // 2. Update/Create PortalGame (Required for LobbyLayoutController)
                        PortalGame::updateOrCreate(
                            [
                                'portal_id' => $portalId,
                                'external_id' => $externalId,
                            ],
                            [
                                'name' => $gameTitle,
                                'product_name' => $providerName,
                                'supplier_name' => $gameData['productSupplierName'] ?? null,
                                'payload' => $gameData,
                            ]
                        );

                        // Collect ID for syncing, using index as position
                        $slotIds[$slot->id] = ['position' => $gameIndex];
                    }

                    if (! empty($slotIds)) {
                        $category->slots()->sync($slotIds);
                    }
                }

                $imported++;
                $this->line("Imported: {$name} ({$slug}) [{$vertical}] - Games: ".count($slotIds));

            } catch (\Exception $e) {
                $this->error("Error importing '{$name}': ".$e->getMessage());
                $skipped++;
            }
        }

        // Save Providers after processing all categories
        $this->saveProviders();

        $this->info('Import completed.');
        $this->info("Total imported/updated: {$imported}");
        $this->info("Total skipped: {$skipped}");

        return 0;
    }

    protected function saveProviders()
    {
        $count = count($this->providersCache);
        if ($count > 0) {
            $this->info("Upserting {$count} providers...");
            foreach ($this->providersCache as $id => $data) {
                Provider::updateOrCreate(
                    ['external_id' => $id],
                    [
                        'name' => $data['name'],
                        'game_count' => count($data['games']),
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
