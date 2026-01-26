<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\Domain\Telegram\BotFlow;
use App\Models\Domain\Telegram\BotFlowStep;
use App\Models\Domain\Telegram\BotUser;
use App\Models\Domain\Telegram\TelegramBot;
use App\Models\Domain\Telegram\BotStatistic;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para executar fluxos de bots
 * Responsável por parse, lógica condicional e sequência de steps
 */
class BotFlowExecutorService
{
    protected TelegramBotService $botService;
    protected TelegramBot $bot;
    protected BotFlow $flow;
    protected BotUser $botUser;
    protected int $chatId;

    public function __construct(TelegramBotService $botService)
    {
        $this->botService = $botService;
    }

    /**
     * Executa um fluxo para um usuário
     */
    public function executeFlow(TelegramBot $bot, BotFlow $flow, int|string $chatId, ?BotUser $botUser = null): void
    {
        try {
            $this->bot = $bot;
            $this->flow = $flow;
            $this->chatId = (int)$chatId;

            // Obtém ou cria usuário do bot
            if ($botUser === null) {
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
            }

            $this->botUser = $botUser;

            // Executa os steps
            $steps = $flow->steps()->orderBy('order')->get();

            foreach ($steps as $step) {
                $this->executeStep($step);
            }
        } catch (\Throwable $e) {
            Log::error('Erro ao executar fluxo do bot', [
                'bot_id' => $bot->id,
                'flow_id' => $flow->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Executa um step individual
     */
    protected function executeStep(BotFlowStep $step): void
    {
        match ($step->type) {
            BotFlowStep::TYPE_MESSAGE => $this->executeMessageStep($step),
            BotFlowStep::TYPE_BUTTONS => $this->executeButtonsStep($step),
            BotFlowStep::TYPE_INPUT => $this->executeInputStep($step),
            BotFlowStep::TYPE_VALIDATION => $this->executeValidationStep($step),
            BotFlowStep::TYPE_CONDITION => $this->executeConditionStep($step),
            BotFlowStep::TYPE_ACTION => $this->executeActionStep($step),
            default => Log::warning('Tipo de step desconhecido', ['type' => $step->type]),
        };
    }

    /**
     * Executa step de mensagem
     */
    protected function executeMessageStep(BotFlowStep $step): void
    {
        $content = $step->data['content'] ?? '';

        // Substitui variáveis
        $content = $this->interpolateVariables($content);

        $options = [];
        if ($step->data['parse_mode'] ?? null) {
            $options['parse_mode'] = $step->data['parse_mode'];
        }

        $response = $this->botService->sendMessage($this->bot, $this->chatId, $content, $options);

        $this->botService->logMessage(
            $this->bot,
            $this->chatId,
            \App\Models\Domain\Telegram\BotMessage::DIRECTION_OUTGOING,
            $content,
            'text',
            [
                'step_id' => $step->id,
                'response' => $response,
            ]
        );
    }

    /**
     * Executa step de botões
     */
    protected function executeButtonsStep(BotFlowStep $step): void
    {
        $buttons = $step->data['buttons'] ?? [];
        $message = $step->data['message'] ?? 'Escolha uma opção:';

        $keyboard = [
            'inline_keyboard' => [
                array_map(fn($btn) => [
                    'text' => $btn['text'] ?? 'Botão',
                    'url' => $btn['url'] ?? null,
                    'callback_data' => $btn['callback_data'] ?? null,
                ], $buttons)
            ]
        ];

        $options = [
            'reply_markup' => $keyboard,
        ];

        $response = $this->botService->sendMessage($this->bot, $this->chatId, $message, $options);

        $this->botService->logMessage(
            $this->bot,
            $this->chatId,
            \App\Models\Domain\Telegram\BotMessage::DIRECTION_OUTGOING,
            $message,
            'buttons',
            [
                'step_id' => $step->id,
                'buttons_count' => count($buttons),
            ]
        );
    }

    /**
     * Executa step de entrada (input)
     * Apenas prepara o sistema, a resposta real vem do webhook
     */
    protected function executeInputStep(BotFlowStep $step): void
    {
        $prompt = $step->data['prompt'] ?? 'Responda por favor:';

        $response = $this->botService->sendMessage($this->bot, $this->chatId, $prompt);

        // Armazena que esperamos input deste tipo
        $this->botUser->update([
            'metadata' => array_merge(
                $this->botUser->metadata ?? [],
                ['awaiting_input' => true, 'input_type' => $step->data['validate_type'] ?? 'text']
            )
        ]);

        $this->botService->logMessage(
            $this->bot,
            $this->chatId,
            \App\Models\Domain\Telegram\BotMessage::DIRECTION_OUTGOING,
            $prompt,
            'input_prompt',
            ['step_id' => $step->id]
        );
    }

    /**
     * Executa step de validação
     * Valida dados contra API externa
     */
    protected function executeValidationStep(BotFlowStep $step): void
    {
        $validationType = $step->data['type'] ?? 'email_api';

        // Obtém valor a validar do metadata do usuário
        $value = $this->botUser->metadata['last_input'] ?? null;

        if (!$value) {
            Log::warning('Nenhum valor para validar', [
                'bot_user_id' => $this->botUser->id,
                'step_id' => $step->id,
            ]);
            return;
        }

        match ($validationType) {
            'email_api' => $this->validateEmailViaApi($step, $value),
            'cpf' => $this->validateCpf($value),
            'email_format' => $this->validateEmailFormat($value),
            default => Log::warning('Tipo de validação desconhecido', ['type' => $validationType]),
        };
    }

    /**
     * Valida email contra API externa
     */
    protected function validateEmailViaApi(BotFlowStep $step, string $email): void
    {
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 15.0]);

            $payload = [
                'portalId' => $this->bot->portal_id,
                'playerDataList' => [
                    ['type' => 'Email', 'value' => $email]
                ]
            ];

            $response = $client->post($this->bot->api_url, [
                'headers' => [
                    'x-api-key' => $this->bot->api_key,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'http_errors' => false,
            ]);

            $data = json_decode((string)$response->getBody(), true);

            if (is_array($data) && !empty($data[0])) {
                $status = $data[0]['status'] ?? null;
                $isValid = ($status === 'EmailExist');

                // Armazena resultado da validação
                $this->botUser->update([
                    'email' => $email,
                    'status' => $isValid ? BotUser::STATUS_VALIDATED : BotUser::STATUS_FAILED,
                    'validated_at' => $isValid ? now() : null,
                    'metadata' => array_merge(
                        $this->botUser->metadata ?? [],
                        [
                            'validation_result' => $status,
                            'validation_timestamp' => now()->toIso8601String(),
                        ]
                    )
                ]);

                // Incrementa estatísticas
                $statistic = BotStatistic::getTodayOrCreate($this->bot->id);
                if ($isValid) {
                    $statistic->increment('validations_success');
                } else {
                    $statistic->increment('validations_failed');
                }

                $this->botService->logMessage(
                    $this->bot,
                    $this->chatId,
                    \App\Models\Domain\Telegram\BotMessage::DIRECTION_OUTGOING,
                    "Validação: $status",
                    'validation_result',
                    ['validation_status' => $status]
                );
            }
        } catch (\Throwable $e) {
            Log::error('Erro ao validar email via API', [
                'bot_id' => $this->bot->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            $this->botUser->markAsFailed('API Error: ' . $e->getMessage());
        }
    }

    /**
     * Valida formato de email
     */
    protected function validateEmailFormat(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valida CPF (básico)
     */
    protected function validateCpf(string $cpf): bool
    {
        // Implementação básica - remover caracteres especiais
        $cpf = preg_replace('/[^0-9]/i', '', $cpf);
        return strlen($cpf) === 11 && $cpf !== '00000000000';
    }

    /**
     * Executa step de condição
     */
    protected function executeConditionStep(BotFlowStep $step): void
    {
        // Implementação futura de lógica condicional
        // Por enquanto apenas passa
    }

    /**
     * Executa step de ação
     */
    protected function executeActionStep(BotFlowStep $step): void
    {
        $action = $step->data['action'] ?? null;

        match ($action) {
            'send_group_link' => $this->actionSendGroupLink($step),
            'offer_registration' => $this->actionOfferRegistration($step),
            'mark_validated' => $this->actionMarkValidated($step),
            default => Log::warning('Ação desconhecida', ['action' => $action]),
        };
    }

    /**
     * Ação: Enviar link do grupo
     */
    protected function actionSendGroupLink(BotFlowStep $step): void
    {
        // Tenta criar link dinâmico
        $link = null;
        if ($this->bot->group_chat_id) {
            $link = $this->botService->createInviteLink($this->bot, $this->bot->group_chat_id);
        }

        // Usa fallback se necessário
        if (!$link && $this->bot->group_invite_link) {
            $link = $this->bot->group_invite_link;
        }

        if ($link) {
            $message = $step->data['message'] ?? "Clique para entrar no grupo!";
            $keyboard = [
                'inline_keyboard' => [[
                    ['text' => $step->data['button_text'] ?? 'Entrar no grupo ✅', 'url' => $link]
                ]]
            ];

            $this->botService->sendMessage(
                $this->bot,
                $this->chatId,
                $message,
                ['reply_markup' => $keyboard]
            );
        }
    }

    /**
     * Ação: Oferecer registro
     */
    protected function actionOfferRegistration(BotFlowStep $step): void
    {
        if ($this->bot->register_url) {
            $message = $step->data['message'] ?? "Faça seu cadastro!";
            $keyboard = [
                'inline_keyboard' => [[
                    ['text' => $step->data['button_text'] ?? 'Fazer cadastro 📝', 'url' => $this->bot->register_url]
                ]]
            ];

            $this->botService->sendMessage(
                $this->bot,
                $this->chatId,
                $message,
                ['reply_markup' => $keyboard]
            );
        }
    }

    /**
     * Ação: Marcar como validado
     */
    protected function actionMarkValidated(BotFlowStep $step): void
    {
        $this->botUser->markAsValidated();

        $message = $step->data['message'] ?? "Validação concluída com sucesso!";
        $this->botService->sendMessage($this->bot, $this->chatId, $message);
    }

    /**
     * Interpola variáveis na mensagem
     * Ex: "Bem vindo, {first_name}!"
     */
    protected function interpolateVariables(string $content): string
    {
        $variables = [
            '{first_name}' => $this->botUser->first_name ?? 'usuário',
            '{username}' => $this->botUser->username ?? 'amigo',
            '{email}' => $this->botUser->email ?? 'seu e-mail',
            '{bot_name}' => $this->bot->name,
        ];

        return str_replace(array_keys($variables), array_values($variables), $content);
    }
}
