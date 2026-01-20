<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Casino\SlotRequest;
use App\Services\FileUploadService;
use App\Models\Domain\Casino\Slot;
use App\Models\Domain\Casino\GameExtra;
use App\Models\Domain\Casino\PortalGame;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class SlotController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/slots",
     *   tags={"Slots"},
     *   security={{"bearerAuth": {}}},
     *   summary="Listar slots",
     *   @OA\Parameter(name="q", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","inactive"})),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $q = Slot::query()
            ->when($request->filled('q'), fn($qq) =>
                $qq->where('title', 'ilike', '%'.$request->q.'%')
                   ->orWhere('provider', 'ilike', '%'.$request->q.'%')
                   ->orWhere('provider_game_id', 'ilike', '%'.$request->q.'%')
            )
            ->when($request->filled('status'), fn($qq) =>
                $qq->where('status', $request->status)
            )
            ->orderBy('position')
            ->orderByDesc('id');

        $paginator = $q->cursorPaginate(20);

        $items = $paginator->getCollection();

        $externalIds = $items
            ->pluck('provider_game_id')
            ->filter()
            ->unique()
            ->values();

        $extrasByExternalId = GameExtra::query()
            ->whereIn('external_id', $externalIds)
            ->get()
            ->keyBy('external_id');

        $portalGamesByExternalId = PortalGame::query()
            ->whereIn('external_id', $externalIds)
            ->get()
            ->keyBy('external_id');

        $items = $items->map(function (Slot $slot) use ($extrasByExternalId, $portalGamesByExternalId) {
            $extra = $extrasByExternalId->get($slot->provider_game_id);
            $portalGame = $portalGamesByExternalId->get($slot->provider_game_id);

            $slot->setAttribute('rtp', $extra?->rtp);
            $slot->setAttribute('volatility', $extra?->volatility);
            $slot->setAttribute('min_bet', $extra?->min_bet);
            $slot->setAttribute('gameTypeName', $portalGame?->payload['gameTypeName'] ?? null);

            return $slot;
        });

        $paginator->setCollection($items);

        return response()->json($paginator);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/slots/by-ids",
     *   tags={"Slots"},
     *   security={{"bearerAuth": {}}},
     *   summary="Listar slots por IDs externos",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="externalIds", type="array", @OA\Items(type="string"))
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function byIds(Request $request)
    {
        $request->validate([
            'externalIds' => 'required|array',
            'externalIds.*' => 'string',
        ]);

        $externalIds = $request->input('externalIds');

        $slots = Slot::query()
            ->whereIn('provider_game_id', $externalIds)
            ->get();

        $extrasByExternalId = GameExtra::query()
            ->whereIn('external_id', $externalIds)
            ->get()
            ->keyBy('external_id');

        $portalGamesByExternalId = PortalGame::query()
            ->whereIn('external_id', $externalIds)
            ->get()
            ->keyBy('external_id');

        $slots = $slots->map(function (Slot $slot) use ($extrasByExternalId, $portalGamesByExternalId) {
            $extra = $extrasByExternalId->get($slot->provider_game_id);
            $portalGame = $portalGamesByExternalId->get($slot->provider_game_id);

            $slot->setAttribute('rtp', $extra?->rtp);
            $slot->setAttribute('volatility', $extra?->volatility);
            $slot->setAttribute('min_bet', $extra?->min_bet);
            $slot->setAttribute('gameTypeName', $portalGame?->payload['gameTypeName'] ?? null);

            return $slot;
        });

        return response()->json($slots);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/slots",
     *   tags={"Slots"},
     *   security={{"bearerAuth": {}}},
     *   summary="Criar slot",
     *   @OA\RequestBody(required=true),
     *   @OA\Response(response=201, description="Criado")
     * )
     */
    public function store(SlotRequest $request)
    {
        $slot = \DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = $request->user()->id;

            // Handle file upload if cover_url file is provided
            if ($request->hasFile('cover_url')) {
                $data['cover_url'] = FileUploadService::uploadSlotImage($request->file('cover_url'));
            }

            return Slot::create($data);
        });

        return response()->json($slot, 201);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/slots/{id}",
     *   tags={"Slots"},
     *   security={{"bearerAuth": {}}},
     *   summary="Detalhar slot",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Slot $slot)
    {
        $extra = GameExtra::query()
            ->where('external_id', $slot->provider_game_id)
            ->first();

        $portalGame = PortalGame::query()
            ->where('external_id', $slot->provider_game_id)
            ->first();

        $slot->setAttribute('rtp', $extra?->rtp);
        $slot->setAttribute('volatility', $extra?->volatility);
        $slot->setAttribute('min_bet', $extra?->min_bet);
        $slot->setAttribute('gameTypeName', $portalGame?->payload['gameTypeName'] ?? null);

        return response()->json($slot);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/slots/{id}",
     *   tags={"Slots"},
     *   security={{"bearerAuth": {}}},
     *   summary="Atualizar slot",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function update(SlotRequest $request, Slot $slot)
    {
        $slot = \DB::transaction(function () use ($request, $slot) {
            $data = $request->validated();
            $data['updated_by'] = $request->user()->id;

            // Handle file upload if cover_url file is provided
            if ($request->hasFile('cover_url')) {
                // Delete old image if exists
                if ($slot->cover_url) {
                    FileUploadService::deleteImageByUrl($slot->cover_url);
                }
                // Upload new image
                $data['cover_url'] = FileUploadService::uploadSlotImage($request->file('cover_url'));
            }

            $slot->update($data);
            return $slot;
        });

        return response()->json($slot);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/slots/{id}",
     *   tags={"Slots"},
     *   security={{"bearerAuth": {}}},
     *   summary="Remover slot",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Sem conteúdo")
     * )
     */
    public function destroy(Slot $slot)
    {
        $slot->delete();
        return response()->noContent();
    }
}