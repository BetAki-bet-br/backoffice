<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Navigation\MenuRequest;
use App\Models\Domain\Navigation\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class MenuController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/menus",
     *  tags={"Menus"},
     *  security={{"bearerAuth": {}}},
     *  summary="Listar menus",
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function index(Request $request)
    {
        $isPublic = ! auth('sanctum')->check();

        $q = Menu::query()
            ->when($isPublic, fn ($qq) => $qq->where('status', ActiveStatus::Active))
            ->when(! $isPublic && $request->filled('status'), fn ($qq) => $qq->where('status', $request->status))
            ->when($request->filled('q'), fn ($qq) => $qq->where('name', 'ilike', '%'.$request->q.'%')
                ->orWhere('slug', 'ilike', '%'.$request->q.'%'))
            ->orderBy('position')->orderBy('id');

        return response()->json($q->cursorPaginate(20));
    }

    /** @OA\Put(
     *  path="/api/v1/menus/reorder",
     *  tags={"Menus"},
     *  security={{"bearerAuth": {}}},
     *  summary="Reordenar menus",
     *
     *  @OA\RequestBody(
     *    required=true,
     *
     *    @OA\JsonContent(
     *
     *      @OA\Property(property="items", type="array",
     *
     *        @OA\Items(
     *
     *          @OA\Property(property="id", type="integer", example=1),
     *          @OA\Property(property="position", type="integer", example=0)
     *        )
     *      )
     *    )
     *  ),
     *
     *  @OA\Response(response=204, description="No Content")
     * ) */
    public function reorder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.position' => 'required|integer',
        ]);

        $items = $request->input('items');

        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                Menu::where('id', $item['id'])->update(['position' => $item['position']]);
            }
        });

        return response()->noContent();
    }

    /** @OA\Post(
     *  path="/api/v1/menus",
     *  tags={"Menus"},
     *  security={{"bearerAuth": {}}},
     *  summary="Criar menu",
     *
     *  @OA\RequestBody(required=true),
     *
     *  @OA\Response(response=201, description="Criado")
     * ) */
    public function store(MenuRequest $request)
    {
        $menu = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = $request->user()->id;

            return Menu::create($data);
        });

        return response()->json($menu, 201);
    }

    /** @OA\Get(
     *  path="/api/v1/menus/{menu}",
     *  tags={"Menus"},
     *  security={{"bearerAuth": {}}},
     *  summary="Exibir menu com itens",
     *
     *  @OA\Parameter(name="menu", in="path", required=true, @OA\Schema(type="integer")),
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function show(Menu $menu)
    {
        $isPublic = ! auth('sanctum')->check();

        if ($isPublic && $menu->status !== ActiveStatus::Active) {
            abort(404);
        }

        $activeFilter = fn ($q) => $q->where('status', ActiveStatus::Active);

        $menu->load($isPublic
            ? ['items' => $activeFilter, 'items.children' => $activeFilter, 'items.children.children' => $activeFilter]
            : ['items.children.children']
        );

        return response()->json($menu);
    }

    /** @OA\Put(
     *  path="/api/v1/menus/{menu}",
     *  tags={"Menus"},
     *  security={{"bearerAuth": {}}},
     *  summary="Atualizar menu",
     *
     *  @OA\Parameter(name="menu", in="path", required=true, @OA\Schema(type="integer")),
     *
     *  @OA\RequestBody(required=true),
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function update(MenuRequest $request, Menu $menu)
    {
        DB::transaction(function () use ($request, $menu) {
            $data = $request->validated();
            $data['updated_by'] = $request->user()->id;
            $menu->update($data);
        });

        return response()->json($menu->refresh());
    }

    /** @OA\Delete(
     *  path="/api/v1/menus/{menu}",
     *  tags={"Menus"},
     *  security={{"bearerAuth": {}}},
     *  summary="Excluir menu",
     *
     *  @OA\Parameter(name="menu", in="path", required=true, @OA\Schema(type="integer")),
     *
     *  @OA\Response(response=204, description="No Content")
     * ) */
    public function destroy(Menu $menu)
    {
        $menu->delete();

        return response()->noContent();
    }
}
