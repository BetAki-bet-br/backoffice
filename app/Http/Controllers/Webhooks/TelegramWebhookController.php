<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Domain\Telegram\TelegramBot;
use App\Services\Telegram\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Processa webhook do Telegram
     */
    public function handle(Request $request, int $botId): JsonResponse
    {
        // Busca o bot
        $bot = TelegramBot::find($botId);

        if (!$bot) {
            Log::warning('Bot não encontrado', ['bot_id' => $botId]);
            return response()->json(['error' => 'Bot not found'], 404);
        }

        // Valida o secret token
        $token = $request->header('X-Telegram-Bot-API-Secret-Token', '');
        if (!hash_equals($bot->webhook_secret, $token)) {
            Log::warning('Token inválido', [
                'bot_id' => $botId,
                'received_token' => substr($token, 0, 10),
            ]);
            return response()->json(['error' => 'Invalid token'], 403);
        }

        // Obtém o update
        $update = $request->json()->all();

        if (empty($update)) {
            // Health check do Telegram
            return response()->json(['ok' => true]);
        }

        Log::debug('Webhook received', [
            'bot_id' => $botId,
            'update_id' => $update['update_id'] ?? null,
            'has_message' => isset($update['message']),
            'has_callback' => isset($update['callback_query']),
        ]);

        // Processa de forma assíncrona
        try {
            $this->webhookService->processUpdate($bot, $update);
        } catch (\Throwable $e) {
            Log::error('Erro ao processar webhook', [
                'bot_id' => $botId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Sempre retorna 200 (Telegram quer feedback rápido)
        return response()->json(['ok' => true]);
    }
}
