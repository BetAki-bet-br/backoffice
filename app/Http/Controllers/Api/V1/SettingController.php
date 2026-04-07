<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SettingRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Annotations as OA;

class SettingController extends Controller
{
    /** @OA\Get(
     *  path="/api/v1/settings",
     *  tags={"Settings"},
     *  security={{"bearerAuth": {}}},
     *  summary="Listar settings (admin)",
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function index(Request $request)
    {
        $q = Setting::query()
            ->when($request->filled('group'), fn ($qq) => $qq->where('group', $request->group))
            ->orderBy('key');

        return response()->json($q->cursorPaginate(50));
    }

    /** @OA\Get(
     *  path="/api/v1/settings/public",
     *  tags={"Settings"},
     *  summary="Listar settings públicos (sem auth)",
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function publicIndex()
    {
        $data = Cache::remember('settings.public', 60, function () {
            return Setting::query()
                ->where('is_public', true)
                ->get(['key', 'group', 'type', 'value'])
                ->map(fn ($s) => ['key' => $s->key, 'group' => $s->group, 'type' => $s->type, 'value' => $s->value]);
        });

        return response()->json($data);
    }

    /** @OA\Post(
     *  path="/api/v1/settings",
     *  tags={"Settings"},
     *  security={{"bearerAuth": {}}},
     *  summary="Criar setting",
     *
     *  @OA\RequestBody(required=true),
     *
     *  @OA\Response(response=201, description="Criado")
     * ) */
    public function store(SettingRequest $request)
    {
        $setting = Setting::create($request->validated());
        Cache::forget('settings.public');

        return response()->json($setting, 201);
    }

    /** @OA\Get(
     *  path="/api/v1/settings/{id}",
     *  tags={"Settings"},
     *  security={{"bearerAuth": {}}},
     *  summary="Detalhar setting",
     *
     *  @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function show(Setting $setting)
    {
        return response()->json($setting);
    }

    /** @OA\Put(
     *  path="/api/v1/settings/{id}",
     *  tags={"Settings"},
     *  security={{"bearerAuth": {}}},
     *  summary="Atualizar setting",
     *
     *  @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *  @OA\RequestBody(required=true),
     *
     *  @OA\Response(response=200, description="OK")
     * ) */
    public function update(SettingRequest $request, Setting $setting)
    {
        $setting->update($request->validated());
        Cache::forget('settings.public');

        return response()->json($setting);
    }

    /** @OA\Delete(
     *  path="/api/v1/settings/{id}",
     *  tags={"Settings"},
     *  security={{"bearerAuth": {}}},
     *  summary="Remover setting",
     *
     *  @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *  @OA\Response(response=204, description="Sem conteúdo")
     * ) */
    public function destroy(Setting $setting)
    {
        $setting->delete();
        Cache::forget('settings.public');

        return response()->noContent();
    }
}
