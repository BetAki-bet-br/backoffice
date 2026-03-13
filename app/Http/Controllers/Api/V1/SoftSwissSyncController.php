<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SyncSoftSwissJob;
use App\Models\SyncJob;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class SoftSwissSyncController extends Controller
{
    /** @OA\Post(
     *  path="/api/v1/softswiss/sync",
     *  tags={"SoftSwiss"},
     *  security={{"bearerAuth": {}}},
     *  summary="Sincronizar provedores e jogos do CDN SoftSwiss",
     *
     *  @OA\RequestBody(
     *    required=false,
     *
     *    @OA\JsonContent(
     *
     *      @OA\Property(property="providers", type="array", @OA\Items(type="string"), example={"bgmng","wazdan"})
     *    )
     *  ),
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function sync(Request $request)
    {
        $providers = $request->input('providers');

        $job = SyncJob::create([
            'name' => 'SoftSwiss Sync',
            'status' => 'pending',
            'message' => 'Syncing games from SoftSwiss CDN'.($providers ? ' (providers: '.implode(', ', $providers).')' : ''),
        ]);

        SyncSoftSwissJob::dispatch($job->id, $providers);

        return response()->json([
            'message' => 'SoftSwiss synchronization has been queued.',
            'sync_job_id' => $job->id,
        ]);
    }
}
