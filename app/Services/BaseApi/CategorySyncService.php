<?php

namespace App\Services\BaseApi;

use App\Models\Domain\Casino\Category;
use Illuminate\Support\Str;

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

        $list = $json['gameCategoryList'] ?? [];
        if (!is_array($list)) $list = [];

        $stats = [
            'fetched' => count($list),
            'created' => 0,
            'updated' => 0,
        ];

        foreach ($list as $item) {
            if (!is_array($item)) continue;

            $externalId = $item['id'] ?? null;
            if (!$externalId) continue;

            $name = $item['name'] ?? 'Sem Nome';
            $parentId = $item['parentId'] ?? null;
            $typeId = $item['categoryTypeId'] ?? null;

            // Tenta encontrar por external_id no meta
            // Note: SQLite/MySQL JSON syntax differ slightly in raw queries, 
            // but Laravel's where('meta->external_id', ...) works broadly.
            // Converting to string for consistency.
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
        }

        return $stats;
    }
}
