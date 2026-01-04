<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Casino\CategoryRequest;
use App\Http\Requests\Casino\CategorySlotsSyncRequest;
use App\Models\Domain\Casino\Category;
use App\Models\Domain\Casino\PortalGame;
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
        $q = Category::query()
            ->when($request->filled('q'), fn($qq) =>
                $qq->where('name', 'ilike', '%'.$request->q.'%')
                   ->orWhere('slug', 'ilike', '%'.$request->q.'%'))
            ->when($request->filled('status'), fn($qq) =>
                $qq->where('status', $request->status))
            ->orderBy('position')
            ->orderBy('name');

        if ($request->get('format') === 'sublevel') {
            $categories = $q->with('slots')->get();

            $externalIds = $categories
                ->flatMap(fn($cat) => $cat->slots->pluck('provider_game_id'))
                ->filter()
                ->unique()
                ->values();

            $portalGames = PortalGame::query()
                ->whereIn('external_id', $externalIds)
                ->get(['external_id', 'payload'])
                ->keyBy('external_id');

            $items = $categories->map(function (Category $cat) use ($portalGames) {
                $gameMains = $cat->slots
                    ->map(function (Slot $slot) use ($portalGames) {
                        $portal = $portalGames->get($slot->provider_game_id);
                        return $portal?->payload;
                    })
                    ->filter()
                    ->values();

                return [
                    'id' => $cat->id,
                    'parentId' => null,
                    'name' => $cat->name,
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
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function show(Category $category)
    {
        return response()->json($category->load(['slots']));
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
}
