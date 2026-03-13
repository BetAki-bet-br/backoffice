<?php

namespace App\Services\SoftSwiss;

use App\Models\Domain\Casino\Category;
use App\Models\Domain\Casino\GameExtra;
use App\Models\Domain\Casino\Provider;
use App\Models\Domain\Casino\Slot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SoftSwissSyncService
{
    public function __construct(protected SoftSwissClient $client) {}

    public static function make(): self
    {
        return new self(SoftSwissClient::fromConfig());
    }

    /**
     * Sync games from SoftSwiss CDN for all configured (or filtered) providers.
     *
     * @param  array<string>|null  $providerFilter  Optional list of provider slugs to sync.
     * @return array<string, int>
     */
    public function sync(?array $providerFilter = null): array
    {
        /** @var array<string, array<string>> $config */
        $config = config('services.softswiss.providers', []);

        if ($providerFilter) {
            $config = array_intersect_key($config, array_flip($providerFilter));
        }

        $stats = [
            'providers' => 0,
            'games' => 0,
            'categories' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($config as $providerSlug => $allowedFeeGroups) {
            try {
                $this->syncProvider($providerSlug, $allowedFeeGroups, $stats);
            } catch (\Throwable $e) {
                Log::error("SoftSwiss: failed to sync provider {$providerSlug}", [
                    'error' => $e->getMessage(),
                ]);
                $stats['errors'][] = "{$providerSlug}: {$e->getMessage()}";
            }
        }

        return $stats;
    }

    /**
     * @param  array<string>  $allowedFeeGroups
     * @param  array<string, mixed>  $stats
     */
    protected function syncProvider(string $providerSlug, array $allowedFeeGroups, array &$stats): void
    {
        $games = $this->client->getProviderGames($providerSlug);

        Log::info('SoftSwiss: fetched '.count($games)." games for {$providerSlug}");

        // Filter by allowed fee groups and BR license
        $filtered = array_filter($games, function (array $game) use ($allowedFeeGroups) {
            $featureGroup = $game['feature_group'] ?? '';
            $licenses = $game['licenses'] ?? [];

            return in_array($featureGroup, $allowedFeeGroups, true)
                && in_array('BR', $licenses, true);
        });

        // Deduplicate by identifier, keeping the version with highest RTP
        $unique = [];
        foreach ($filtered as $game) {
            $id = $game['identifier'] ?? null;
            if (! $id) {
                $stats['skipped']++;

                continue;
            }

            $currentRtp = (float) ($game['payout'] ?? 0);
            $existingRtp = (float) ($unique[$id]['payout'] ?? 0);

            if (! isset($unique[$id]) || $currentRtp > $existingRtp) {
                $unique[$id] = $game;
            }
        }

        if (empty($unique)) {
            Log::info("SoftSwiss: no BR-licensed games for {$providerSlug} after filtering");

            return;
        }

        Log::info("SoftSwiss: {$providerSlug} has ".count($unique).' unique BR games');

        // Determine verticals from game categories
        $verticals = collect($unique)
            ->pluck('category')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Upsert Provider
        $this->upsertProvider($providerSlug, count($unique), $verticals);
        $stats['providers']++;

        // Upsert Slots and GameExtras in batch
        $this->upsertSlotsAndExtras($providerSlug, $unique);
        $stats['games'] += count($unique);

        // Upsert Category and sync slots
        $this->upsertCategory($providerSlug, $unique, $verticals);
        $stats['categories']++;
    }

    /**
     * @param  array<string>  $verticals
     */
    protected function upsertProvider(string $providerSlug, int $gameCount, array $verticals): void
    {
        Provider::upsert(
            [[
                'external_id' => $providerSlug,
                'name' => $providerSlug,
                'game_count' => $gameCount,
                'status' => 'active',
                'verticals' => json_encode($verticals),
            ]],
            ['external_id'],
            ['name', 'game_count', 'verticals']
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $games  Keyed by identifier.
     */
    protected function upsertSlotsAndExtras(string $providerSlug, array $games): void
    {
        $slotsToUpsert = [];
        $extrasToUpsert = [];

        foreach ($games as $identifier => $game) {
            $volatility = $game['volatility_rating'] ?? null;
            if ($volatility && ! in_array($volatility, Slot::VOLATILITY_TYPES, true)) {
                $volatility = null;
            }

            $slotsToUpsert[] = [
                'provider' => $game['provider'] ?? $providerSlug,
                'provider_game_id' => $identifier,
                'title' => $game['title'] ?? $identifier,
                'status' => 'active',
                'rtp' => $game['payout'] ?? null,
                'volatility' => $volatility,
                'tags' => json_encode([
                    'source' => 'softswiss',
                    'feature_group' => $game['feature_group'] ?? null,
                    'producer' => $game['producer'] ?? null,
                    'category' => $game['category'] ?? null,
                    'theme' => $game['theme'] ?? null,
                    'has_freespins' => $game['has_freespins'] ?? null,
                    'devices' => $game['devices'] ?? [],
                    'licenses' => $game['licenses'] ?? [],
                    'lines' => $game['lines'] ?? null,
                    'hit_rate' => $game['hit_rate'] ?? null,
                    'has_jackpot' => $game['has_jackpot'] ?? null,
                    'jackpot_type' => $game['jackpot_type'] ?? null,
                    'bonus_buy' => $game['bonus_buy'] ?? null,
                    'multiplier' => $game['multiplier'] ?? null,
                    'released_at' => $game['released_at'] ?? null,
                    'hd' => $game['hd'] ?? null,
                    'identifier2' => $game['identifier2'] ?? null,
                    'productId' => $providerSlug,
                ]),
            ];

            $extrasToUpsert[] = [
                'external_id' => $identifier,
                'rtp' => $game['payout'] ?? null,
                'volatility' => $volatility,
                'source' => 'softswiss',
            ];
        }

        if (! empty($slotsToUpsert)) {
            // Chunk to avoid exceeding query parameter limits
            foreach (array_chunk($slotsToUpsert, 500) as $chunk) {
                Slot::upsert(
                    $chunk,
                    ['provider', 'provider_game_id'],
                    ['title', 'status', 'rtp', 'volatility', 'tags']
                );
            }
        }

        if (! empty($extrasToUpsert)) {
            foreach (array_chunk($extrasToUpsert, 500) as $chunk) {
                GameExtra::upsert(
                    $chunk,
                    ['external_id'],
                    ['rtp', 'volatility', 'source']
                );
            }
        }
    }

    /**
     * Create or update a Category for this provider and sync its slots.
     *
     * @param  array<string, array<string, mixed>>  $games
     * @param  array<string>  $verticals
     */
    protected function upsertCategory(string $providerSlug, array $games, array $verticals): void
    {
        $slug = Str::slug($providerSlug);

        $category = Category::withoutGlobalScopes()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $providerSlug,
                'verticals' => ! empty($verticals) ? $verticals : ['slots'],
                'type' => Category::TYPE_GAME_LIST,
                'status' => 'active',
                'meta' => [
                    'source' => 'softswiss',
                    'provider' => $providerSlug,
                ],
            ]
        );

        // Fetch slot IDs for these games
        $identifiers = array_keys($games);

        $slots = Slot::whereIn('provider_game_id', $identifiers)
            ->select('id', 'provider_game_id')
            ->get();

        // Build sync payload with positions
        $syncPayload = [];
        $position = 0;
        foreach ($identifiers as $identifier) {
            $slot = $slots->firstWhere('provider_game_id', $identifier);
            if ($slot) {
                $syncPayload[$slot->id] = ['position' => $position++];
            }
        }

        if (! empty($syncPayload)) {
            $category->slots()->sync($syncPayload);
            Log::info("SoftSwiss: synced {$position} slots to category {$category->id} ({$providerSlug})");
        }
    }
}
