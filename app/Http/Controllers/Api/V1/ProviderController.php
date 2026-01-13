<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Domain\Casino\Provider;
use App\Models\Domain\Casino\Slot;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class ProviderController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/providers",
     *  tags={"Providers"},
     *  security={{"bearerAuth": {}}},
     *  summary="Listar provedores",
     *  @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"active","inactive"})),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function index(Request $request)
    {
        $query = Provider::query()
            ->orderBy('name');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('vertical')) {
            $vertical = $request->input('vertical');

            // Find provider IDs (external_id) that have games in this vertical
            // We look for active Slots in Categories that have the requested vertical
            $providerIds = Slot::where('status', 'active')
                ->whereHas('categories', function ($q) use ($vertical) {
                    $q->whereJsonContains('verticals', $vertical);
                })
                ->get()
                ->map(function ($slot) {
                    // tags is an array due to cast
                    return $slot->tags['productId'] ?? null;
                })
                ->filter()
                ->unique()
                ->values();

            $query->whereIn('external_id', $providerIds);
        }

        return response()->json($query->get());
    }

    /** @OA\Get(
     *  path="/api/v1/providers/{id}",
     *  tags={"Providers"},
     *  security={{"bearerAuth": {}}},
     *  summary="Detalhar provedor e seus jogos",
     *  @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function show(Provider $provider)
    {
        // Busca os jogos vinculados via tags->productId
        // O external_id do provider corresponde ao productId nos tags do slot
        $games = Slot::whereJsonContains('tags->productId', (int)$provider->external_id)
            ->where('status', 'active')
            ->orderBy('title')
            ->get();

        $data = $provider->toArray();
        $data['games'] = $games;

        return response()->json($data);
    }

    /** @OA\Post(
     *  path="/api/v1/providers/sync",
     *  tags={"Providers"},
     *  security={{"bearerAuth": {}}},
     *  summary="Sincronizar provedores da API externa",
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
        $portalId = (int) ($request->input('portal_id') ?? config('services.base_api.portal_id', 1));

        $stats = \App\Services\BaseApi\ProviderSyncService::make()->syncPortal($portalId);

        return response()->json($stats);
    }
}
