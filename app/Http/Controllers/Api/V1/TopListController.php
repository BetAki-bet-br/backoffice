<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Casino\TopListRequest;
use App\Http\Requests\Casino\TopListSlotsSyncRequest;
use App\Models\Domain\Casino\TopList;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class TopListController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/top-lists",
     *  tags={"TopLists"},
     *  security={{"bearerAuth": {}}},
     *  summary="Listar top lists",
     *  @OA\Parameter(name="q", in="query", @OA\Schema(type="string")),
     *  @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"draft","scheduled","published","archived"})),
     *  @OA\Parameter(name="vertical", in="query", @OA\Schema(type="string", enum={"slots","live"})),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function index(Request $request)
    {
        $q = TopList::query()
            ->when($request->filled('q'), fn($qq) =>
                $qq->where('title','ilike','%'.$request->q.'%')
                   ->orWhere('slug','ilike','%'.$request->q.'%'))
            ->when($request->filled('status'), fn($qq) =>
                $qq->where('status',$request->status))
            ->when($request->filled('vertical'), fn($qq) =>
                $qq->where('vertical',$request->vertical))
            ->orderBy('position')->orderByDesc('id');

        return response()->json($q->cursorPaginate(20));
    }

    /** @OA\Post(
     *  path="/api/v1/top-lists",
     *  tags={"TopLists"},
     *  security={{"bearerAuth": {}}},
     *  summary="Criar top list",
     *  @OA\RequestBody(required=true),
     *  @OA\Response(response=201, description="Criado")
     * ) */
    public function store(TopListRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $topList = \DB::transaction(fn() => TopList::create($data));

        return response()->json($topList, 201);
    }

    /** @OA\Get(
     *  path="/api/v1/top-lists/{id}",
     *  tags={"TopLists"},
     *  security={{"bearerAuth": {}}},
     *  summary="Detalhar top list",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function show(TopList $top_list)
    {
        return response()->json($top_list->load('slots'));
    }

    /** @OA\Put(
     *  path="/api/v1/top-lists/{id}",
     *  tags={"TopLists"},
     *  security={{"bearerAuth": {}}},
     *  summary="Atualizar top list",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function update(TopListRequest $request, TopList $top_list)
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        \DB::transaction(fn() => $top_list->update($data));

        return response()->json($top_list->refresh());
    }

    /** @OA\Delete(
     *  path="/api/v1/top-lists/{id}",
     *  tags={"TopLists"},
     *  security={{"bearerAuth": {}}},
     *  summary="Remover top list",
     *  @OA\Response(response=204, description="Sem conteúdo")
     * ) */
    public function destroy(TopList $top_list)
    {
        $top_list->delete();
        return response()->noContent();
    }

    /** @OA\Put(
     *  path="/api/v1/top-lists/{id}/slots",
     *  tags={"TopLists"},
     *  security={{"bearerAuth": {}}},
     *  summary="Sincronizar slots da top list (manual)",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function syncSlots(TopListSlotsSyncRequest $request, TopList $top_list)
    {
        abort_unless($top_list->type === 'manual', 400, 'Apenas listas manuais podem receber slots diretos.');

        $payload = collect($request->validated()['items'])
            ->keyBy('slot_id')
            ->map(fn($i) => ['position' => (int) ($i['position'] ?? 0)])
            ->all();

        \DB::transaction(fn() => $top_list->slots()->sync($payload));

        return response()->json($top_list->load('slots'));
    }

    public function publish(Request $request, TopList $top_list)
    {
        $top_list->update([
            'status'       => 'published',
            'published_by' => $request->user()->id,
            'valid_from'   => $top_list->valid_from ?? now(),
        ]);

        return response()->json($top_list->refresh());
    }
}