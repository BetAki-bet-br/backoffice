<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Navigation\MenuItemRequest;
use App\Http\Requests\Navigation\MenuItemTreeSyncRequest;
use App\Models\Domain\Navigation\Menu;
use App\Models\Domain\Navigation\MenuItem;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class MenuItemController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/menus/{menu}/items",
     *  tags={"Menus"},
     *  security={{"bearerAuth": {}}},
     *  summary="Listar itens de um menu (raiz -> filhos)",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function index(Menu $menu)
    {
        $isPublic = !auth('sanctum')->check();
        $activeFilter = fn($q) => $q->where('status', ActiveStatus::Active);

        $menu->load($isPublic
            ? ['items' => $activeFilter, 'items.children' => $activeFilter, 'items.children.children' => $activeFilter]
            : ['items.children.children']
        );

        return response()->json($menu->items);
    }

    /** @OA\Post(
     *  path="/api/v1/menus/{menu}/items",
     *  tags={"Menus"},
     *  security={{"bearerAuth": {}}},
     *  summary="Criar item",
     *  @OA\RequestBody(required=true),
     *  @OA\Response(response=201, description="Criado")
     * ) */
    public function store(MenuItemRequest $request, Menu $menu)
    {
        $data = $request->validated();
        $data['menu_id']   = $menu->id;
        $data['created_by']= $request->user()->id;

        $item = MenuItem::create($data);

        return response()->json($item, 201);
    }

    public function show(Menu $menu, MenuItem $item)
    {
        abort_unless($item->menu_id === $menu->id, 404);

        $isPublic = !auth('sanctum')->check();

        if ($isPublic && $item->status !== ActiveStatus::Active) {
            abort(404);
        }

        $activeFilter = fn($q) => $q->where('status', ActiveStatus::Active);

        $item->load($isPublic
            ? ['children' => $activeFilter, 'children.children' => $activeFilter]
            : ['children.children']
        );

        return response()->json($item);
    }

    public function update(MenuItemRequest $request, Menu $menu, MenuItem $item)
    {
        abort_unless($item->menu_id === $menu->id, 404);

        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        if (!empty($data['parent_id'])) {
            $parent = MenuItem::where('menu_id', $menu->id)->findOrFail($data['parent_id']);
            $data['depth'] = ($parent->depth + 1);
        } else {
            $data['depth'] = 0;
        }

        $item->update($data);
        return response()->json($item->refresh());
    }

    public function destroy(Menu $menu, MenuItem $item)
    {
        abort_unless($item->menu_id === $menu->id, 404);
        $item->delete();
        return response()->noContent();
    }

    /** @OA\Put(
     *  path="/api/v1/menus/{menu}/items/tree",
     *  tags={"Menus"},
     *  security={{"bearerAuth": {}}},
     *  summary="Sincronizar árvore (reordenar e reparent)",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function syncTree(MenuItemTreeSyncRequest $request, Menu $menu)
    {
        $items = collect($request->validated()['items']);

        \DB::transaction(function () use ($items, $menu) {
            foreach ($items as $node) {
                MenuItem::where('menu_id', $menu->id)
                    ->where('id', $node['id'])
                    ->update([
                        'parent_id' => $node['parent_id'] ?? null,
                        'position'  => $node['position'],
                        'depth'     => $node['depth'],
                    ]);
            }
        });

        $menu->load(['items.children.children']);
        return response()->json($menu->items);
    }
}