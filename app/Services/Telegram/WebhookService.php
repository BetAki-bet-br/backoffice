<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\Domain\Telegram\TelegramBot;
use App\Models\Domain\Telegram\BotUser;
use App\Models\Domain\Telegram\BotMessage;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para processar webhooks do Telegram
 */
class WebhookService
{
    protected TelegramBotService $botService;
    protected BotFlowExecutorService $flowExecutor;

    public function __construct(
        TelegramBotService $botService,
        BotFlowExecutorService $flowExecutor
    ) {
        $this->botService = $botService;
        $this->flowExecutor = $flowExecutor;
    }

    /**
     * Processa um update do Telegram
     */
    public function processUpdate(TelegramBot $bot, array $update): void
    {
        try {
            if (isset($update['message'])) {
                $this->handleMessage($bot, $update['message']);
            } elseif (isset($update['callback_query'])) {
                $this->handleCallbackQuery($bot, $update['callback_query']);
            }
        } catch (\Throwable $e) {
            Log::error('Erro ao processar webhook', [
                'bot_id' => $bot->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Processa mensagem de texto
     */
    protected function handleMessage(TelegramBot $bot, array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        $text = trim($message['text'] ?? '');
        $firstName = $message['from']['first_name'] ?? '';
        $username = $message['from']['username'] ?? '';
        $telegramUserId = $message['from']['id'] ?? null;

        if (!$chatId || !$telegramUserId) {
            Log::warning('Mensagem sem chat_id ou user_id', $message);
            return;
        }

        // Obtém ou cria usuário do bot
        $botUser = BotUser::firstOrCreate(
            [
                'bot_id' => $bot->id,
                'telegram_user_id' => $telegramUserId,
            ],
            [
                'first_name' => $firstName,
                'username' => $username,
                'status' => BotUser::STATUS_NEW,
                'first_interaction_at' => now(),
            ]
        );

        // Log da mensagem recebida
        $this->botService->logMessage(
            $bot,
            $chatId,
            BotMessage::DIRECTION_INCOMING,
            $text,
            'text'
        );

        // Dispatcher de comandos e fluxos
        if (strpos($text, '/start') === 0) {
            $this->handleStartCommand($bot, $botUser, $chatId);
        } elseif ($botUser->metadata['awaiting_input'] ?? false) {
            $this->handleUserInput($bot, $botUser, $chatId, $text);
        } else {
            // Resposta padrão se não estiver em fluxo
            $this->botService->sendMessage(
                $bot,
                $chatId,
                "Desculpe, não entendi. Use /start para recomeçar."
            );
        }
    }

    /**
     * Processa comando /start
     */
    protected function handleStartCommand(TelegramBot $bot, BotUser $botUser, int|string $chatId): void
    {
        // Obtém o fluxo padrão
        $defaultFlow = $bot->flows()
            ->where('is_default', true)
            ->where('status', 'active')
            ->first();

        if ($defaultFlow) {
            $this->flowExecutor->executeFlow($bot, $defaultFlow, $chatId, $botUser);
        } else {
            // Fallback: mensagem simples
            $this->botService->sendMessage(
                $bot,
                $chatId,
                "Bem vindo ao " . $bot->name . "! 👋"
            );
        }
    }

    /**
     * Processa entrada de usuário (email, cpf, etc)
     */
    protected function handleUserInput(TelegramBot $bot, BotUser $botUser, int|string $chatId, string $input): void
    {
        $inputType = $botUser->metadata['input_type'] ?? 'text';

        // Valida o input conforme tipo
        $isValid = match ($inputType) {
            'email' => $this->validateEmail($input),
            'cpf' => $this->validateCpf($input),
            default => true,
        };

        if (!$isValid) {
            $this->botService->sendMessage(
                $bot,
                $chatId,
                "Formato inválido. Por favor, tente novamente."
            );
            return;
        }

        // Armazena input recebido
        $metadata = $botUser->metadata ?? [];
        $metadata['last_input'] = $input;
        $metadata['awaiting_input'] = false;
        $botUser->update([
            'metadata' => $metadata,
            'email' => $inputType === 'email' ? $input : $botUser->email,
            'status' => BotUser::STATUS_VALIDATING,
        ]);

        // Obtém o fluxo ativo
        $activeFlow = $bot->flows()
            ->where('status', 'active')
            ->first();

        if ($activeFlow) {
            // Executa novamente para processar validação
            $this->flowExecutor->executeFlow($bot, $activeFlow, $chatId, $botUser);
        }
    }

    /**
     * Processa callback query (clique em botão)
     */
    protected function handleCallbackQuery(TelegramBot $bot, array $callbackQuery): void
    {
        $id = $callbackQuery['id'];
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $telegramUserId = $callbackQuery['from']['id'] ?? null;
        $data = $callbackQuery['data'] ?? '';

        if (!$chatId || !$telegramUserId) {
            Log::warning('Callback query sem chat_id ou user_id', $callbackQuery);
            return;
        }

        // Responde imediatamente
        $this->botService->answerCallbackQuery($bot, $id);

        // Obtém usuário
        $botUser = BotUser::where([
            'bot_id' => $bot->id,
            'telegram_user_id' => $telegramUserId,
        ])->first();

        if (!$botUser) {
            return;
        }

        // Processa callback data
        match ($data) {
            'start_validation' => $this->handleStartValidation($bot, $botUser, $chatId),
            default => Log::info('Callback data desconhecido', ['data' => $data]),
        };
    }

    /**
     * Processa callback de iniciar validação
     */
    protected function handleStartValidation(TelegramBot $bot, BotUser $botUser, int|string $chatId): void
    {
        // Envia mensagens informativas
        $messages = [
            "Antes de te adicionar no grupo você precisa saber de uma coisa:\n\nA rainha do green só opera em casas legalizadas e honestas🚨",
            "Pra começar com o pé direito crie a sua conta 100% gratuita na BetAki!",
        ];

        foreach ($messages as $msg) {
            $this->botService->sendMessage($bot, $chatId, $msg);
            sleep(1); // Pequeno delay entre mensagens
        }

        // Se houver register_url, envia botão
        if ($bot->register_url) {
            $keyboard = [
                'inline_keyboard' => [[
                    ['text' => 'Criar conta gratuita na BetAki 📝', 'url' => $bot->register_url]
                ]]
            ];

            $this->botService->sendMessage(
                $bot,
                $chatId,
                "Clique no botão abaixo 👇🏽",
                ['reply_markup' => $keyboard]
            );
        }

        // Pede o email
        $this->botService->sendMessage(
            $bot,
            $chatId,
            "Qual é o e-mail que você usou no cadastro?"
        );

        // Marca como aguardando input de email
        $botUser->update([
            'metadata' => array_merge(
                $botUser->metadata ?? [],
                ['awaiting_input' => true, 'input_type' => 'email']
            )
        ]);
    }

    /**
     * Valida email
     */
    protected function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valida CPF (básico)
     */
    protected function validateCpf(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/i', '', $cpf);
        return strlen($cpf) === 11 && $cpf !== '00000000000';
    }
}
