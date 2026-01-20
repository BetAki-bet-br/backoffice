<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Casino\CategoryRequest;
use App\Http\Requests\Casino\CategorySlotsSyncRequest;
use App\Models\Domain\Casino\Category;
use App\Models\Domain\Casino\PortalGame;
use App\Models\Domain\Casino\GameExtra;
use App\Models\Domain\Casino\Slot;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class CategoryController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/categories",
     *  tags={"Categories"},
     *  security={{"bearerAuth": {}}},
     *  summary="Listar categorias",
     *  @OA\Parameter(name="q", in="query", @OA\Schema(type="string")),
     *  @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","inactive"})),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function index(Request $request)
    {
        $vertical = $request->vertical;
        if ($vertical === 'slot') $vertical = 'slots';

        $q = Category::query()
            ->withCount('slots')
            ->when($request->filled('q'), fn($qq) =>
                $qq->where('name', 'ilike', '%'.$request->q.'%')
                   ->orWhere('slug', 'ilike', '%'.$request->q.'%'))
            ->when($request->filled('status'), fn($qq) =>
                $qq->where('status', $request->status))
            ->when($vertical, fn($qq) =>
                $qq->whereJsonContains('verticals', $vertical))
            ->when($request->filled('type'), fn($qq) =>
                $qq->where('type', $request->type))
            ->orderBy('name');

        if ($request->get('format') === 'sublevel') {
            $categories = $q->has('slots', '>', 1)->with('slots')->get();

            $externalIds = $categories
                ->flatMap(fn($cat) => $cat->slots->pluck('provider_game_id'))
                ->filter()
                ->unique()
                ->values();

            $portalGames = PortalGame::query()
                ->whereIn('external_id', $externalIds)
                ->get(['external_id', 'payload'])
                ->keyBy('external_id');

            $gameExtras = GameExtra::query()
                ->whereIn('external_id', $externalIds)
                ->get(['external_id', 'rtp', 'volatility', 'min_bet'])
                ->keyBy('external_id');

            $items = $categories->map(function (Category $cat) use ($portalGames, $gameExtras) {
                $gameMains = $cat->slots
                    ->map(function (Slot $slot) use ($portalGames, $gameExtras) {
                        $portal = $portalGames->get($slot->provider_game_id);
                        $payload = $portal?->payload;
                        
                        if ($payload) {
                            $extra = $gameExtras->get($slot->provider_game_id);
                            if ($extra) {
                                $payload['rtp'] = $extra->rtp;
                                $payload['volatility'] = \App\Support\Casino\GameExtraResolver::mapVolatility($extra->volatility);
                                $payload['minBet'] = $extra->min_bet;
                            }
                        }
                        
                        return $payload;
                    })
                    ->filter()
                    ->values();

                return [
                    'id' => $cat->id,
                    'parentId' => null,
                    'name' => $cat->name,
                    'verticals' => $cat->verticals,
                    'type' => $cat->type,
                    'gameName' => null,
                    'subLevel' => [],
                    'gameMains' => $gameMains,
                    'levelType' => 'category',
                ];
            });

            return response()->json($items);
        }

        return response()->json($q->cursorPaginate(20));
    }

    /** @OA\Get(
     *  path="/api/v1/categories/slots",
     *  tags={"Categories"},
     *  summary="Listar categorias de slots (sem jogos)",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function slots()
    {
        return $this->listByVertical('slots');
    }

    /** @OA\Get(
     *  path="/api/v1/categories/live",
     *  tags={"Categories"},
     *  summary="Listar categorias de live casino (sem jogos)",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function live()
    {
        return $this->listByVertical('live');
    }

    protected function listByVertical(string $vertical)
    {
        $categories = Category::query()
            ->whereJsonContains('verticals', $vertical)
            ->where('status', 'active')
            ->whereHas('slots', null, '>', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'verticals', 'type', 'meta']);

        return response()->json($categories);
    }

    /** @OA\Post(
     *  path="/api/v1/categories",
     *  tags={"Categories"},
     *  security={{"bearerAuth": {}}},
     *  summary="Criar categoria",
     *  @OA\RequestBody(required=true),
     *  @OA\Response(response=201, description="Criado")
     * ) */
    public function store(CategoryRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $category = \DB::transaction(fn() => Category::create($data));

        return response()->json($category, 201);
    }

    /** @OA\Get(
     *  path="/api/v1/categories/{id}",
     *  tags={"Categories"},
     *  security={{"bearerAuth": {}}},
     *  summary="Detalhar categoria",
     *  @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *  @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", default=500, description="Limite de jogos retornados")),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function show(Request $request, Category $category)
    {
        $limit = (int) $request->input('limit', 500);

        $category->load(['slots' => function ($query) use ($limit) {
            $query->orderBy('category_slot.position');
            if ($limit > 0) {
                $query->take($limit);
            }
        }]);

        $externalIds = $category->slots
            ->pluck('provider_game_id')
            ->filter()
            ->unique()
            ->values();

        if ($externalIds->isNotEmpty()) {
            $portalGames = PortalGame::query()
                ->whereIn('external_id', $externalIds)
                ->get(['external_id', 'payload'])
                ->keyBy('external_id');

            $gameExtras = GameExtra::query()
                ->whereIn('external_id', $externalIds)
                ->get(['external_id', 'rtp', 'volatility', 'min_bet'])
                ->keyBy('external_id');

            $category->slots->each(function (Slot $slot) use ($portalGames, $gameExtras) {
                $portal = $portalGames->get($slot->provider_game_id);
                $payload = $portal?->payload;

                if ($payload) {
                    $extra = $gameExtras->get($slot->provider_game_id);
                    if ($extra) {
                        $payload['rtp'] = $extra->rtp;
                        $payload['volatility'] = \App\Support\Casino\GameExtraResolver::mapVolatility($extra->volatility);
                        $payload['minBet'] = $extra->min_bet;
                    }
                }

                $slot->setAttribute('game_data', $payload);
            });
        }

        return response()->json($category);
    }

    /** @OA\Put(
     *  path="/api/v1/categories/{id}",
     *  tags={"Categories"},
     *  security={{"bearerAuth": {}}},
     *  summary="Atualizar categoria",
     *  @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *  @OA\RequestBody(required=true),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function update(CategoryRequest $request, Category $category)
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        \DB::transaction(fn() => $category->update($data));

        return response()->json($category->refresh());
    }

    /** @OA\Delete(
     *  path="/api/v1/categories/{id}",
     *  tags={"Categories"},
     *  security={{"bearerAuth": {}}},
     *  summary="Remover categoria",
     *  @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *  @OA\Response(response=204, description="Sem conteúdo")
     * ) */
    public function destroy(Category $category)
    {
        $category->delete();
        return response()->noContent();
    }

    /** @OA\Put(
     *  path="/api/v1/categories/{id}/slots",
     *  tags={"Categories"},
     *  security={{"bearerAuth": {}}},
     *  summary="Sincronizar slots da categoria (com posição por item)",
     *  @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *  @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *      @OA\Property(property="items", type="array",
     *        @OA\Items(
     *          @OA\Property(property="slot_id", type="integer", example=1),
     *          @OA\Property(property="position", type="integer", example=1)
     *        )
     *      )
     *    )
     *  ),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function syncSlots(CategorySlotsSyncRequest $request, Category $category)
    {
        $payload = collect($request->validated()['items'])
            ->keyBy('slot_id')
            ->map(fn($i) => ['position' => (int) ($i['position'] ?? 0)])
            ->all();

        \DB::transaction(function () use ($category, $payload) {
            $category->slots()->sync($payload);
        });

        return response()->json($category->load('slots'));
    }

    /** @OA\Post(
     *  path="/api/v1/categories/sync",
     *  tags={"Categories"},
     *  security={{"bearerAuth": {}}},
     *  summary="Sincronizar categorias da API externa",
     *  @OA\RequestBody(
     *    required=false,
     *    @OA\JsonContent(
     *      @OA\Property(property="portal_id", type="integer", example=1)
     *    )
     *  ),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function sync(Request $request)
    {
        $portalId = (int) ($request->input('portal_id') ?? config('services.base_api.portal_id', 5));
        $levelId = $request->input('level_id') ? (int) $request->input('level_id') : null;

        $stats = \App\Services\BaseApi\CategorySyncService::make()->syncFromLobby($portalId, $levelId);

        return response()->json($stats);
    }
}
