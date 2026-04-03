<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Domain\Casino\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return response()->json($config);
    }

    private function buildDefaultConfig(string $vertical): array
    {
        $categories = Category::query()
            ->where('status', 'active')
            ->forVertical($vertical)
            ->where(function ($q) {
                $q->where('type', '!=', 'game-list')
                    ->orWhereHas('slots', null, '>', 1);
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

    /** @OA\Get(
     *  path="/api/v1/lobbies/{vertical}/status",
     *  tags={"Lobbies"},
     *  security={{"bearerAuth": {}}},
     *  summary="Obter status do lobby",
     *  @OA\Parameter(name="vertical", in="path", required=true, @OA\Schema(type="string", enum={"slots","live"})),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function getStatus(string $vertical)
    {
        if (!in_array($vertical, ['slots', 'live'])) {
            abort(400, 'Vertical inválida');
        }

        $key = "lobby.status.{$vertical}";
        $setting = Setting::where('key', $key)->first();

        $status = 'active'; // default
        if ($setting) {
            $value = $setting->value;
            $status = is_array($value) ? ($value['value'] ?? 'active') : $value;
        }

        return response()->json(['vertical' => $vertical, 'status' => $status]);
    }

    /** @OA\Put(
     *  path="/api/v1/lobbies/{vertical}/status",
     *  tags={"Lobbies"},
     *  security={{"bearerAuth": {}}},
     *  summary="Atualizar status do lobby",
     *  @OA\Parameter(name="vertical", in="path", required=true, @OA\Schema(type="string", enum={"slots","live"})),
     *  @OA\RequestBody(required=true, @OA\JsonContent(
     *      @OA\Property(property="status", type="string", enum={"active","inactive","maintenance"})
     *  )),
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function updateStatus(Request $request, string $vertical)
    {
        if (!in_array($vertical, ['slots', 'live'])) {
            abort(400, 'Vertical inválida');
        }

        $request->validate([
            'status' => 'required|string|in:active,inactive,maintenance',
        ]);

        $key = "lobby.status.{$vertical}";
        $status = $request->input('status');

        DB::transaction(function () use ($key, $status) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => ['value' => $status],
                    'group' => 'lobby',
                    'type' => 'string'
                ]
            );
        });

        return response()->json(['vertical' => $vertical, 'status' => $status]);
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

        DB::transaction(function () use ($key, $data) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $data,
                    'group' => 'lobby',
                    'type' => 'json'
                ]
            );
        });

        return response()->json($data);
    }
}
