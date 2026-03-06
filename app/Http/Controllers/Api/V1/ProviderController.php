<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SyncProvidersJob;
use App\Models\Domain\Casino\GameExtra;
use App\Models\Domain\Casino\PortalGame;
use App\Models\Domain\Casino\Provider;
use App\Models\Domain\Casino\Slot;
use App\Enums\ActiveStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        $portalGames = $this->loadPortalGames(
            $games->pluck('provider_game_id')
        );

        $enrichedGames = $this->mapSlotsToGameMains($games, $portalGames);

        $data = $provider->toArray();
        $data['games'] = $enrichedGames;

        return response()->json($data);
    }

    /** @OA\Put(
     *  path="/api/v1/providers/{id}",
     *  tags={"Providers"},
     *  security={{"bearerAuth": {}}},
     *  summary="Atualizar provedor",
     *  @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *  @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *      @OA\Property(property="name", type="string"),
     *      @OA\Property(property="status", type="string", enum={"active","inactive"})
     *    )
     *  ),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function update(Request $request, Provider $provider)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,inactive',
            'verticals' => 'sometimes|array',
            'verticals.*' => 'sometimes|string',
        ]);

        $validated['updated_by'] = $request->user()->id;

        DB::transaction(function () use ($provider, $validated) {
            $provider->update($validated);
        });

        return response()->json($provider->refresh());
    }

    /** @OA\Post(
     *  path="/api/v1/providers/{id}/deactivate-slots",
     *  tags={"Providers"},
     *  security={{"bearerAuth": {}}},
     *  summary="Desativar em cascata todos os slots do provedor",
     *  @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function deactivateSlots(Provider $provider)
    {
        $affected = DB::transaction(function () use ($provider) {
            return Slot::where('provider', $provider->name)
                ->where('status', ActiveStatus::Active)
                ->update(['status' => ActiveStatus::Inactive]);
        });

        return response()->json(['affected' => $affected]);
    }

    /** @OA\Post(
     *  path="/api/v1/providers/{id}/activate-slots",
     *  tags={"Providers"},
     *  security={{"bearerAuth": {}}},
     *  summary="Reativar em cascata todos os slots do provedor",
     *  @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function activateSlots(Provider $provider)
    {
        $affected = DB::transaction(function () use ($provider) {
            return Slot::where('provider', $provider->name)
                ->where('status', ActiveStatus::Inactive)
                ->update(['status' => ActiveStatus::Active]);
        });

        return response()->json(['affected' => $affected]);
    }

    /** @OA\Put(
     *  path="/api/v1/providers/reorder",
     *  tags={"Providers"},
     *  security={{"bearerAuth": {}}},
     *  summary="Reordenar provedores",
     *  @OA\RequestBody(
     *    required=true,
     *    @OA\JsonContent(
     *      @OA\Property(
     *        property="providers",
     *        type="array",
     *        @OA\Items(
     *          @OA\Property(property="id", type="integer"),
     *          @OA\Property(property="position", type="integer")
     *        )
     *      )
     *    )
     *  ),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'providers' => 'required|array',
            'providers.*.id' => 'required|integer|exists:providers,id',
            'providers.*.position' => 'required|integer',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['providers'] as $item) {
                Provider::where('id', $item['id'])->update(['position' => $item['position']]);
            }
        });

        return response()->json(['message' => 'Providers reordered successfully.']);
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

        SyncProvidersJob::dispatch($portalId)->onConnection('database');

        return response()->json(['message' => 'Provider synchronization has been queued.']);
    }

    private function loadPortalGames($externalIds): Collection
    {
        $ids = collect($externalIds)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        // Carrega PortalGames e GameExtras
        $portalGames = PortalGame::query()
            ->whereIn('external_id', $ids)
            ->get(['external_id', 'payload'])
            ->keyBy('external_id');

        $gameExtras = \App\Models\Domain\Casino\GameExtra::query()
            ->whereIn('external_id', $ids)
            ->get(['external_id', 'rtp', 'volatility', 'min_bet'])
            ->keyBy('external_id');

        // Merge extra data into payload
        return $portalGames->map(function ($pg) use ($gameExtras) {
            $extra = $gameExtras->get($pg->external_id);
            $payload = $pg->payload ?? [];
            
            if ($extra) {
                $payload['rtp'] = $extra->rtp;
                $payload['volatility'] = \App\Support\Casino\GameExtraResolver::mapVolatility($extra->volatility);
                $payload['minBet'] = $extra->min_bet;
            }
            
            $pg->payload = $payload;
            return $pg;
        });
    }

    private function mapSlotsToGameMains($slots, Collection $portalGames): array
    {
        return collect($slots)
            ->map(function ($slot) use ($portalGames) {
                if (!$slot?->provider_game_id) {
                    return null;
                }
                return $portalGames->get($slot->provider_game_id)?->payload;
            })
            ->filter()
            ->values()
            ->all();
    }
}
