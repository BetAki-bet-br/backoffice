<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Casino\GameExtraRequest;
use App\Models\Domain\Casino\GameExtra;
use Illuminate\Http\Request;

class GameExtraController extends Controller
{
    public function index(Request $request)
    {
        $query = GameExtra::query();

        if ($q = $request->get('q')) {
            $query->where('external_id', 'like', "%{$q}%");
        }

        if ($vol = $request->get('volatility')) {
            $query->where('volatility', $vol);
        }

        return $query->orderBy('id','desc')->paginate();
    }

    public function store(GameExtraRequest $request)
    {
        $data = $request->validated();
        $data['source'] = $data['source'] ?? 'manual';

        $extra = GameExtra::create($data);

        return response()->json($extra, 201);
    }

    public function show(GameExtra $game_extra)
    {
        return $game_extra;
    }

    public function update(GameExtraRequest $request, GameExtra $game_extra)
    {
        $game_extra->fill($request->validated());
        $game_extra->save();

        return $game_extra->fresh();
    }

    public function destroy(GameExtra $game_extra)
    {
        $game_extra->delete();
        return response()->noContent();
    }

    public function byExternalId(string $externalId)
    {
        $extra = GameExtra::where('external_id', $externalId)->first();

        if (!$extra) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Game extra não encontrado para external_id informado.',
                ]
            ], 404);
        }

        return $extra;
    }
}