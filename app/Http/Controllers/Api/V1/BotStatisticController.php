<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Domain\Telegram\TelegramBot;
use App\Services\Telegram\BotStatisticService;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class BotStatisticController extends Controller
{
    protected BotStatisticService $statisticService;

    public function __construct(BotStatisticService $statisticService)
    {
        $this->statisticService = $statisticService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/telegram-bots/{bot_id}/statistics",
     *     tags={"Bot Statistics"},
     *     summary="Resumo de estatísticas",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         schema={"type": "integer", "default": 30},
     *         description="Período em dias"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resumo estatístico"
     *     )
     * )
     */
    public function summary(TelegramBot $bot): JsonResponse
    {
        $days = request()->query('days', 30);

        $summary = $this->statisticService->getSummary($bot, (int)$days);

        return response()->json($summary);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/telegram-bots/{bot_id}/statistics/chart",
     *     tags={"Bot Statistics"},
     *     summary="Dados para gráficos",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         schema={"type": "integer", "default": 30},
     *         description="Período em dias"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados para gráfico"
     *     )
     * )
     */
    public function chartData(TelegramBot $bot): JsonResponse
    {
        $days = request()->query('days', 30);

        $chartData = $this->statisticService->getChartData($bot, (int)$days);

        return response()->json($chartData);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/telegram-bots/{bot_id}/statistics/validated-users",
     *     tags={"Bot Statistics"},
     *     summary="Usuários validados",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         schema={"type": "integer", "default": 50}
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de usuários validados"
     *     )
     * )
     */
    public function validatedUsers(TelegramBot $bot): JsonResponse
    {
        $limit = request()->query('limit', 50);

        $users = $this->statisticService->getValidatedUsers($bot, (int)$limit);

        return response()->json([
            'data' => $users,
            'total' => $users->count(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/telegram-bots/{bot_id}/statistics/failed-users",
     *     tags={"Bot Statistics"},
     *     summary="Usuários com validação falhada",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         schema={"type": "integer", "default": 50}
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de usuários com falha"
     *     )
     * )
     */
    public function failedUsers(TelegramBot $bot): JsonResponse
    {
        $limit = request()->query('limit', 50);

        $users = $this->statisticService->getFailedUsers($bot, (int)$limit);

        return response()->json([
            'data' => $users,
            'total' => $users->count(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/telegram-bots/{bot_id}/statistics/messages",
     *     tags={"Bot Statistics"},
     *     summary="Log de mensagens",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         schema={"type": "integer", "default": 100}
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Log de mensagens"
     *     )
     * )
     */
    public function messageLogs(TelegramBot $bot): JsonResponse
    {
        $limit = request()->query('limit', 100);

        $messages = $this->statisticService->getMessageLogs($bot, (int)$limit);

        return response()->json([
            'data' => $messages,
            'total' => $messages->count(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/telegram-bots/{bot_id}/statistics/export",
     *     tags={"Bot Statistics"},
     *     summary="Exportar em CSV",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         schema={"type": "integer", "default": 30}
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Arquivo CSV"
     *     )
     * )
     */
    public function export(TelegramBot $bot)
    {
        $days = request()->query('days', 30);

        $csv = $this->statisticService->exportToCSV($bot, (int)$days);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="stats_' . $bot->id . '_' . now()->format('Y-m-d') . '.csv"');
    }
}
