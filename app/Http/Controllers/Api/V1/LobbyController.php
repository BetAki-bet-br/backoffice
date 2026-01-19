<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Domain\Casino\AwardedGameBatch;
use App\Models\Setting;
use App\Models\Domain\Casino\Category;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class LobbyController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/lobbies/{vertical}/config",
     *  tags={"Lobbies"},
     *  security={{"bearerAuth": {}}},
     *  summary="Obter configuração do lobby",
     *  @OA\Parameter(name="vertical", in="path", required=true, @OA\Schema(type="string", enum={"slots","live"})),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function show(string $vertical)
    {
        if (!in_array($vertical, ['slots', 'live'])) {
            abort(400, 'Vertical inválida');
        }

        $key = "lobby.layout.{$vertical}";
        $setting = Setting::where('key', $key)->first();

        $config = $setting ? $setting->value : null;

        if (isset($config['value'])) {
            $config = $config['value']; // Legacy wrapper
        }

        // Se não existir config salva (ou estiver vazia), gera o padrão baseado nas categorias existentes
        if (empty($config) || empty($config['sections'])) {
            $config = $this->buildDefaultConfig($vertical);
        }

        if (!empty($config['sections'])) {
            foreach ($config['sections'] as &$section) {
                if (($section['type'] ?? null) === 'awarded-games') {
                    $this->enrichAwardedGamesSection($section, $vertical);
                }
            }
            unset($section); // break reference
        }

        return response()->json($config);
    }

    private function enrichAwardedGamesSection(array &$section, string $vertical): void
    {
        $batch = AwardedGameBatch::query()
            ->where('status', 'published')
            ->where('vertical', $vertical)
            ->latest('published_at')
            ->with(['results.slot'])
            ->first();

        if (!$batch) {
            $section['games'] = [];
            return;
        }

        $section['games'] = $batch->results->map(function ($awardedGame) {
            if (!$awardedGame->slot) return null;

            $slotData = $awardedGame->slot->toArray();
            $slotData['awarded'] = [
                'wins_count' => $awardedGame->wins_count,
                'prize_sum' => $awardedGame->prize_sum,
                'max_prize' => $awardedGame->max_prize,
                'avg_prize' => $awardedGame->avg_prize,
            ];
            return $slotData;
        })->whereNotNull()->values();
    }

    private function buildDefaultConfig(string $vertical): array
    {
        $categories = Category::query()
            ->where('status', 'active')
            ->forVertical($vertical)
            ->where(function ($q) {
                $q->where('type', '!=', 'game-list')
                    ->orWhereHas('slots', null, '>', 0);
            })
            ->orderBy('name')
            ->get();

        $sections = $categories->map(function (Category $category) {
            return [
                'type' => $category->type ?? 'game-list',
                'title' => $category->name,
                'categoryId' => $category->id,
                'metadata' => []
            ];
        })->values()->all();

        return ['sections' => $sections];
    }

    /** @OA\Put(
     *  path="/api/v1/lobbies/{vertical}/config",
     *  tags={"Lobbies"},
     *  security={{"bearerAuth": {}}},
     *  summary="Atualizar configuração do lobby",
     *  @OA\Parameter(name="vertical", in="path", required=true, @OA\Schema(type="string", enum={"slots","live"})),
     *  @OA\RequestBody(required=true, @OA\JsonContent(
     *      @OA\Property(property="sections", type="array", @OA\Items(type="object"))
     *  )),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function update(Request $request, string $vertical)
    {
        if (!in_array($vertical, ['slots', 'live'])) {
            abort(400, 'Vertical inválida');
        }

        $request->validate([
            'sections' => 'required|array',
            'sections.*.type' => 'required|string',
            // Add more validation if needed, e.g. depending on type
        ]);

        $key = "lobby.layout.{$vertical}";
        $data = [
            'sections' => $request->input('sections')
        ];

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $data,
                'group' => 'lobby',
                'type' => 'json'
            ]
        );

        return response()->json($data);
    }
}
