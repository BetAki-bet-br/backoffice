<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Domain\Casino\PortalGame;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class PortalGamesPublicController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/public/portal-games/providers",
     *   tags={"Portal Games Public"},
     *   summary="Get providers with game count for a portal",
     *
     *   @OA\Parameter(name="portal_id", in="query", required=true, @OA\Schema(type="integer")),
     *
     *   @OA\Response(response=200, description="List of providers")
     * )
     */
    public function getProviders(Request $request)
    {
        $request->validate([
            'portal_id' => 'required|integer',
        ]);

        $portalId = (int) $request->portal_id;

        $games = PortalGame::query()
            ->where('portal_id', $portalId)
            ->get(['payload']);

        $providersMap = [];

        foreach ($games as $game) {
            $payload = $game->payload;
            if (empty($payload) || ! is_array($payload)) {
                continue;
            }

            $productId = $payload['productId'] ?? null;
            $productName = $payload['productName'] ?? null;

            if ($productId && $productName) {
                if (! isset($providersMap[$productId])) {
                    $providersMap[$productId] = [
                        'id' => $productId,
                        'name' => $productName,
                        'gameCount' => 0,
                    ];
                }
                $providersMap[$productId]['gameCount']++;
            }
        }

        $providers = array_values($providersMap);

        usort($providers, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return response()->json($providers);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/public/portal-games/by-provider",
     *   tags={"Portal Games Public"},
     *   summary="Get games by provider for a portal",
     *
     *   @OA\Parameter(name="portal_id", in="query", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="provider_id", in="query", required=true, @OA\Schema(type="integer")),
     *
     *   @OA\Response(response=200, description="List of games")
     * )
     */
    public function getGamesByProvider(Request $request)
    {
        $request->validate([
            'portal_id' => 'required|integer',
            'provider_id' => 'required|integer',
        ]);

        $portalId = (int) $request->portal_id;
        $providerId = (int) $request->provider_id;

        $games = PortalGame::query()
            ->where('portal_id', $portalId)
            ->where('payload->productId', $providerId)
            ->orderBy('name')
            ->get();

        $data = $games->map(function ($game) {
            return $game->payload;
        });

        return response()->json($data);
    }
}
