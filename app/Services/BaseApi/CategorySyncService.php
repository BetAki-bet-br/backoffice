<?php

namespace App\Services\BaseApi;

use App\Models\Domain\Casino\Category;
use App\Models\Domain\Casino\PortalGame;
use App\Models\Domain\Casino\Provider;
use App\Models\Domain\Casino\Slot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategorySyncService
{
    protected array $providersCache = [];
    protected array $stats = [];

    public function __construct(protected BasePortalApiClient $client)
    {
    }

    public static function make(): self
    {
        return new self(BasePortalApiClient::fromConfig());
    }

    public function syncPortal(int $portalId, ?int $levelId = null): array
    {
        $this->providersCache = [];
        // Make a single call to get the entire lobby structure
        $list = $this->client->getLobby($portalId, $levelId);

        Log::debug('CategorySyncService: Raw lobby response', ['response' => $list]);

        Log::info('CategorySyncService: Lobby response received', [
            'portalId' => $portalId,
            'levelId' => $levelId,
            'top_level_category_count' => count($list),
            'sample_item' => $list[0] ?? null,
        ]);

        if (!is_array($list)) {
            $list = [];
        }

        $this->stats = [
            'fetched' => count($list),
            'created' => 0,
            'updated' => 0,
            'games_synced' => 0,
            'providers_synced' => 0,
        ];

        foreach ($list as $item) {
            $this->processCategoryItem($item, $portalId);
        }

        $this->stats['providers_synced'] = $this->saveProviders();

        return $this->stats;
    }

    protected function processCategoryItem(array $item, int $portalId)
    {
        if (!is_array($item)) {
            return;
        }

        $externalId = $item['id'] ?? null;
        if (!$externalId) {
            return;
        }

        $name = $item['name'] ?? 'Sem Nome';
        $slug = Str::slug($name);
        if (empty($slug)) {
            return;
        }

        $parentId = $item['parentId'] ?? null;

        // Determine Vertical based on parentId
        $currentVertical = null;
        if (isset($parentId)) {
            $pid = (int) $parentId;
            if ($pid === 500) {
                $currentVertical = 'slots';
            } elseif ($pid === 520) {
                $currentVertical = 'live';
            }
        }

        // Use updateOrCreate to find by slug or create a new one
        $category = Category::updateOrCreate(
            ['slug' => $slug],
            ['name' => $name]
        );

        // Prepare meta data, merging with existing if any
        $metaData = array_merge($category->meta ?? [], [
            'external_id' => (string) $externalId,
            'parent_id' => $parentId,
            'category_type_id' => $item['categoryTypeId'] ?? null,
            'title' => $item['gameName'] ?? $item['title'] ?? null,
            'level_type' => $item['levelType'] ?? null,
            'sub_level_structure' => !empty($item['subLevel']),
            'original_type' => 'game-list',
            'source' => 'sync',
        ]);

        if ($category->wasRecentlyCreated) {
            // Set defaults for new categories
            $category->status = 'inactive';
            $category->position = 0;
            $category->verticals = $currentVertical ? [$currentVertical] : [];
            $this->stats['created']++;
        } else {
            // Update verticals for existing categories
            $verticals = $category->verticals ?? [];
            if ($currentVertical && !in_array($currentVertical, $verticals)) {
                $verticals[] = $currentVertical;
                $category->verticals = $verticals;
            }
            $this->stats['updated']++;
        }

        // Always update name and meta
        $category->name = $name;
        $category->meta = $metaData;
        $category->save();


        // The lobby payload contains the games directly within the category item
        $games = $item['gameMains'] ?? [];

        if (!empty($games) && is_array($games)) {
            Log::info("Category {$category->id} ({$name}) has ".count($games)." games from Lobby API payload.");

            $slotIds = [];
            foreach ($games as $gameIndex => $gameData) {
                if (!is_array($gameData) || empty($gameData['externalId']) || empty($gameData['name'])) {
                    Log::warning('Skipping invalid game data from payload.', ['category_id' => $category->id, 'game_data' => $gameData]);
                    continue;
                }

                $providerName = $gameData['productName'] ?? $gameData['productSupplierName'] ?? 'Unknown';
                $gameExternalId = (string) $gameData['externalId'];
                $gameTitle = trim($gameData['name']);

                // Cache Provider Info
                $pId = $gameData['productId'] ?? null;
                if ($pId && $providerName) {
                    $pIdStr = (string) $pId;
                    if (!isset($this->providersCache[$pIdStr])) {
                        $this->providersCache[$pIdStr] = [
                            'name' => $providerName,
                            'games' => [],
                            'verticals' => [],
                        ];
                    }
                    $this->providersCache[$pIdStr]['games'][$gameExternalId] = true;

                    if ($currentVertical && !in_array($currentVertical, $this->providersCache[$pIdStr]['verticals'])) {
                        $this->providersCache[$pIdStr]['verticals'][] = $currentVertical;
                    }
                }

                // 1. Update/Create Slot
                $slot = Slot::updateOrCreate(
                    [
                        'provider' => $providerName,
                        'provider_game_id' => $gameExternalId,
                    ],
                    [
                        'title' => $gameTitle,
                        'status' => 'active',
                        'tags' => [
                            'gameTypeName' => $gameData['gameTypeName'] ?? null,
                            'gameTypeId' => $gameData['gameTypeId'] ?? null,
                            'productId' => $gameData['productId'] ?? null,
                            'productSupplierId' => $gameData['productSupplierId'] ?? null,
                            'id' => $gameData['id'] ?? null,
                            'demoPlayRestricted' => $gameData['demoPlayRestricted'] ?? null,
                            'realPlayRestricted' => $gameData['realPlayRestricted'] ?? null,
                            'maintenanceModeEnabled' => $gameData['maintenanceModeEnabled'] ?? null,
                        ],
                    ]
                );

                // 2. Update/Create PortalGame
                PortalGame::updateOrCreate(
                    [
                        'portal_id' => $portalId,
                        'external_id' => $gameExternalId,
                    ],
                    [
                        'name' => $gameTitle,
                        'product_name' => $providerName,
                        'supplier_name' => $gameData['productSupplierName'] ?? null,
                        'payload' => $gameData,
                    ]
                );

                $slotIds[$slot->id] = ['position' => $gameIndex];
            }

            if (!empty($slotIds)) {
                Log::info("Syncing slots for category {$category->id}", ['slot_ids_to_sync' => count($slotIds)]);
                $category->slots()->sync($slotIds);
                $this->stats['games_synced'] += count($slotIds);
            }
        }

        // Recursive call for sub-levels
        $subLevels = $item['subLevel'] ?? [];
        if (!empty($subLevels) && is_array($subLevels)) {
            foreach ($subLevels as $subItem) {
                $this->processCategoryItem($subItem, $portalId);
            }
        }
    }

    protected function saveProviders(): int
    {
        $count = count($this->providersCache);
        if ($count > 0) {
            Log::info("Upserting {$count} providers...");
            foreach ($this->providersCache as $id => $data) {
                Provider::updateOrCreate(
                    ['external_id' => $id],
                    [
                        'name' => $data['name'],
                        'game_count' => count($data['games']),
                        'status' => 'active',
                        'verticals' => $data['verticals'],
                    ]
                );
            }
        }
        return $count;
    }
}