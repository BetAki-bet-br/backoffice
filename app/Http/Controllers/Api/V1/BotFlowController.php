<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\StoreBotFlowRequest;
use App\Http\Requests\Telegram\UpdateBotFlowRequest;
use App\Http\Resources\Telegram\BotFlowResource;
use App\Http\Resources\Telegram\BotFlowDetailResource;
use App\Models\Domain\Telegram\TelegramBot;
use App\Models\Domain\Telegram\BotFlow;
use App\Models\Domain\Telegram\BotFlowStep;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class BotFlowController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/telegram-bots/{bot_id}/flows",
     *     tags={"Bot Flows"},
     *     summary="Listar fluxos de um bot",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de fluxos"
     *     )
     * )
     */
    public function index(TelegramBot $bot): JsonResponse
    {
        $flows = $bot->flows()
            ->where(function ($query) {
                $query->where('status', '!=', 'archived')
                    ->orWhere('is_default', true);
            })
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'data' => BotFlowResource::collection($flows),
            'meta' => [
                'current_page' => $flows->currentPage(),
                'per_page' => $flows->perPage(),
                'total' => $flows->total(),
                'last_page' => $flows->lastPage(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/telegram-bots/{bot_id}/flows",
     *     tags={"Bot Flows"},
     *     summary="Criar novo fluxo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=201,
     *         description="Fluxo criado"
     *     )
     * )
     */
    public function store(StoreBotFlowRequest $request, TelegramBot $bot): JsonResponse
    {
        // Cria o fluxo
        $flow = $bot->flows()->create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'flow_data' => $request->input('flow_data'),
            'is_default' => $request->input('is_default', false),
            'status' => 'draft',
            'version' => 1,
            'created_by' => $request->user()->id,
        ]);

        // Cria os steps a partir do flow_data
        $this->createStepsFromFlowData($flow, $request->input('flow_data', []));

        return response()->json(
            BotFlowDetailResource::make($flow->load('steps')),
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/telegram-bots/{bot_id}/flows/{flow_id}",
     *     tags={"Bot Flows"},
     *     summary="Detalhes de um fluxo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do fluxo"
     *     )
     * )
     */
    public function show(TelegramBot $bot, BotFlow $flow): JsonResponse
    {
        return response()->json(
            BotFlowDetailResource::make($flow->load('steps', 'createdBy', 'updatedBy'))
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v1/telegram-bots/{bot_id}/flows/{flow_id}",
     *     tags={"Bot Flows"},
     *     summary="Atualizar fluxo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Fluxo atualizado"
     *     )
     * )
     */
    public function update(UpdateBotFlowRequest $request, TelegramBot $bot, BotFlow $flow): JsonResponse
    {
        $flow->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'flow_data' => $request->input('flow_data'),
            'status' => $request->input('status', $flow->status),
            'is_default' => $request->input('is_default', false),
            'updated_by' => $request->user()->id,
        ]);

        // Deleta steps antigos
        $flow->steps()->delete();

        // Cria novos steps
        $this->createStepsFromFlowData($flow, $request->input('flow_data', []));

        return response()->json(
            BotFlowDetailResource::make($flow->load('steps'))
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/telegram-bots/{bot_id}/flows/{flow_id}",
     *     tags={"Bot Flows"},
     *     summary="Deletar fluxo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=204,
     *         description="Fluxo deletado"
     *     )
     * )
     */
    public function destroy(TelegramBot $bot, BotFlow $flow): JsonResponse
    {
        $flow->delete();

        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/telegram-bots/{bot_id}/flows/{flow_id}/duplicate",
     *     tags={"Bot Flows"},
     *     summary="Duplicar fluxo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=201,
     *         description="Fluxo duplicado"
     *     )
     * )
     */
    public function duplicate(Request $request, TelegramBot $bot, BotFlow $flow): JsonResponse
    {
        $newFlow = $flow->createVersion([
            'name' => $request->input('name', $flow->name . ' (Cópia)'),
            'flow_data' => $flow->flow_data,
        ]);

        // Copia os steps
        foreach ($flow->steps as $step) {
            BotFlowStep::create([
                'flow_id' => $newFlow->id,
                'type' => $step->type,
                'order' => $step->order,
                'data' => $step->data,
                'metadata' => $step->metadata,
            ]);
        }

        return response()->json(
            BotFlowDetailResource::make($newFlow->load('steps')),
            201
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/telegram-bots/{bot_id}/flows/{flow_id}/publish",
     *     tags={"Bot Flows"},
     *     summary="Publicar fluxo (tornar ativo)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Fluxo publicado"
     *     )
     * )
     */
    public function publish(TelegramBot $bot, BotFlow $flow): JsonResponse
    {
        $flow->publish();

        return response()->json(
            BotFlowDetailResource::make($flow)
        );
    }

    /**
     * Cria steps a partir de array flow_data
     */
    protected function createStepsFromFlowData(BotFlow $flow, array $flowData): void
    {
        foreach ($flowData as $index => $stepData) {
            BotFlowStep::create([
                'flow_id' => $flow->id,
                'type' => $stepData['type'] ?? 'message',
                'order' => $stepData['order'] ?? $index + 1,
                'data' => $stepData['data'] ?? [],
                'metadata' => $stepData['metadata'] ?? null,
            ]);
        }
    }
}
