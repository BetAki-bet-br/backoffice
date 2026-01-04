<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Domain\Casino\PortalGame;
use App\Models\Domain\Casino\Slot;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class PortalSlotsSyncController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/v1/slots/sync-from-portal",
     *   tags={"Slots"},
     *   security={{"bearerAuth": {}}},
     *   summary="Pré-cadastrar (upsert) slots a partir de portal_games",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"portal_id"},
     *       @OA\Property(property="portal_id", type="integer", example=1),
     *       @OA\Property(property="status", type="string", example="active", enum={"active","inactive"})
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function sync(Request $request)
    {
        $request->validate([
            'portal_id' => ['nullable','integer','min:1'],
            'status' => ['nullable','in:active,inactive'],
        ]);

        $portalId = (int) ($request->portal_id ?? config('services.base_api.portal_id', 1));
        $defaultStatus = $request->input('status', 'active');

        /** @var \App\Models\User $user */
        $user = $request->user();

        $games = PortalGame::query()
            ->where('portal_id', $portalId)
            ->orderBy('id')
            ->get(['id','external_id','name','product_name','payload']);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        $now = now();

        \DB::transaction(function () use ($games, $user, $defaultStatus, $now, &$created, &$updated, &$skipped) {
            foreach ($games as $g) {
                // Só faz sentido criar slot se tiver external_id e product_name
                if (!$g->external_id || !$g->product_name) {
                    $skipped++;
                    continue;
                }

                // Regra do projeto:
                // Slot.provider_game_id = external_id (chave do CSV e do portal_games)
                // Slot.provider = product_name
                $key = [
                    'provider' => $g->product_name,
                    'provider_game_id' => $g->external_id,
                ];

                $data = [
                    'title'      => $g->name ?: ($g->payload['name'] ?? $g->external_id),
                    'cover_url'  => null,
                    'status'     => $defaultStatus,
                    'tags'       => [],
                    'position'   => null,
                    'updated_by' => $user->id,
                    'updated_at' => $now,
                ];

                $existing = Slot::query()->where($key)->first();

                if (!$existing) {
                    $data['created_by'] = $user->id;
                    $data['created_at'] = $now;
                    Slot::query()->create(array_merge($key, $data));
                    $created++;
                } else {
                    $existing->update($data);
                    $updated++;
                }
            }
        });

        return response()->json([
            'portal_id' => $portalId,
            'result' => [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'total_portal_games' => $games->count(),
            ],
        ]);
    }
}
