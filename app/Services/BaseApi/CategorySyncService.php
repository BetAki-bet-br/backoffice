<?php

namespace App\Services\BaseApi;

use App\Models\Domain\Casino\Category;
use App\Models\Domain\Casino\Slot;
use App\Models\Domain\Casino\PortalGame;
use App\Models\Domain\Casino\Provider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CategorySyncService
{
    protected $providersCache = [];

    public function __construct(protected BasePortalApiClient $client)
    {
    }

    public static function make(): self
    {
        return new self(BasePortalApiClient::fromConfig());
    }

    public function syncFromLobby(int $portalId, ?int $levelId): array
    {
        $data = $this->client->getLobby($portalId, $levelId);
        Log::info('SyncFromLobby: Lobby data fetched.', ['portalId' => $portalId, 'levelId' => $levelId, 'item_count' => count($data)]);

        $stats = [
            'fetched' => count($data),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'games_synced' => 0,
        ];

        $this->processCategoryItems($data, $portalId, $stats);

        $this->saveProviders();

        return $stats;
    }

    protected function processCategoryItems(array $items, int $portalId, array &$stats)
    {
        foreach ($items as $item) {
            if (!is_array($item) || empty($item['name'])) {
                Log::warning('SyncFromLobby: Skipping item due to invalid structure or empty name.', ['item' => $item]);
                $stats['skipped']++;
                continue;
            }

            $name = trim($item['name']);
            Log::info("SyncFromLobby: Processing category '{$name}'.");

            try {
                if ($name === 'Lançamentos') {
                    Log::debug("Lançamentos: Attempting to process item.", ['item_sample' => array_slice($item, 0, 5)]);
                }
                $externalId = (string)($item['id'] ?? null);

                // Determine Vertical based on parentId
                $currentVertical = 'slots'; // Default
                if (isset($item['parentId'])) {
                    $pid = (int) $item['parentId'];
                    if ($pid === 500) {
                        $currentVertical = 'slots';
                    } elseif ($pid === 520) {
                        $currentVertical = 'live';
                    }
                }

                $category = null;
                if ($externalId) {
                    $category = Category::where('meta->external_id', $externalId)->first();
                }

                $isNew = false;
                if (!$category) {
                    $slug = Str::slug($name);
                    Log::debug("Generating slug for '{$name}'. Initial slug: '{$slug}'.");

                    // Ensure unique slug for new categories
                    $originalSlug = $slug;
                    $counter = 1;
                    while (Category::where('slug', $slug)->exists()) {
                        $slug = $originalSlug . '-' . $counter++;
                        Log::debug("Slug conflict for '{$name}'. New slug attempt: '{$slug}'.");
                    }

                    $category = new Category();
                    $category->slug = $slug;
                    $isNew = true;
                    Log::info("SyncFromLobby: Category '{$name}' not found by external ID. Creating new.", ['slug' => $slug, 'external_id' => $externalId]);
                }

                if ($isNew) {
                    $category->verticals = [$currentVertical];
                } else {
                    $verticals = $category->verticals ?? [];
                    if (!in_array($currentVertical, $verticals)) {
                        $verticals[] = $currentVertical;
                        $category->verticals = $verticals;
                    }
                    Log::info("SyncFromLobby: Category '{$name}' found. Updating.", ['id' => $category->id]);
                }

                $category->name = $name;
                $category->status = 'active';
                $category->type = 'game-list';

                $meta = $category->meta ?? [];
                $meta['external_id'] = $externalId; // Use the string version
                $meta['parent_id'] = $item['parentId'] ?? null;
                $meta['level_type'] = $item['levelType'] ?? null;
                $meta['game_name'] = $item['gameName'] ?? null;
                $meta['source'] = 'sync-lobby';
                $category->meta = $meta;

                $category->save();

                if ($isNew) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }

                $gameMains = $item['gameMains'] ?? [];
                $slotIds = [];

                if (is_array($gameMains)) {
                    foreach ($gameMains as $gameIndex => $gameData) {
                        if (!is_array($gameData) || empty($gameData['externalId']) || empty($gameData['name'])) {
                            continue;
                        }

                        $providerName = $gameData['productName'] ?? $gameData['productSupplierName'] ?? 'Unknown';
                        $externalId = (string) $gameData['externalId'];
                        $gameTitle = trim($gameData['name']);

                        $pId = $gameData['productId'] ?? null;
                        $pName = $gameData['productName'] ?? null;

                        if ($pId && $pName) {
                            $pIdStr = (string) $pId;
                            if (!isset($this->providersCache[$pIdStr])) {
                                $this->providersCache[$pIdStr] = ['name' => $pName, 'games' => []];
                            }
                            $this->providersCache[$pIdStr]['games'][$externalId] = true;
                        }

                        $slot = Slot::updateOrCreate(
                            [
                                'provider' => $providerName,
                                'provider_game_id' => $externalId
                            ],
                            [
                                'title' => $gameTitle,
                                'status' => 'active',
                                'tags' => [
                                    'gameTypeName' => $gameData['gameTypeName'] ?? null,
                                    'gameTypeId' => $gameData['gameTypeId'] ?? null,
                                    'productId' => $gameData['productId'] ?? null,
                                    'productSupplierId' => $gameData['productSupplierId'] ?? null,
                                    'id' => $gameData['id'] ?? null
                                ]
                            ]
                        );

                        PortalGame::updateOrCreate(
                            ['portal_id' => $portalId, 'external_id' => $externalId],
                            [
                                'name' => $gameTitle,
                                'product_name' => $providerName,
                                'supplier_name' => $gameData['productSupplierName'] ?? null,
                                'payload' => $gameData
                            ]
                        );

                        $slotIds[$slot->id] = ['position' => $gameIndex];
                    }

                    if (!empty($slotIds)) {
                        $category->slots()->sync($slotIds);
                        $stats['games_synced'] += count($slotIds);
                        Log::info("SyncFromLobby: Synced " . count($slotIds) . " games to category '{$name}'.");
                    }
                }

                if (!empty($item['subLevel']) && is_array($item['subLevel'])) {
                    $this->processCategoryItems($item['subLevel'], $portalId, $stats);
                }

            } catch (\Exception $e) {
                if ($name === 'Lançamentos') {
                    $itemCopy = $item;
                    unset($itemCopy['gameMains']); // Avoid logging hundreds of games
                    Log::error("Lançamentos: CRITICAL ERROR during processing.", [
                        'error_message' => $e->getMessage(),
                        'item' => $itemCopy,
                    ]);
                }
                Log::error("SyncFromLobby: Error processing category '{$name}'.", [
                    'error_message' => $e->getMessage(),
                    'item' => $item,
                    'exception' => $e
                ]);
                $stats['skipped']++;
            }
        }
    }

    protected function saveProviders()
    {
        $count = count($this->providersCache);
        if ($count > 0) {
            foreach ($this->providersCache as $id => $data) {
                Provider::updateOrCreate(
                    ['external_id' => $id],
                    [
                        'name' => $data['name'],
                        'game_count' => count($data['games']),
                        'status' => 'active'
                    ]
                );
            }
        }
    }

    public function syncPortal(int $portalId): array
    {
        $json = $this->client->getGameCategories($portalId);
        
        // Log requested to verify games are present
        Log::info('CategorySyncService: Response received', [
            'portalId' => $portalId,
            'category_count' => count($json['gameCategoryList'] ?? []),
            'sample_item' => $json['gameCategoryList'][0] ?? null,
        ]);

        $list = $json['gameCategoryList'] ?? [];
        if (!is_array($list)) $list = [];

        $stats = [
            'fetched' => count($list),
            'created' => 0,
            'updated' => 0,
            'games_synced' => 0,
        ];

        foreach ($list as $item) {
            if (!is_array($item)) continue;

            $externalId = $item['id'] ?? null;
            if (!$externalId) continue;

            $name = $item['name'] ?? 'Sem Nome';
            $parentId = $item['parentId'] ?? null;
            $typeId = $item['categoryTypeId'] ?? null;

            // Tenta encontrar por external_id no meta
            $externalIdStr = (string)$externalId;
            
            $category = Category::query()
                ->where('meta->external_id', $externalIdStr)
                ->first();

            if (!$category) {
                // Tenta fallback por slug para evitar duplicação se não tiver external_id gravado ainda
                $slug = Str::slug($name);
                // Ensure unique slug
                $originalSlug = $slug;
                $counter = 1;
                while (Category::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }

                $category = Category::create([
                    'name' => $name,
                    'slug' => $slug,
                    'status' => 'active', // Default active?
                    'position' => 0,
                    'meta' => [
                        'external_id' => $externalIdStr,
                        'parent_id' => $parentId,
                        'category_type_id' => $typeId,
                        'original_type' => 'game-list', // Default mapping
                        'source' => 'sync',
                    ],
                ]);
                $stats['created']++;
            } else {
                // Update
                $meta = $category->meta ?? [];
                $meta['external_id'] = $externalIdStr;
                $meta['parent_id'] = $parentId;
                $meta['category_type_id'] = $typeId;
                // Preserve other meta fields if any

                $category->update([
                    'name' => $name,
                    // Não alteramos o slug existente para não quebrar links
                    'meta' => $meta,
                ]);
                $stats['updated']++;
            }

            // Sync Games if present
            // We check common keys for games list
            $games = $item['games'] ?? $item['gameList'] ?? [];
            if (!empty($games) && is_array($games)) {
                Log::info("Category {$category->id} ({$name}) has " . count($games) . " games from API.");
                
                $slotIds = [];
                foreach ($games as $g) {
                    // Extract Game ID
                    $gId = is_array($g) ? ($g['id'] ?? $g['externalId'] ?? null) : $g;
                    if (!$gId) continue;
                    
                    $gIdStr = (string)$gId;
                    
                    // Find existing Slot by provider_game_id
                    $slot = Slot::where('provider_game_id', $gIdStr)->first();
                    
                    if (!$slot) {
                        // Try to find PortalGame info to populate Slot
                        $pg = PortalGame::where('external_id', $gIdStr)->first();
                        
                        $slot = Slot::create([
                            'provider_game_id' => $gIdStr,
                            'provider' => $pg?->supplier_name ?? 'unknown',
                            'title' => $pg?->name ?? "Game $gIdStr",
                            'status' => 'active',
                            'created_by' => 0,
                        ]);
                    }
                    
                    $slotIds[] = $slot->id;
                }
                
                if (!empty($slotIds)) {
                    $category->slots()->sync($slotIds);
                    $stats['games_synced'] += count($slotIds);
                }
            }
        }

        return $stats;
    }
}