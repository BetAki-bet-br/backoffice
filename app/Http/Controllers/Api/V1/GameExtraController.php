<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Casino\GameExtraRequest;
use App\Models\Domain\Casino\GameExtra;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class GameExtraController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/game-extras",
     *  tags={"GameExtras"},
     *  security={{"bearerAuth": {}}},
     *  summary="Listar game extras (paginado)",
     *
     *  @OA\Parameter(name="q", in="query", description="Busca por external_id", @OA\Schema(type="string")),
     *  @OA\Parameter(name="volatility", in="query", @OA\Schema(type="string", enum={"low","medium","high"})),
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function index(Request $request)
    {
        $query = GameExtra::query();

        if ($q = $request->get('q')) {
            $query->where('external_id', 'like', "%{$q}%");
        }

        if ($vol = $request->get('volatility')) {
            $query->where('volatility', $vol);
        }

        return $query->orderBy('id', 'desc')->paginate();
    }

    /** @OA\Post(
     *  path="/api/v1/game-extras",
     *  tags={"GameExtras"},
     *  security={{"bearerAuth": {}}},
     *  summary="Criar game extra",
     *
     *  @OA\RequestBody(required=true),
     *
     *  @OA\Response(response=201, description="Criado")
     * ) */
    public function store(GameExtraRequest $request)
    {
        $data = $request->validated();
        $data['source'] = $data['source'] ?? 'manual';

        $extra = GameExtra::create($data);

        return response()->json($extra, 201);
    }

    /** @OA\Get(
     *  path="/api/v1/game-extras/{game_extra}",
     *  tags={"GameExtras"},
     *  security={{"bearerAuth": {}}},
     *  summary="Exibir game extra",
     *
     *  @OA\Parameter(name="game_extra", in="path", required=true, @OA\Schema(type="integer")),
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function show(GameExtra $game_extra)
    {
        return $game_extra;
    }

    /** @OA\Put(
     *  path="/api/v1/game-extras/{game_extra}",
     *  tags={"GameExtras"},
     *  security={{"bearerAuth": {}}},
     *  summary="Atualizar game extra",
     *
     *  @OA\Parameter(name="game_extra", in="path", required=true, @OA\Schema(type="integer")),
     *
     *  @OA\RequestBody(required=true),
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function update(GameExtraRequest $request, GameExtra $game_extra)
    {
        $game_extra->fill($request->validated());
        $game_extra->save();

        return $game_extra->fresh();
    }

    /** @OA\Delete(
     *  path="/api/v1/game-extras/{game_extra}",
     *  tags={"GameExtras"},
     *  security={{"bearerAuth": {}}},
     *  summary="Excluir game extra",
     *
     *  @OA\Parameter(name="game_extra", in="path", required=true, @OA\Schema(type="integer")),
     *
     *  @OA\Response(response=204, description="No Content")
     * ) */
    public function destroy(GameExtra $game_extra)
    {
        $game_extra->delete();

        return response()->noContent();
    }

    /** @OA\Get(
     *  path="/api/v1/game-extras/by-external-id/{externalId}",
     *  tags={"GameExtras"},
     *  security={{"bearerAuth": {}}},
     *  summary="Buscar game extra por external_id",
     *
     *  @OA\Parameter(name="externalId", in="path", required=true, @OA\Schema(type="string")),
     *
     *  @OA\Response(response=200, description="OK"),
     *  @OA\Response(response=404, description="Not Found")
     * ) */
    public function byExternalId(string $externalId)
    {
        $extra = GameExtra::where('external_id', $externalId)->first();

        if (! $extra) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Game extra não encontrado para external_id informado.',
                ],
            ], 404);
        }

        return $extra;
    }
}
