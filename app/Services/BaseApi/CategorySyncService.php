<?php

namespace App\Services\BaseApi;

use App\Models\Domain\Casino\Category;
use App\Models\Domain\Casino\Slot;
use App\Models\Domain\Casino\PortalGame;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CategorySyncService
{
    public function __construct(protected BasePortalApiClient $client)
    {
    }

    public static function make(): self
    {
        return new self(BasePortalApiClient::fromConfig());
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