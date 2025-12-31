<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Domain\Casino\GameExtra;
use App\Models\Domain\Casino\PortalGame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class GameExtraOverviewController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/game-extras/overview",
     *   tags={"Game Extras"},
     *   security={{"bearerAuth": {}}},
     *   summary="Listar Game Extras com status de match na base (portal_games)",
     *   @OA\Parameter(name="portal_id", in="query", @OA\Schema(type="integer", example=1)),
     *   @OA\Parameter(name="q", in="query", @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $portalId = (int) ($request->get('portal_id') ?? config('services.base_api.portal_id', 1));

        $query = PortalGame::query()
            ->where('portal_games.portal_id', $portalId)
            ->leftJoin('game_extras', 'game_extras.external_id', '=', 'portal_games.external_id')
            ->select([
                'portal_games.id',
                'portal_games.portal_id',
                'portal_games.external_id',
                DB::raw('portal_games.name as base_name'),
                DB::raw('portal_games.supplier_name as base_supplier_name'),
                DB::raw('portal_games.product_name as base_product_name'),
                DB::raw('CASE WHEN game_extras.id IS NULL THEN false ELSE true END as has_extra'),
                DB::raw('game_extras.rtp as rtp'),
                DB::raw('game_extras.volatility as volatility'),
                DB::raw('game_extras.min_bet as min_bet'),
                DB::raw('game_extras.source as source'),
            ])
            ->when($request->filled('q'), fn($q) =>
                $q->where('portal_games.external_id', 'like', '%'.$request->q.'%')
                ->orWhere('portal_games.name', 'ilike', '%'.$request->q.'%')
            )
            ->orderByDesc('portal_games.id');

        return response()->json($query->paginate(20));
    }
}