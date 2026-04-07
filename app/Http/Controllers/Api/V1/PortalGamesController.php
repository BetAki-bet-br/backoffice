<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Domain\Casino\GameExtra;
use App\Models\Domain\Casino\PortalGame;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class PortalGamesController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/portal-games",
     *   tags={"Portal Games"},
     *   security={{"bearerAuth": {}}},
     *   summary="Listar jogos do portal (portal_games) enriquecidos com RTP/Volatilidade/Aposta mínima",
     *
     *   @OA\Parameter(name="portal_id", in="query", required=true, @OA\Schema(type="integer", example=1)),
     *   @OA\Parameter(name="q", in="query", @OA\Schema(type="string", example="Roulette")),
     *
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $request->validate([
            'portal_id' => ['required', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $portalId = (int) $request->portal_id;

        $q = PortalGame::query()
            ->where('portal_id', $portalId)
            ->when($request->filled('q'), function ($qq) use ($request) {
                $term = $request->q;
                $qq->where(function ($w) use ($term) {
                    $w->where('name', 'ilike', "%{$term}%")
                        ->orWhere('external_id', 'ilike', "%{$term}%")
                        ->orWhere('product_name', 'ilike', "%{$term}%")
                        ->orWhere('supplier_name', 'ilike', "%{$term}%");
                });
            })
            ->orderByDesc('id');

        $paginator = $q->cursorPaginate(50);

        $items = $paginator->getCollection();

        $externalIds = $items->pluck('external_id')->filter()->unique()->values();

        $extrasByExternalId = GameExtra::query()
            ->whereIn('external_id', $externalIds)
            ->get()
            ->keyBy('external_id');

        $items = $items->map(function (PortalGame $g) use ($extrasByExternalId) {
            $extra = $extrasByExternalId->get($g->external_id);

            // payload é o GameMain (obj do jogo)
            $g->setAttribute('rtp', $extra?->rtp);
            $g->setAttribute('volatility', $extra?->volatility);
            $g->setAttribute('min_bet', $extra?->min_bet);

            return $g;
        });

        $paginator->setCollection($items);

        return response()->json($paginator);
    }
}
