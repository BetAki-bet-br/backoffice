<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\StoreTelegramBotRequest;
use App\Http\Requests\Telegram\UpdateTelegramBotRequest;
use App\Http\Resources\Telegram\TelegramBotResource;
use App\Http\Resources\Telegram\TelegramBotDetailResource;
use App\Models\Domain\Telegram\TelegramBot;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\Paginator;
use OpenApi\Annotations as OA;

class TelegramBotController extends Controller
{
    protected TelegramBotService $botService;

    public function __construct(TelegramBotService $botService)
    {
        $this->botService = $botService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/telegram-bots",
     *     tags={"Telegram Bots"},
     *     summary="Listar bots do Telegram",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de bots"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $bots = TelegramBot::with(['createdBy', 'flows'])
            ->where('created_by', $request->user()->id)
            ->paginate(15);

        return response()->json([
            'data' => TelegramBotResource::collection($bots),
            'meta' => [
                'current_page' => $bots->currentPage(),
                'per_page' => $bots->perPage(),
                'total' => $bots->total(),
                'last_page' => $bots->lastPage(),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/telegram-bots",
     *     tags={"Telegram Bots"},
     *     summary="Criar novo bot do Telegram",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dados do bot"
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bot criado com sucesso"
     *     )
     * )
     */
    public function store(StoreTelegramBotRequest $request): JsonResponse
    {
        $bot = TelegramBot::create([
            ...$request->validated(),
            'webhook_secret' => TelegramBot::generateWebhookSecret(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json(
            TelegramBotDetailResource::make($bot),
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/telegram-bots/{id}",
     *     tags={"Telegram Bots"},
     *     summary="Detalhes de um bot",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         schema={"type": "integer"}
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do bot"
     *     )
     * )
     */
    public function show(TelegramBot $bot): JsonResponse
    {
        return response()->json(
            TelegramBotDetailResource::make($bot->load(['flows', 'botUsers', 'statistics']))
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v1/telegram-bots/{id}",
     *     tags={"Telegram Bots"},
     *     summary="Atualizar bot",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         schema={"type": "integer"}
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bot atualizado"
     *     )
     * )
     */
    public function update(UpdateTelegramBotRequest $request, TelegramBot $bot): JsonResponse
    {
        $bot->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json(
            TelegramBotDetailResource::make($bot)
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/telegram-bots/{id}",
     *     tags={"Telegram Bots"},
     *     summary="Deletar bot",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         schema={"type": "integer"}
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Bot deletado"
     *     )
     * )
     */
    public function destroy(TelegramBot $bot): JsonResponse
    {
        $bot->delete();

        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/telegram-bots/{id}/setup-webhook",
     *     tags={"Telegram Bots"},
     *     summary="Configurar webhook do bot",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Webhook configurado"
     *     )
     * )
     */
    public function setupWebhook(Request $request, TelegramBot $bot): JsonResponse
    {
        // Gera a URL do webhook
        $webhookUrl = url("/webhooks/telegram/{$bot->id}");

        $result = $this->botService->setupWebhook($bot, $webhookUrl);

        return response()->json($result, $result['ok'] ? 200 : 400);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/telegram-bots/{id}/test-webhook",
     *     tags={"Telegram Bots"},
     *     summary="Testar webhook",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Teste realizado"
     *     )
     * )
     */
    public function testWebhook(TelegramBot $bot): JsonResponse
    {
        $result = $this->botService->testWebhook($bot);

        return response()->json($result, $result['ok'] ? 200 : 400);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/telegram-bots/{id}/reset-webhook",
     *     tags={"Telegram Bots"},
     *     summary="Remover webhook",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Webhook removido"
     *     )
     * )
     */
    public function resetWebhook(TelegramBot $bot): JsonResponse
    {
        $result = $this->botService->resetWebhook($bot);

        return response()->json($result, $result['ok'] ? 200 : 400);
    }
}
