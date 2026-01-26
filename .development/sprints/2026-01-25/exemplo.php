<?php
declare(strict_types=1);

use GuzzleHttp\Client;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$BOT_TOKEN         = $_ENV['BOT_TOKEN'] ?? '';
$API_URL           = $_ENV['API_URL'] ?? 'https://pgapi.betaki.bet.br/api/portal/v1/player/validate-data';
$API_KEY           = $_ENV['API_KEY'] ?? '';
$PORTAL_ID         = (int)($_ENV['PORTAL_ID'] ?? 5);
$GROUP_CHAT_ID     = $_ENV['GROUP_CHAT_ID'] ?? '';
$GROUP_INVITE_LINK = $_ENV['GROUP_INVITE_LINK'] ?? '';
$REGISTER_URL      = $_ENV['REGISTER_URL'] ?? 'https://go.aff.betaki.bet.br/z57v8nbq';
$WEBHOOK_SECRET    = $_ENV['WEBHOOK_SECRET'] ?? '';
$DEBUG             = filter_var($_ENV['DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

/**
 * Log de debug condicional, controlado pela flag DEBUG no .env.
 * Exemplo de linha no error_log:
 * [BetAkiBot DEBUG] mensagem...
 */
function debug_log(string $msg): void
{
    global $DEBUG;
    if (!$DEBUG) {
        return;
    }
    error_log('[BetAkiBot DEBUG] ' . $msg);
}

debug_log('Script iniciado.');

// Verificação de configs obrigatórias
if (!$BOT_TOKEN || !$API_KEY) {
    debug_log('Erro de configuração: BOT_TOKEN ou API_KEY ausente.');
    http_response_code(500);
    echo 'Missing BOT_TOKEN or API_KEY';
    exit;
}

// Valida o header secreto do Telegram (se configurado)
if ($WEBHOOK_SECRET) {
    $hdr = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if (!hash_equals($WEBHOOK_SECRET, $hdr)) {
        debug_log('Webhook secret inválido. Recebido: ' . ($hdr ?: 'vazio'));
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
    debug_log('Webhook secret validado com sucesso.');
}

$input = file_get_contents('php://input');
debug_log('Webhook hit. Raw size=' . strlen((string)$input));

if (!$input) {
    debug_log('Nenhum corpo na requisição (provavelmente getMe/getWebhookInfo ou teste de health).');
    http_response_code(200);
    exit;
}

$update = json_decode($input, true);
if (!is_array($update)) {
    debug_log('Falha ao decodificar JSON do update.');
    http_response_code(200);
    exit;
}

$http = new Client([
    'base_uri' => "https://api.telegram.org/bot{$BOT_TOKEN}/",
    'timeout'  => 10.0,
]);

function tg_sendMessage(Client $http, int|string $chatId, string $text, array $opts = []): void {
    debug_log("Enviando mensagem para chat_id={$chatId} texto_snippet=" . substr($text, 0, 60));
    $payload = array_merge([
        'chat_id' => $chatId,
        'text'    => $text,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true,
    ], $opts);

    try {
        $http->post('sendMessage', ['json' => $payload]);
    } catch (\Throwable $e) {
        debug_log('Falha ao enviar mensagem (sendMessage): ' . $e->getMessage());
    }
}

function tg_answerCallbackQuery(Client $http, string $callbackQueryId, string $text = ''): void {
    debug_log("Respondendo callback_query_id={$callbackQueryId}");
    $payload = ['callback_query_id' => $callbackQueryId];
    if ($text !== '') $payload['text'] = $text;

    try {
        $http->post('answerCallbackQuery', ['json' => $payload]);
    } catch (\Throwable $e) {
        debug_log('Falha ao responder callback query: ' . $e->getMessage());
    }
}

function tg_createInviteLink(Client $http, int|string $chatId): ?string {
    debug_log("Tentando criar invite link para chat_id={$chatId}");
    try {
        $resp = $http->post('createChatInviteLink', [
            'json' => [
                'chat_id' => $chatId,
                'name' => 'Convite Bot Bet Aki',
                'creates_join_request' => false,
            ]
        ]);
        $body = (string)$resp->getBody();
        $data = json_decode($body, true);
        if (($data['ok'] ?? false) && isset($data['result']['invite_link'])) {
            $link = $data['result']['invite_link'];
            debug_log("Invite link criado com sucesso: {$link}");
            return $link;
        }
        debug_log('Resposta inesperada ao criar invite link: ' . substr($body, 0, 300));
    } catch (\Throwable $e) {
        debug_log('Falha ao criar invite link: ' . $e->getMessage());
    }
    return null;
}

function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida usuário na API apenas por e-mail.
 *
 * Retorna array:
 *  - ['ok' => true, 'status' => 'EmailExist']  se encontrado
 *  - ['ok' => false, 'status' => 'EmailInvalid' | 'HTTP403' | ...] se não
 */
function validate_with_api(
    \GuzzleHttp\Client $httpRaw,
    string $apiUrl,
    string $apiKey,
    int $portalId,
    string $email
): array {
    $start = microtime(true);

    $payload = [
        'portalId' => $portalId,
        'playerDataList' => [
            ['type' => 'Email', 'value' => $email]
        ]
    ];

    $maskedKey = substr($apiKey, 0, 4) . '...' . substr($apiKey, -4);

    // Descobre IP do servidor (com alguns fallbacks)
    $serverIp = $_SERVER['SERVER_ADDR']
        ?? ($_SERVER['LOCAL_ADDR'] ?? gethostbyname(gethostname()));

    debug_log(
        "API call start => url={$apiUrl} portalId={$portalId} email={$email} " .
        "key={$maskedKey} server_ip={$serverIp}"
    );

    try {
        $client = new \GuzzleHttp\Client([
            'timeout' => 15.0,
        ]);

        $resp = $client->post($apiUrl, [
            'headers' => [
                'x-api-key'    => $apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'json'        => $payload,
            'http_errors' => false,
        ]);

        $status  = $resp->getStatusCode();
        $bodyRaw = (string)$resp->getBody();
        $ms      = (int)((microtime(true) - $start) * 1000);
        $cfRay   = $resp->hasHeader('CF-RAY') ? $resp->getHeaderLine('CF-RAY') : '';

        debug_log(
            "API response => status={$status} time_ms={$ms} CF-RAY={$cfRay} " .
            "body_snippet=" . substr($bodyRaw, 0, 300)
        );

        if ($status < 200 || $status >= 300) {
            return ['ok' => false, 'status' => "HTTP{$status}"];
        }

        $data = json_decode($bodyRaw, true);
        if (!is_array($data) || empty($data[0])) {
            debug_log('API parse => resposta vazia ou formato inesperado.');
            return ['ok' => false, 'status' => 'EmptyResponse'];
        }

        $statusStr = (string)($data[0]['status'] ?? 'Unknown');
        $ok = ($statusStr === 'EmailExist');

        debug_log("API result => ok=" . ($ok ? 'true' : 'false') . " status={$statusStr}");

        return ['ok' => $ok, 'status' => $statusStr];

    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $resp = $e->getResponse();
        $code = $resp ? $resp->getStatusCode() : 0;
        $body = $resp ? substr((string)$resp->getBody(), 0, 300) : '';
        debug_log("API ClientException => http={$code} msg={$e->getMessage()} body_snippet={$body}");
        return ['ok' => false, 'status' => "HTTP{$code}"];
    } catch (\GuzzleHttp\Exception\ServerException $e) {
        $resp = $e->getResponse();
        $code = $resp ? $resp->getStatusCode() : 0;
        $body = $resp ? substr((string)$resp->getBody(), 0, 300) : '';
        debug_log("API ServerException => http={$code} msg={$e->getMessage()} body_snippet={$body}");
        return ['ok' => false, 'status' => "HTTP{$code}"];
    } catch (\GuzzleHttp\Exception\ConnectException $e) {
        debug_log("API ConnectException => msg={$e->getMessage()}");
        return ['ok' => false, 'status' => 'ConnectError'];
    } catch (\Throwable $e) {
        debug_log("API Throwable => " . get_class($e) . " msg={$e->getMessage()} file={$e->getFile()}:{$e->getLine()}");
        return ['ok' => false, 'status' => 'RequestError'];
    }
}


// ===== Dispatcher =====
if (isset($update['message'])) {
    debug_log('Update tipo message recebido.');

    $msg       = $update['message'];
    $chatId    = $msg['chat']['id'] ?? null;
    $text      = trim((string)($msg['text'] ?? ''));
    $firstName = $msg['from']['first_name'] ?? '';
    $name      = $firstName !== '' ? $firstName : 'jogador';

    debug_log("Mensagem de chat_id={$chatId} texto_snippet=" . substr($text, 0, 60));

    if (!$chatId) {
        debug_log('Mensagem sem chat_id. Encerrando.');
        http_response_code(200);
        exit;
    }

    if (strpos($text, '/start') === 0) {
        // 1ª mensagem (boas-vindas) + botão "Iniciar verificação"
        $keyboard = [
            'inline_keyboard' => [[
                ['text' => '🚀 Iniciar verificação', 'callback_data' => 'start_validation']
            ]]
        ];

        $welcome = "Bem vindo, {$name} você ganhou acesso 100% gratuito ao grupo privado da rainha do green 🔥👑\n\n"
                 . "Nesse grupo você vai operar junto comigo 2x ao dia❕❕\n\n"
                 . "Acompanhe as lives para você concorrer a prêmios exclusivos, sorteio de bancas, bônus e muitos mais\n\n"
                 . "Não te vendo nada ✅\n\n"
                 . "Não cobro acesso ao grupo ✅\n\n"
                 . "Tudo 100% em uma casa legalizada e honesta ✅\n\n"
                 . "Você precisa da minha ajuda pra ter resultado 🚨";

        tg_sendMessage(
            $http,
            $chatId,
            $welcome,
            ['reply_markup' => $keyboard]
        );
        http_response_code(200);
        exit;
    }

    // fluxo: usuário enviando e-mail
    if ($text !== '') {
        if (!is_valid_email($text)) {
            debug_log("E-mail inválido enviado: {$text}");
            tg_sendMessage(
                $http,
                $chatId,
                "Formato inválido. Envie um **e-mail válido** para continuar."
            );
            http_response_code(200);
            exit;
        }

        $email = $text;
        debug_log("E-mail recebido para validação: {$email}");

        // Durante a validação
        tg_sendMessage($http, $chatId, "Um momento.. já te retorno");

        // BYPASS de teste:
        $lowerEmail = strtolower($email);
        if ($lowerEmail === 'teste.aprovado@betaki.bet.br') {
            debug_log('Bypass de teste: teste.aprovado@betaki.bet.br (forçando EmailExist).');
            // força como se a API tivesse retornado EmailExist
            $res = ['ok' => true, 'status' => 'EmailExist'];
        } elseif ($lowerEmail === 'teste.reprovado@betaki.bet.br') {
            debug_log('Bypass de teste: teste.reprovado@betaki.bet.br (forçando EmailInvalid).');
            // força como se a API tivesse retornado EmailInvalid
            $res = ['ok' => false, 'status' => 'EmailInvalid'];
        } else {
            // fluxo normal: chama API de verdade
            $res = validate_with_api($http, $API_URL, $API_KEY, $PORTAL_ID, $email);
        }

        if ($res['ok'] === true) {
            debug_log("Validação OK para email={$email} status={$res['status']}");

            // tentar criar link dinâmico (se BOT for admin e houver GROUP_CHAT_ID)
            $link = null;
            if ($GROUP_CHAT_ID !== '') {
                $link = tg_createInviteLink($http, $GROUP_CHAT_ID);
            }
            if (!$link && $GROUP_INVITE_LINK !== '') {
                debug_log('Usando GROUP_INVITE_LINK como fallback.');
                $link = $GROUP_INVITE_LINK;
            }

            if ($link) {
                // botão para entrar no grupo
                $keyboard = [
                    'inline_keyboard' => [[
                        ['text' => 'Entrar no grupo ✅', 'url' => $link]
                    ]]
                ];

                $msgOk = "Ótimo {$name}, agora vou te adicionar no grupo!\n"
                       . "Lembre-se a Rainha do Green só opera na BetAki. É a única casa que confio e sei que é segura!\n\n"
                       . "Clica no botão abaixo pra entrar no grupo 👇";

                tg_sendMessage(
                    $http,
                    $chatId,
                    $msgOk,
                    ['reply_markup' => $keyboard]
                );
            } else {
                debug_log('Validação OK mas não foi possível obter link do grupo.');
                tg_sendMessage(
                    $http,
                    $chatId,
                    "✅ *Acesso liberado!* E-mail validado com sucesso.\n\nPorém não consegui gerar/obter o link do grupo agora. Avise um administrador para receber o convite."
                );
            }
        } else {
            debug_log("Validação FALHOU para email={$email} status={$res['status']}");

            $map = [
                'EmailInvalid'  => 'E-mail não encontrado.',
                'RequestError'  => 'Erro de comunicação com o servidor.',
                'ConnectError'  => 'Falha de conexão com o servidor.',
                'InvalidJSON'   => 'Resposta inválida do servidor.',
                'EmptyResponse' => 'Resposta vazia do servidor.',
            ];
            $human = $map[$res['status']] ?? 'E-mail não localizado.';

            // 1ª mensagem: convite pra cadastro + botão
            $msgFail1 = "Poxa, não encontramos o seu cadastro...\n"
                       ."Faça o seu registro gratuito na BetAki! A casa de apostas da Silvia Abravanel, filha do Silvio Santos.";

            $keyboard = [
                'inline_keyboard' => [[
                    ['text' => 'Fazer cadastro na BetAki 📝', 'url' => $REGISTER_URL]
                ]]
            ];

            tg_sendMessage(
                $http,
                $chatId,
                $msgFail1,
                ['reply_markup' => $keyboard]
            );

            // 2ª mensagem: instrução pra voltar e mandar o e-mail
            $msgFail2 = "Assim que fizer o cadastro, volte aqui e mande novamente seu email para que eu libere o grupo pra você!";

            tg_sendMessage(
                $http,
                $chatId,
                $msgFail2
            );
        }
    }

    http_response_code(200);
    exit;
}

if (isset($update['callback_query'])) {
    debug_log('Update tipo callback_query recebido.');

    $cq      = $update['callback_query'];
    $id      = $cq['id'];
    $chatId  = $cq['message']['chat']['id'];
    $from    = $cq['from'] ?? [];
    $fname   = $from['first_name'] ?? '';
    $name    = $fname !== '' ? $fname : 'jogador';
    $data    = $cq['data'] ?? '';

    debug_log("Callback data recebido={$data} chat_id={$chatId}");

    if ($data === 'start_validation') {
        tg_answerCallbackQuery($http, $id);

        // 2ª mensagem
        $msg2 = "Antes de te adicionar no grupo você precisa saber de uma coisa:\n\n"
              . "A rainha do green só opera em casas legalizadas e honestas🚨\n\n"
              . "A melhor do momento é casa de apostas da filha do Silvio Santos, Silvia Abravanel. Segura, honesta e com diversos sorteios, bonificações e mais!";

        tg_sendMessage($http, $chatId, $msg2);

        // 3ª mensagem + botão para registro
        $msg3 = "Pra começar com o pé direito crie a sua conta 100% gratuita na BetAki! 👇🏽";

        $keyboardCadastro = [
            'inline_keyboard' => [[
                ['text' => 'Criar conta gratuita na BetAki 📝', 'url' => $REGISTER_URL]
            ]]
        ];

        tg_sendMessage(
            $http,
            $chatId,
            $msg3,
            ['reply_markup' => $keyboardCadastro]
        );

        // 4ª mensagem
        $msg4 = "Em alguns instantes eu vou te adicionar no grupo gratuito onde faço as lives diariamente ✅";
        tg_sendMessage($http, $chatId, $msg4);

        // 5ª mensagem (pedido de e-mail)
        $msg5 = "Só preciso que você me informe o e-mail que usou no cadastro da BetAki, "
               ."assim a rainha consegue te dar um presente surpresa 🎁";
        tg_sendMessage($http, $chatId, $msg5);
    }

    http_response_code(200);
    exit;
}

// fallback
debug_log('Update sem message nem callback_query. Encerrando.');
http_response_code(200);
