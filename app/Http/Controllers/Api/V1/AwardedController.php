<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Casino\AwardedBatchRequest;
use App\Http\Requests\Casino\AwardedResultsSyncRequest;
use App\Models\Domain\Casino\AwardedGameBatch;
use App\Models\Domain\Casino\AwardedGame;
use App\Enums\BatchStatus;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class AwardedController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/awards/batches",
     *  tags={"Awards"},
     *  security={{"bearerAuth": {}}},
     *  summary="Listar lotes de apuração",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function index(Request $request)
    {
        $q = AwardedGameBatch::query()
            ->when($request->filled('q'), fn($qq) =>
                $qq->where('title','ilike','%'.$request->q.'%'))
            ->when($request->filled('status'), fn($qq) =>
                $qq->where('status', $request->status))
            ->when($request->filled('vertical'), fn($qq) =>
                $qq->where('vertical', $request->vertical))
            ->orderByDesc('id');

        return response()->json($q->cursorPaginate(20));
    }

    /** @OA\Post(
     *  path="/api/v1/awards/batches",
     *  tags={"Awards"},
     *  security={{"bearerAuth": {}}},
     *  summary="Criar lote",
     *  @OA\RequestBody(required=true),
     *  @OA\Response(response=201, description="Criado")
     * ) */
    public function store(AwardedBatchRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $batch = \DB::transaction(fn() => AwardedGameBatch::create($data));

        return response()->json($batch, 201);
    }

    /** @OA\Get(
     *  path="/api/v1/awards/batches/{id}",
     *  tags={"Awards"},
     *  security={{"bearerAuth": {}}},
     *  summary="Detalhar lote + resultados",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function show(AwardedGameBatch $batch)
    {
        return response()->json($batch->load(['results.slot']));
    }

    /** @OA\Put(
     *  path="/api/v1/awards/batches/{id}",
     *  tags={"Awards"},
     *  security={{"bearerAuth": {}}},
     *  summary="Atualizar lote",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function update(AwardedBatchRequest $request, AwardedGameBatch $batch)
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        \DB::transaction(fn() => $batch->update($data));

        return response()->json($batch->refresh());
    }

    /** @OA\Delete(
     *  path="/api/v1/awards/batches/{id}",
     *  tags={"Awards"},
     *  security={{"bearerAuth": {}}},
     *  summary="Remover lote",
     *  @OA\Response(response=204, description="Sem conteúdo")
     * ) */
    public function destroy(AwardedGameBatch $batch)
    {
        $batch->delete();
        return response()->noContent();
    }

    /** @OA\Put(
     *  path="/api/v1/awards/batches/{id}/results",
     *  tags={"Awards"},
     *  security={{"bearerAuth": {}}},
     *  summary="Sincronizar resultados (pós-apuração) e preparar revisão",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function syncResults(AwardedResultsSyncRequest $request, AwardedGameBatch $batch)
    {
        // apenas rascunho ou revisão podem receber sync
        abort_unless(in_array($batch->status, [BatchStatus::Draft, BatchStatus::Review]), 400, 'Batch must be draft or review to sync results.');

        $items = collect($request->validated()['items'])
            ->sortBy('rank')
            ->values();

        \DB::transaction(function () use ($batch, $items) {
            AwardedGame::where('batch_id', $batch->id)->delete();

            foreach ($items as $i) {
                AwardedGame::create([
                    'batch_id'   => $batch->id,
                    'slot_id'    => $i['slot_id'],
                    'position'   => (int) ($i['position'] ?? 0),
                    'wins_count' => (int) ($i['wins_count'] ?? 0),
                    'prize_sum'  => (float) ($i['prize_sum'] ?? 0),
                    'max_prize'  => (float) ($i['max_prize'] ?? 0),
                    'avg_prize'  => (float) ($i['avg_prize'] ?? 0),
                    'meta'       => $i['meta'] ?? null,
                    'prize_sum_initial' => (float) ($i['prize_sum_initial'] ?? null),
                    'prize_sum_final' => (float) ($i['prize_sum_final'] ?? null),
                    'increment_interval_minutes' => (int) ($i['increment_interval_minutes'] ?? null),
                ]);
            }

            $batch->update(['status' => BatchStatus::Review]);
        });

        return response()->json($batch->load('results.slot'));
    }

    /** @OA\Post(
     *  path="/api/v1/awards/batches/{id}/publish",
     *  tags={"Awards"},
     *  security={{"bearerAuth": {}}},
     *  summary="Publicar lote revisado",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function publish(Request $request, AwardedGameBatch $batch)
    {
        abort_unless($batch->status === BatchStatus::Review && $batch->results()->exists(), 400, 'Batch must be in review with results to publish.');

        $batch->update([
            'status'       => BatchStatus::Published,
            'published_at' => now(),
            'published_by' => $request->user()->id,
        ]);

        return response()->json($batch->refresh()->load('results.slot'));
    }

    /** @OA\Post(
     *  path="/api/v1/awards/batches/{id}/archive",
     *  tags={"Awards"},
     *  security={{"bearerAuth": {}}},
     *  summary="Arquivar lote publicado",
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function archive(AwardedGameBatch $batch)
    {
        abort_unless($batch->status === BatchStatus::Published, 400, 'Only published batches can be archived.');

        $batch->update(['status' => BatchStatus::Archived]);

        return response()->json($batch->refresh());
    }
}