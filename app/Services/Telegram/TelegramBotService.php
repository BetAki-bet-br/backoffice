<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\Domain\Telegram\TelegramBot;
use App\Models\Domain\Telegram\BotUser;
use App\Models\Domain\Telegram\BotMessage;
use App\Models\Domain\Telegram\BotStatistic;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para gerenciar bots do Telegram
 */
class TelegramBotService
{
    protected HttpClient $httpClient;

    public function __construct()
    {
        $this->httpClient = new HttpClient([
            'timeout' => 15.0,
            'http_errors' => false,
        ]);
    }

    /**
     * Configura o webhook de um bot
     */
    public function setupWebhook(TelegramBot $bot, string $webhookUrl): array
    {
        try {
            $telegramApiUrl = "https://api.telegram.org/bot{$bot->bot_token}/setWebhook";

            $response = $this->httpClient->post($telegramApiUrl, [
                'json' => [
                    'url' => $webhookUrl,
                    'secret_token' => $bot->webhook_secret,
                    'allowed_updates' => ['message', 'callback_query'],
                    'drop_pending_updates' => false,
                ],
            ]);

            $data = json_decode((string)$response->getBody(), true);

            if ($data['ok'] ?? false) {
                $bot->update(['webhook_set_at' => now()]);

                Log::info('Telegram webhook configurado com sucesso', [
                    'bot_id' => $bot->id,
                    'bot_token' => substr($bot->bot_token, 0, 10) . '...',
                ]);

                return [
                    'ok' => true,
                    'message' => 'Webhook configurado com sucesso',
                ];
            }

            return [
                'ok' => false,
                'message' => 'Falha ao configurar webhook',
                'error' => $data['description'] ?? 'Erro desconhecido',
            ];
        } catch (\Throwable $e) {
            Log::error('Erro ao configurar webhook do Telegram', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Erro ao configurar webhook',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Testa o webhook de um bot
     */
    public function testWebhook(TelegramBot $bot): array
    {
        try {
            $telegramApiUrl = "https://api.telegram.org/bot{$bot->bot_token}/getWebhookInfo";

            $response = $this->httpClient->post($telegramApiUrl);
            $data = json_decode((string)$response->getBody(), true);

            if ($data['ok'] ?? false) {
                $result = $data['result'] ?? [];
                $bot->update(['webhook_tested_at' => now()]);

                return [
                    'ok' => true,
                    'message' => 'Webhook testado com sucesso',
                    'webhook_info' => [
                        'url' => $result['url'] ?? null,
                        'has_custom_certificate' => $result['has_custom_certificate'] ?? false,
                        'pending_update_count' => $result['pending_update_count'] ?? 0,
                        'ip_address' => $result['ip_address'] ?? null,
                        'last_error_date' => $result['last_error_date'] ?? null,
                        'last_error_message' => $result['last_error_message'] ?? null,
                        'last_synchronization_error_date' => $result['last_synchronization_error_date'] ?? null,
                    ],
                ];
            }

            return [
                'ok' => false,
                'message' => 'Falha ao testar webhook',
                'error' => $data['description'] ?? 'Erro desconhecido',
            ];
        } catch (\Throwable $e) {
            Log::error('Erro ao testar webhook do Telegram', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Erro ao testar webhook',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reseta o webhook de um bot
     */
    public function resetWebhook(TelegramBot $bot): array
    {
        try {
            $telegramApiUrl = "https://api.telegram.org/bot{$bot->bot_token}/deleteWebhook";

            $response = $this->httpClient->post($telegramApiUrl, [
                'json' => ['drop_pending_updates' => false],
            ]);

            $data = json_decode((string)$response->getBody(), true);

            if ($data['ok'] ?? false) {
                $bot->update([
                    'webhook_set_at' => null,
                    'webhook_tested_at' => null,
                ]);

                return [
                    'ok' => true,
                    'message' => 'Webhook removido com sucesso',
                ];
            }

            return [
                'ok' => false,
                'message' => 'Falha ao remover webhook',
                'error' => $data['description'] ?? 'Erro desconhecido',
            ];
        } catch (\Throwable $e) {
            Log::error('Erro ao remover webhook do Telegram', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Erro ao remover webhook',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envia uma mensagem via Telegram
     */
    public function sendMessage(TelegramBot $bot, int|string $chatId, string $text, array $options = []): array
    {
        try {
            $telegramApiUrl = "https://api.telegram.org/bot{$bot->bot_token}/sendMessage";

            $payload = array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => true,
            ], $options);

            $response = $this->httpClient->post($telegramApiUrl, [
                'json' => $payload,
            ]);

            $data = json_decode((string)$response->getBody(), true);

            if ($data['ok'] ?? false) {
                return [
                    'ok' => true,
                    'message_id' => $data['result']['message_id'] ?? null,
                ];
            }

            return [
                'ok' => false,
                'error' => $data['description'] ?? 'Erro desconhecido',
            ];
        } catch (\Throwable $e) {
            Log::error('Erro ao enviar mensagem do Telegram', [
                'bot_id' => $bot->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cria um invite link para um grupo
     */
    public function createInviteLink(TelegramBot $bot, int|string $chatId): ?string
    {
        try {
            $telegramApiUrl = "https://api.telegram.org/bot{$bot->bot_token}/createChatInviteLink";

            $response = $this->httpClient->post($telegramApiUrl, [
                'json' => [
                    'chat_id' => $chatId,
                    'name' => 'Convite Bot ' . $bot->name,
                    'creates_join_request' => false,
                ],
            ]);

            $data = json_decode((string)$response->getBody(), true);

            if ($data['ok'] ?? false && isset($data['result']['invite_link'])) {
                return $data['result']['invite_link'];
            }

            Log::warning('Falha ao criar invite link', [
                'bot_id' => $bot->id,
                'response' => $data,
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Erro ao criar invite link do Telegram', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Responde a uma callback query
     */
    public function answerCallbackQuery(TelegramBot $bot, string $callbackQueryId, string $text = ''): array
    {
        try {
            $telegramApiUrl = "https://api.telegram.org/bot{$bot->bot_token}/answerCallbackQuery";

            $payload = ['callback_query_id' => $callbackQueryId];
            if ($text !== '') {
                $payload['text'] = $text;
            }

            $response = $this->httpClient->post($telegramApiUrl, [
                'json' => $payload,
            ]);

            $data = json_decode((string)$response->getBody(), true);

            return [
                'ok' => $data['ok'] ?? false,
            ];
        } catch (\Throwable $e) {
            Log::error('Erro ao responder callback query', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false];
        }
    }

    /**
     * Obtém informações do bot
     */
    public function getMe(TelegramBot $bot): ?array
    {
        try {
            $telegramApiUrl = "https://api.telegram.org/bot{$bot->bot_token}/getMe";

            $response = $this->httpClient->post($telegramApiUrl);
            $data = json_decode((string)$response->getBody(), true);

            if ($data['ok'] ?? false) {
                return $data['result'];
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('Erro ao obter info do bot', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Loga uma mensagem do fluxo
     */
    public function logMessage(
        TelegramBot $bot,
        int|string $chatId,
        string $direction,
        string $content,
        string $type = 'text',
        array $metadata = []
    ): BotMessage {
        // Obtém ou cria usuário bot
        $botUser = BotUser::firstOrCreate(
            [
                'bot_id' => $bot->id,
                'telegram_user_id' => $chatId,
            ],
            [
                'status' => BotUser::STATUS_NEW,
                'first_interaction_at' => now(),
            ]
        );

        // Cria log da mensagem
        $message = BotMessage::create([
            'bot_id' => $bot->id,
            'bot_user_id' => $botUser->id,
            'direction' => $direction,
            'type' => $type,
            'content' => $content,
            'status' => BotMessage::STATUS_SENT,
            'metadata' => $metadata,
        ]);

        // Incrementa estatística
        $statistic = BotStatistic::getTodayOrCreate($bot->id);
        if ($direction === BotMessage::DIRECTION_OUTGOING) {
            $statistic->increment('messages_sent');
        } else {
            $statistic->increment('messages_received');
        }

        return $message;
    }
}
