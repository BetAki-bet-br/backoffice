<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Navigation\MenuRequest;
use App\Models\Domain\Navigation\Menu;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class MenuController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/menus",
     *  tags={"Menus"},
     *  security={{"bearerAuth": {}}},
     *  summary="Listar menus",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function index(Request $request)
    {
        $q = Menu::query()
            ->when($request->filled('q'), fn($qq) =>
                $qq->where('name','ilike','%'.$request->q.'%')
                   ->orWhere('slug','ilike','%'.$request->q.'%'))
            ->when($request->filled('status'), fn($qq) =>
                $qq->where('status',$request->status))
            ->orderBy('position')->orderBy('id');

        return response()->json($q->cursorPaginate(20));
    }

    /** @OA\Post(
     *  path="/api/v1/menus",
     *  tags={"Menus"},
     *  security={{"bearerAuth": {}}},
     *  summary="Criar menu",
     *  @OA\RequestBody(required=true),
     *  @OA\Response(response=201, description="Criado")
     * ) */
    public function store(MenuRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $menu = Menu::create($data);

        return response()->json($menu, 201);
    }

    public function show(Menu $menu)
    {
        $menu->load(['items.children.children']);
        return response()->json($menu);
    }

    public function update(MenuRequest $request, Menu $menu)
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;
        $menu->update($data);

        return response()->json($menu->refresh());
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        return response()->noContent();
    }
}