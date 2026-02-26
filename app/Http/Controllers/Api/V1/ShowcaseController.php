<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Casino\ShowcaseRequest;
use App\Http\Requests\Casino\ShowcaseSlotsSyncRequest;
use App\Models\Domain\Casino\Showcase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class ShowcaseController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/showcases",
     *  tags={"Showcases"},
     *  security={{"bearerAuth": {}}},
     *  summary="Listar vitrines",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function index(Request $request)
    {
        $q = Showcase::query()
            ->when($request->filled('q'), fn($qq) =>
                $qq->where('title','ilike','%'.$request->q.'%')
                   ->orWhere('slug','ilike','%'.$request->q.'%'))
            ->when($request->filled('status'), fn($qq) =>
                $qq->where('status', $request->status))
            ->orderBy('position');

        return response()->json($q->cursorPaginate(20));
    }

    /** @OA\Post(
     *  path="/api/v1/showcases",
     *  tags={"Showcases"},
     *  security={{"bearerAuth": {}}},
     *  summary="Criar vitrine",
     *  @OA\RequestBody(required=true),
     *  @OA\Response(response=201, description="Criado")
     * ) */
    public function store(ShowcaseRequest $request)
    {
        $showcase = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = $request->user()->id;
            return Showcase::create($data);
        });
        return response()->json($showcase, 201);
    }

    public function show(Showcase $showcase)
    {
        return response()->json($showcase->load('slots'));
    }

    public function update(ShowcaseRequest $request, Showcase $showcase)
    {
        DB::transaction(function () use ($request, $showcase) {
            $data = $request->validated();
            $data['updated_by'] = $request->user()->id;
            $showcase->update($data);
        });
        return response()->json($showcase->refresh());
    }

    public function destroy(Showcase $showcase)
    {
        $showcase->delete();
        return response()->noContent();
    }

    /** @OA\Put(
     *  path="/api/v1/showcases/{id}/slots",
     *  tags={"Showcases"},
     *  security={{"bearerAuth": {}}},
     *  summary="Sincronizar slots da vitrine (manual)",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function syncSlots(ShowcaseSlotsSyncRequest $request, Showcase $showcase)
    {
        abort_unless($showcase->type === 'manual', 400, 'Apenas vitrines manuais podem ter slots diretos.');

        $payload = collect($request->validated()['items'])
            ->keyBy('slot_id')
            ->map(fn($i) => ['position' => (int) ($i['position'] ?? 0)])
            ->all();

        DB::transaction(function () use ($showcase, $payload) {
            $showcase->slots()->sync($payload);
        });

        return response()->json($showcase->load('slots'));
    }
}