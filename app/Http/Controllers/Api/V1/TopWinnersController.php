<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Casino\TopWinnerBatchRequest;
use App\Http\Requests\Casino\TopWinnerResultsSyncRequest;
use App\Models\Domain\Casino\PortalGame;
use App\Models\Domain\Casino\TopWinner;
use App\Models\Domain\Casino\TopWinnerBatch;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class TopWinnersController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/winners/batches",
     *  tags={"TopWinners"},
     *  security={{"bearerAuth": {}}},
     *  summary="Listar lotes (Top Winners)",
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function index(Request $request)
    {
        $q = TopWinnerBatch::query()
            ->when($request->filled('q'), fn ($qq) => $qq->where('title', 'ilike', '%'.$request->q.'%'))
            ->when($request->filled('status'), fn ($qq) => $qq->where('status', $request->status))
            ->when($request->filled('vertical'), fn ($qq) => $qq->where('vertical', $request->vertical))
            ->orderByDesc('id');

        return response()->json($q->cursorPaginate(20));
    }

    /** @OA\Post(
     *  path="/api/v1/winners/batches",
     *  tags={"TopWinners"},
     *  security={{"bearerAuth": {}}},
     *  summary="Criar lote",
     *
     *  @OA\RequestBody(required=true),
     *
     *  @OA\Response(response=201, description="Criado")
     * ) */
    public function store(TopWinnerBatchRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $batch = \DB::transaction(fn () => TopWinnerBatch::create($data));

        return response()->json($batch, 201);
    }

    /** @OA\Get(
     *  path="/api/v1/winners/batches/{batch}",
     *  tags={"TopWinners"},
     *  security={{"bearerAuth": {}}},
     *  summary="Detalhar lote + vencedores",
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function show(TopWinnerBatch $batch)
    {
        $batch->load(['winners']);
        $gameMains = $this->resolveGameMains($batch->winners);

        return response()->json(array_merge($batch->toArray(), [
            'gameMains' => $gameMains,
        ]));
    }

    /** @OA\Put(
     *  path="/api/v1/winners/batches/{batch}",
     *  tags={"TopWinners"},
     *  security={{"bearerAuth": {}}},
     *  summary="Atualizar lote",
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function update(TopWinnerBatchRequest $request, TopWinnerBatch $batch)
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        \DB::transaction(fn () => $batch->update($data));

        return response()->json($batch->refresh());
    }

    /** @OA\Delete(
     *  path="/api/v1/winners/batches/{batch}",
     *  tags={"TopWinners"},
     *  security={{"bearerAuth": {}}},
     *  summary="Remover lote",
     *
     *  @OA\Response(response=204, description="Sem conteúdo")
     * ) */
    public function destroy(TopWinnerBatch $batch)
    {
        $batch->delete();

        return response()->noContent();
    }

    /** @OA\Put(
     *  path="/api/v1/winners/batches/{batch}/results",
     *  tags={"TopWinners"},
     *  security={{"bearerAuth": {}}},
     *  summary="Sincronizar vencedores (pós-apuração) para revisão",
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function syncResults(TopWinnerResultsSyncRequest $request, TopWinnerBatch $batch)
    {
        abort_unless(in_array($batch->status, [BatchStatus::Draft, BatchStatus::Review]), 400, 'Batch must be draft or review to sync results.');

        $items = collect($request->validated()['items'])
            ->sortBy('rank')->values();

        \DB::transaction(function () use ($batch, $items) {
            TopWinner::where('batch_id', $batch->id)->delete();

            foreach ($items as $i) {
                TopWinner::create([
                    'batch_id' => $batch->id,
                    'player_ref' => $i['player_ref'] ?? null, // NUNCA PII
                    'display_name' => $i['display_name'],       // já anonimizado
                    'country' => $i['country'] ?? null,

                    'rank' => (int) $i['rank'],
                    'position' => (int) ($i['position'] ?? $i['rank']),

                    'wins_count' => (int) ($i['wins_count'] ?? 0),
                    'prize_sum' => (float) ($i['prize_sum'] ?? 0),
                    'max_prize' => (float) ($i['max_prize'] ?? 0),
                    'avg_prize' => (float) ($i['avg_prize'] ?? 0),

                    'meta' => $i['meta'] ?? null,
                ]);
            }

            $batch->update(['status' => BatchStatus::Review]);
        });

        $batch->load('winners');
        $gameMains = $this->resolveGameMains($batch->winners);

        return response()->json(array_merge($batch->toArray(), [
            'gameMains' => $gameMains,
        ]));
    }

    /** @OA\Post(
     *  path="/api/v1/winners/batches/{batch}/publish",
     *  tags={"TopWinners"},
     *  security={{"bearerAuth": {}}},
     *  summary="Publicar lote revisado",
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function publish(Request $request, TopWinnerBatch $batch)
    {
        abort_unless($batch->status === BatchStatus::Review && $batch->winners()->exists(), 400, 'Batch must be in review with winners to publish.');

        $batch->update([
            'status' => BatchStatus::Published,
            'published_at' => now(),
            'published_by' => $request->user()->id,
        ]);

        $batch->refresh()->load('winners');
        $gameMains = $this->resolveGameMains($batch->winners);

        return response()->json(array_merge($batch->toArray(), [
            'gameMains' => $gameMains,
        ]));
    }

    /** @OA\Post(
     *  path="/api/v1/winners/batches/{batch}/archive",
     *  tags={"TopWinners"},
     *  security={{"bearerAuth": {}}},
     *  summary="Arquivar lote publicado",
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function archive(TopWinnerBatch $batch)
    {
        abort_unless($batch->status === BatchStatus::Published, 400, 'Only published batches can be archived.');

        $batch->update(['status' => BatchStatus::Archived]);

        return response()->json($batch->refresh());
    }

    private function resolveGameMains($winners): array
    {
        $externalIds = collect($winners)
            ->map(function (TopWinner $winner) {
                return $this->extractExternalId($winner->meta);
            })
            ->filter()
            ->unique()
            ->values();

        if ($externalIds->isEmpty()) {
            return [];
        }

        $portalGames = PortalGame::query()
            ->whereIn('external_id', $externalIds)
            ->get(['external_id', 'payload'])
            ->keyBy('external_id');

        return $externalIds
            ->map(fn ($id) => $portalGames->get($id)?->payload)
            ->filter()
            ->values()
            ->all();
    }

    private function extractExternalId(?array $meta): ?string
    {
        if (! $meta) {
            return null;
        }

        $keys = [
            'externalId',
            'external_id',
            'gameId',
            'game_id',
            'provider_game_id',
            'game.externalId',
            'game.external_id',
            'game.id',
        ];

        foreach ($keys as $key) {
            $value = data_get($meta, $key);
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }
}
