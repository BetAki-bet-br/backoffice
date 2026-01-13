<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Domain\Casino\AwardedGameBatch;
use App\Models\Domain\Casino\Category;
use App\Models\Domain\Casino\TopList;
use App\Models\Domain\Casino\TopWinnerBatch;
use App\Models\Domain\Casino\PortalGame;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use OpenApi\Annotations as OA;

class LobbyLayoutController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/lobbies/casino",
     *  tags={"Lobbies"},
     *  summary="Layout do lobby de cassino (slots)",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function casino(Request $request)
    {
        return response()->json($this->buildLayout('slots'));
    }

    /** @OA\Get(
     *  path="/api/v1/lobbies/live",
     *  tags={"Lobbies"},
     *  summary="Layout do lobby de cassino ao vivo",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function live(Request $request)
    {
        return response()->json($this->buildLayout('live'));
    }

    private function buildLayout(string $vertical): array
    {
        $sectionsConfig = $this->resolveSectionsConfig($vertical);

        if (empty($sectionsConfig)) {
            return [
                'sections' => $this->buildDefaultSections($vertical),
            ];
        }

        return [
            'sections' => $this->buildConfiguredSections($sectionsConfig, $vertical),
        ];
    }

    private function resolveSectionsConfig(string $vertical): array
    {
        $aliases = $vertical === 'slots' ? ['casino'] : [];
        $keys = array_merge(["lobby.layout.{$vertical}"], array_map(fn($alias) => "lobby.layout.{$alias}", $aliases));

        $setting = Setting::query()
            ->whereIn('key', $keys)
            ->orderByRaw("CASE key WHEN ? THEN 0 ELSE 1 END", [$keys[0]])
            ->first();

        if (!$setting) {
            return [];
        }

        $value = $setting->value;
        if (is_array($value) && array_key_exists('value', $value) && is_array($value['value'])) {
            $value = $value['value'];
        }

        if (is_array($value) && array_key_exists('sections', $value) && is_array($value['sections'])) {
            return $value['sections'];
        }

        return is_array($value) ? $value : [];
    }

    private function buildDefaultSections(string $vertical): array
    {
        $categories = Category::query()
            ->where('status', 'active')
            ->forVertical($vertical)
            ->where(function ($q) {
                $q->where('type', '!=', 'game-list')
                    ->orWhereHas('slots', null, '>', 1);
            })
            ->with(['slots' => fn($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $portalGames = $this->loadPortalGames(
            $categories->flatMap(fn(Category $cat) => $cat->slots->pluck('provider_game_id'))
        );

        $categoriesKeyed = $categories->keyBy('id');

        return $categories
            ->map(function (Category $category, int $index) use ($portalGames, $vertical, $categoriesKeyed) {
                return $this->buildSection(['id' => $category->id], $vertical, $categoriesKeyed, $portalGames, $index);
            })
            ->filter()
            ->values()
            ->all();
    }

    private function buildConfiguredSections(array $sectionsConfig, string $vertical): array
    {
        $sectionsConfig = collect($sectionsConfig)
            ->filter(fn($item) => is_array($item))
            ->values();

        $categoryIds = $sectionsConfig
            ->map(fn($section) => $this->resolveCategoryId($section))
            ->filter()
            ->unique()
            ->values();

        $categories = $categoryIds->isEmpty()
            ? collect()
            : Category::query()
                ->whereIn('id', $categoryIds)
                ->where('status', 'active')
                ->forVertical($vertical)
                ->with(['slots' => fn($q) => $q->where('status', 'active')])
                ->get()
                ->keyBy('id');

        $portalGames = $this->loadPortalGames(
            $categories->flatMap(fn(Category $cat) => $cat->slots->pluck('provider_game_id'))
        );

        $sections = $sectionsConfig->map(function (array $section, int $index) use ($categories, $portalGames, $vertical) {
            return $this->buildSection($section, $vertical, $categories, $portalGames, $index);
        })
        ->filter()
        ->sortBy('order')
        ->values();

        return $sections->all();
    }

    private function buildSection(
        array $section,
        string $vertical,
        Collection $categories,
        Collection $portalGames,
        int $index
    ): ?array {
        $categoryId = $this->resolveCategoryId($section);
        $category = $categoryId ? $categories->get($categoryId) : null;

        $type = $section['type'] ?? ($category?->type ?? 'game-list');
        $title = $section['title'] ?? ($category?->name ?? null);
        $order = (int) ($section['order'] ?? $index);
        $metadata = $section['metadata'] ?? [];

        return match ($type) {
            'game-list' => $this->buildGameListSection($section, $categories, $portalGames, $order, $title, $metadata),
            'top-10-list' => $this->buildTopListSection($section, $vertical, $order, $title, $metadata),
            'mais-premiados' => $this->buildAwardedSection($section, $vertical, $order, $title, $metadata),
            'winners-list' => $this->buildWinnersSection($section, $vertical, $order, $title, $metadata),
            default => $this->buildGenericSection($section, $order, $title, $metadata),
        };
    }

    private function buildGameListSection(
        array $section,
        Collection $categories,
        Collection $portalGames,
        int $order,
        ?string $title,
        array $metadata
    ): ?array {
        $categoryId = $this->resolveCategoryId($section);
        if (!$categoryId) {
            return null;
        }

        $category = $categories->get($categoryId);
        if (!$category) {
            return null;
        }

        if ($category->type === 'game-list' && $category->slots->count() <= 1) {
            return null;
        }

        $games = $this->mapSlotsToGameMains($category->slots, $portalGames);
        $games = $this->applyDisplayCount($games, $metadata);

        return $this->makeCategorySection($category, $portalGames, [
            'order' => $order,
            'title' => $title,
            'metadata' => array_merge(['categoryId' => $category->id], $metadata),
            'games' => $games,
        ]);
    }

    private function buildTopListSection(
        array $section,
        string $vertical,
        int $order,
        ?string $title,
        array $metadata
    ): ?array {
        $topListId = $this->extractNumericId($section, ['topListId', 'top_list_id']);
        $topList = $topListId
            ? TopList::query()->whereKey($topListId)->first()
            : TopList::query()
                ->where('status', 'published')
                ->where('vertical', $vertical)
                ->activeWindow()
                ->orderBy('position')
                ->orderByDesc('id')
                ->first();

        if (!$topList) {
            return null;
        }

        $topList->load(['slots' => fn($q) => $q->where('status', 'active')]);

        $games = $this->resolveGamesFromSlots($topList->slots);
        $games = $this->applyDisplayCount($games, $metadata);

        return $this->makeGenericSection($section, [
            'id' => $section['id'] ?? $topList->id,
            'type' => 'top-10-list',
            'title' => $title ?? $topList->title,
            'order' => $order,
            'games' => $games,
            'metadata' => array_merge(['topListId' => $topList->id], $metadata),
        ]);
    }

    private function buildAwardedSection(
        array $section,
        string $vertical,
        int $order,
        ?string $title,
        array $metadata
    ): ?array {
        $batchId = $this->extractNumericId($section, ['awardedBatchId', 'awarded_batch_id', 'batchId', 'batch_id']);
        $batch = $batchId
            ? AwardedGameBatch::query()->whereKey($batchId)->first()
            : AwardedGameBatch::query()
                ->where('status', 'published')
                ->where('vertical', $vertical)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->first();

        if (!$batch) {
            return null;
        }

        $batch->load(['results.slot' => fn($q) => $q->where('status', 'active')]);

        $games = $this->resolveGamesFromResults($batch->results);
        $games = $this->applyDisplayCount($games, $metadata);

        return $this->makeGenericSection($section, [
            'id' => $section['id'] ?? $batch->id,
            'type' => 'mais-premiados',
            'title' => $title ?? $batch->title,
            'order' => $order,
            'games' => $games,
            'metadata' => array_merge(['batchId' => $batch->id], $metadata),
        ]);
    }

    private function buildWinnersSection(
        array $section,
        string $vertical,
        int $order,
        ?string $title,
        array $metadata
    ): ?array {
        $batchId = $this->extractNumericId($section, ['winnersBatchId', 'winners_batch_id', 'batchId', 'batch_id']);
        $batch = $batchId
            ? TopWinnerBatch::query()->whereKey($batchId)->first()
            : TopWinnerBatch::query()
                ->where('status', 'published')
                ->where('vertical', $vertical)
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->first();

        return $this->makeGenericSection($section, [
            'id' => $section['id'] ?? ($batch?->id ?? 'winners'),
            'type' => 'winners-list',
            'title' => $title ?? ($batch?->title ?? 'Vencedores'),
            'order' => $order,
            'metadata' => array_merge(['batchId' => $batch?->id], $metadata),
        ]);
    }

    private function buildGenericSection(array $section, int $order, ?string $title, array $metadata): array
    {
        return $this->makeGenericSection($section, [
            'id' => $section['id'] ?? ($section['type'] ?? 'section'),
            'type' => $section['type'] ?? 'section',
            'title' => $title ?? ($section['title'] ?? ''),
            'order' => $order,
            'metadata' => $metadata,
        ]);
    }

    private function makeCategorySection(Category $category, Collection $portalGames, array $overrides): array
    {
        $games = $overrides['games'] ?? $this->mapSlotsToGameMains($category->slots, $portalGames);

        $gameCount = $category->slots->count();
        $games = array_slice($games, 0, 10);

        return $this->makeGenericSection([], [
            'id' => $category->id,
            'type' => $overrides['type'] ?? ($category->type ?? ($category->meta['type'] ?? ($category->meta['original_type'] ?? 'game-list'))),
            'title' => $overrides['title'] ?? $category->name,
            'order' => (int) ($overrides['order'] ?? 0),
            'games' => $games,
            'gameCount' => $gameCount,
            'metadata' => $overrides['metadata'] ?? ['categoryId' => $category->id],
        ]);
    }

    private function makeGenericSection(array $section, array $payload): array
    {
        $result = [
            'id' => $payload['id'],
            'type' => $payload['type'],
            'title' => $payload['title'],
            'order' => $payload['order'],
        ];

        if (isset($payload['gameCount'])) {
            $result['gameCount'] = $payload['gameCount'];
        }

        if (!empty($payload['games'])) {
            $result['games'] = $payload['games'];
        }

        if (!empty($payload['providers'])) {
            $result['providers'] = $payload['providers'];
        }

        if (!empty($payload['metadata'])) {
            $result['metadata'] = $payload['metadata'];
        }

        return $result;
    }

    private function resolveCategoryId(array $section): ?int
    {
        return $this->extractNumericId($section, ['categoryId', 'category_id', 'id']);
    }

    private function extractNumericId(array $section, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $section)) {
                continue;
            }
            $value = $section[$key];
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function loadPortalGames($externalIds): Collection
    {
        $ids = collect($externalIds)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        // Carrega PortalGames e GameExtras
        $portalGames = PortalGame::query()
            ->whereIn('external_id', $ids)
            ->get(['external_id', 'payload'])
            ->keyBy('external_id');

        $gameExtras = \App\Models\Domain\Casino\GameExtra::query()
            ->whereIn('external_id', $ids)
            ->get(['external_id', 'rtp', 'volatility', 'min_bet'])
            ->keyBy('external_id');

        // Merge extra data into payload
        return $portalGames->map(function ($pg) use ($gameExtras) {
            $extra = $gameExtras->get($pg->external_id);
            $payload = $pg->payload ?? [];
            
            if ($extra) {
                $payload['rtp'] = $extra->rtp;
                $payload['volatility'] = \App\Support\Casino\GameExtraResolver::mapVolatility($extra->volatility);
                $payload['minBet'] = $extra->min_bet;
            }
            
            $pg->payload = $payload;
            return $pg;
        });
    }

    private function mapSlotsToGameMains($slots, Collection $portalGames): array
    {
        return collect($slots)
            ->map(function ($slot) use ($portalGames) {
                if (!$slot?->provider_game_id) {
                    return null;
                }
                return $portalGames->get($slot->provider_game_id)?->payload;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolveGamesFromSlots(Collection $slots): array
    {
        $portalGames = $this->loadPortalGames($slots->pluck('provider_game_id'));
        return $this->mapSlotsToGameMains($slots, $portalGames);
    }

    private function resolveGamesFromResults(Collection $results): array
    {
        $externalIds = $results
            ->map(fn($result) => $result->slot?->provider_game_id)
            ->filter()
            ->unique()
            ->values();

        $portalGames = $this->loadPortalGames($externalIds);

        return $results
            ->map(function ($result) use ($portalGames) {
                $externalId = $result->slot?->provider_game_id;
                if (!$externalId) {
                    return null;
                }
                return $portalGames->get($externalId)?->payload;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function applyDisplayCount(array $games, array $metadata): array
    {
        $displayCount = $metadata['displayCount'] ?? $metadata['display_count'] ?? null;
        if ($displayCount === null || !is_numeric($displayCount)) {
            return $games;
        }

        return array_slice($games, 0, (int) $displayCount);
    }
}
