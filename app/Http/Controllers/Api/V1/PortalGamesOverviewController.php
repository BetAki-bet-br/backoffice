<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Domain\Casino\GameExtra;
use App\Models\Domain\Casino\PortalGame;
use App\Models\Domain\Casino\Slot;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class PortalGamesOverviewController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/portal-games/overview",
     *   tags={"Portal Games"},
     *   security={{"bearerAuth": {}}},
     *   summary="Listar portal_games com RTP/Volatilidade/Aposta mínima (cursor paginate)",
     *
     *   @OA\Parameter(name="portal_id", in="query", required=true, @OA\Schema(type="integer", example=1)),
     *   @OA\Parameter(name="q", in="query", @OA\Schema(type="string", example="Roulette")),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=50)),
     *
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        // Mantém o padrão do projeto: validação simples -> Handler padroniza erro.
        $data = $request->validate([
            'portal_id' => ['required', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $portalId = (int) $data['portal_id'];
        $perPage = (int) ($data['per_page'] ?? 50);

        $query = PortalGame::query()
            ->where('portal_id', $portalId)
            ->when(! empty($data['q']), function ($qq) use ($data) {
                $term = $data['q'];

                $qq->where(function ($w) use ($term) {
                    $w->where('name', 'ilike', "%{$term}%")
                        ->orWhere('external_id', 'ilike', "%{$term}%")
                        ->orWhere('product_name', 'ilike', "%{$term}%")
                        ->orWhere('supplier_name', 'ilike', "%{$term}%");
                });
            })
            ->orderByDesc('id');

        $paginator = $query->cursorPaginate($perPage);

        $items = $paginator->getCollection();

        $externalIds = $items
            ->pluck('external_id')
            ->filter()
            ->unique()
            ->values();

        // 1) extras (RTP/Volatilidade/Aposta mínima)
        $extrasByExternalId = GameExtra::query()
            ->whereIn('external_id', $externalIds)
            ->get()
            ->keyBy('external_id');

        // 2) slot (se já existir pré-cadastro)
        $slotsByExternalId = Slot::query()
            ->whereIn('provider_game_id', $externalIds)
            ->get()
            ->keyBy('provider_game_id');

        $items = $items->map(function (PortalGame $g) use ($extrasByExternalId, $slotsByExternalId) {
            $extra = $extrasByExternalId->get($g->external_id);
            $slot = $slotsByExternalId->get($g->external_id);

            // Campos “prioridade máxima”
            $g->setAttribute('rtp', $extra?->rtp);
            $g->setAttribute('volatility', $extra?->volatility);
            $g->setAttribute('min_bet', $extra?->min_bet);

            // GameMain do PDF: vem do payload (cast pra array no model)
            $g->setAttribute('game_main', $g->payload);

            // Ajuda o backoffice/front a saber se já existe slot cadastrado
            $g->setAttribute('slot', $slot ? $slot->only([
                'id', 'title', 'status', 'provider', 'provider_game_id',
            ]) : null);

            return $g;
        });

        $paginator->setCollection($items);

        return response()->json($paginator);
    }
}
