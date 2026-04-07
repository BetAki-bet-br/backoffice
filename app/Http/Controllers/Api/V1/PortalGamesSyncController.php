<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BaseApi\PortalGamesSyncService;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class PortalGamesSyncController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/v1/portal-games/sync",
     *   tags={"Portal Games"},
     *   security={{"bearerAuth": {}}},
     *   summary="Sincronizar jogos da API base (portal_games) pelo PortalId",
     *
     *   @OA\RequestBody(
     *     required=false,
     *
     *     @OA\JsonContent(
     *
     *       @OA\Property(property="portal_id", type="integer", example=1)
     *     )
     *   ),
     *
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function sync(Request $request)
    {
        $portalId = (int) ($request->input('portal_id') ?? config('services.base_api.portal_id', 1));

        $result = PortalGamesSyncService::make()->syncPortal($portalId);

        return response()->json([
            'portal_id' => $result['portal_id'],
            'fetched' => $result['fetched'],
            'upserted' => $result['upserted'],
        ]);
    }
}
