<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use OpenApi\Annotations as OA;

class GameExtraSyncController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/v1/game-extras/sync",
     *   tags={"Game Extras"},
     *   security={{"bearerAuth": {}}},
     *   summary="Sincronizar dados de RTP/Volatilidade/Aposta mínima do CSV",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function sync(Request $request)
    {
        Artisan::call('app:import-game-extras', ['file' => base_path('Games List.csv')]);

        return response()->json([
            'message' => 'A sincronização de dados extras do jogo foi iniciada.',
            'output' => Artisan::output(),
        ]);
    }
}
