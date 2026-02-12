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

        $existingCategory = Category::withoutGlobalScopes()->where('slug', $slug)->first();

        // Prepare meta data, merging with existing if any
        $metaData = array_merge($existingCategory->meta ?? [], [
            'external_id' => (string) $externalId,
            'parent_id' => $parentId,
            'category_type_id' => $item['categoryTypeId'] ?? null,
            'title' => $item['gameName'] ?? $item['title'] ?? null,
            'level_type' => $item['levelType'] ?? null,
            'sub_level_structure' => !empty($item['subLevel']),
            'original_type' => 'game-list',
            'source' => 'sync',
        ]);

        $verticals = $existingCategory->verticals ?? [];
        if ($currentVertical && !in_array($currentVertical, $verticals)) {
            $verticals[] = $currentVertical;
        }

        $category = Category::withoutGlobalScopes()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'meta' => $metaData,
                'status' => $existingCategory ? $existingCategory->status : 'active',
                'position' => $existingCategory->position ?? 0,
                'verticals' => $verticals,
            ]
        );

        if ($category->wasRecentlyCreated) {
            $this->stats['created']++;
        } else {
            $this->stats['updated']++;
        }


        // The lobby payload contains the games directly within the category item
        $games = $item['gameMains'] ?? [];

        if (!empty($games) && is_array($games)) {
            Log::info("Category {$category->id} ({$name}) has ".count($games)." games from Lobby API payload.");

            $slotsToUpsert = [];
            $portalGamesToUpsert = [];
            $gamePositionMap = [];

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

                // Prepare data for upsert
                $slotsToUpsert[] = [
                    'provider' => $providerName,
                    'provider_game_id' => $gameExternalId,
                    'title' => $gameTitle,
                    'status' => 'active',
                    'tags' => json_encode([ // JSON encode for upsert
                        'gameTypeName' => $gameData['gameTypeName'] ?? null,
                        'gameTypeId' => $gameData['gameTypeId'] ?? null,
                        'productId' => $gameData['productId'] ?? null,
                        'productSupplierId' => $gameData['productSupplierId'] ?? null,
                        'id' => $gameData['id'] ?? null,
                        'demoPlayRestricted' => $gameData['demoPlayRestricted'] ?? null,
                        'realPlayRestricted' => $gameData['realPlayRestricted'] ?? null,
                        'maintenanceModeEnabled' => $gameData['maintenanceModeEnabled'] ?? null,
                    ]),
                ];

                $portalGamesToUpsert[] = [
                    'portal_id' => $portalId,
                    'external_id' => $gameExternalId,
                    'name' => $gameTitle,
                    'product_name' => $providerName,
                    'supplier_name' => $gameData['productSupplierName'] ?? null,
                    'payload' => json_encode($gameData), // JSON encode for upsert
                ];

                // Map composite key to position
                $compositeKey = $providerName . '|' . $gameExternalId;
                $gamePositionMap[$compositeKey] = $gameIndex;
            }

            if (!empty($slotsToUpsert)) {
                // 1. Upsert Slots and PortalGames atomically
                Slot::upsert(
                    $slotsToUpsert,
                    ['provider', 'provider_game_id'],
                    ['title', 'status', 'tags']
                );

                PortalGame::upsert(
                    $portalGamesToUpsert,
                    ['portal_id', 'external_id'],
                    ['name', 'product_name', 'supplier_name', 'payload']
                );

                // 2. Fetch the IDs of the upserted slots
                $slots = Slot::where(function ($query) use ($slotsToUpsert) {
                    foreach ($slotsToUpsert as $slot) {
                        $query->orWhere(function ($q) use ($slot) {
                            $q->where('provider', $slot['provider'])
                              ->where('provider_game_id', $slot['provider_game_id']);
                        });
                    }
                })->select('id', 'provider', 'provider_game_id')->get();

                // 3. Build the array for syncing
                $slotIdsToSync = [];
                foreach ($slots as $slot) {
                    $compositeKey = $slot->provider . '|' . $slot->provider_game_id;
                    if (isset($gamePositionMap[$compositeKey])) {
                        $position = $gamePositionMap[$compositeKey];
                        $slotIdsToSync[$slot->id] = ['position' => $position];
                    }
                }

                // 4. Sync slots to the category
                if (!empty($slotIdsToSync)) {
                    Log::info("Syncing slots for category {$category->id}", ['slot_ids_to_sync' => count($slotIdsToSync)]);
                    $category->slots()->sync($slotIdsToSync);
                    $this->stats['games_synced'] += count($slotIdsToSync);
                }
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
            $providersToUpsert = [];
            foreach ($this->providersCache as $id => $data) {
                $providersToUpsert[] = [
                    'external_id' => $id,
                    'name' => $data['name'],
                    'game_count' => count($data['games']),
                    'status' => 'active',
                    'verticals' => json_encode($data['verticals']),
                ];
            }

            Provider::upsert(
                $providersToUpsert,
                ['external_id'],
                ['name', 'game_count', 'status', 'verticals']
            );
        }
        return $count;
    }
}