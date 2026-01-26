# 📝 Desenvolvimento - Sistema de Bots Telegram

**Data**: 25 de janeiro de 2026  
**Sprint**: 2026-01-25  
**Status**: ✅ Implementação Completa

---

## 📋 Sumário Executivo

Implementação completa do **Sistema de Gerenciamento de Bots Telegram** conforme definido em `01-analysis.md`. O sistema inclui:

- ✅ **Migrations** (6 tabelas)
- ✅ **Models** (6 modelos Eloquent com relacionamentos)
- ✅ **Form Requests** (4 validações)
- ✅ **Controllers** (4 controllers + 1 webhook)
- ✅ **Services** (4 serviços)
- ✅ **Resources** (4 serializers)
- ✅ **Rotas** (15 endpoints)

---

## 🏗️ Arquitetura Implementada

### Estrutura de Diretórios

```
app/
├── Models/Domain/Telegram/              # Models do domínio
│   ├── TelegramBot.php
│   ├── BotFlow.php
│   ├── BotFlowStep.php
│   ├── BotUser.php
│   ├── BotMessage.php
│   └── BotStatistic.php
│
├── Services/Telegram/                  # Lógica de negócio
│   ├── TelegramBotService.php          # Gerenciamento de bots
│   ├── BotFlowExecutorService.php      # Executor de fluxos
│   ├── WebhookService.php              # Processamento de webhooks
│   └── BotStatisticService.php         # Analytics
│
├── Http/Controllers/Api/V1/            # API Controllers
│   ├── TelegramBotController.php
│   ├── BotFlowController.php
│   └── BotStatisticController.php
│
├── Http/Controllers/Webhooks/          # Webhook Handler
│   └── TelegramWebhookController.php
│
├── Http/Requests/Telegram/             # Validações
│   ├── StoreTelegramBotRequest.php
│   ├── UpdateTelegramBotRequest.php
│   ├── StoreBotFlowRequest.php
│   └── UpdateBotFlowRequest.php
│
└── Http/Resources/Telegram/            # Serialização
    ├── TelegramBotResource.php
    ├── TelegramBotDetailResource.php
    ├── BotFlowResource.php
    └── BotFlowDetailResource.php

database/migrations/                    # Migrations
├── 2026_01_25_000001_create_telegram_bots_table.php
├── 2026_01_25_000002_create_bot_flows_table.php
├── 2026_01_25_000003_create_bot_flow_steps_table.php
├── 2026_01_25_000004_create_bot_users_table.php
├── 2026_01_25_000005_create_bot_messages_table.php
└── 2026_01_25_000006_create_bot_statistics_table.php
```

---

## 📊 Migrations

### 1. `telegram_bots` - Configuração dos Bots

```sql
CREATE TABLE telegram_bots (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    bot_token VARCHAR(255) UNIQUE,
    username VARCHAR(255) UNIQUE,
    description TEXT,
    
    -- Config API
    api_url VARCHAR(255),
    api_key VARCHAR(255),
    portal_id INT,
    group_chat_id VARCHAR(255),
    group_invite_link VARCHAR(255),
    register_url VARCHAR(255),
    webhook_secret VARCHAR(255) UNIQUE,
    
    -- Status
    status ENUM('active', 'inactive', 'paused'),
    debug_mode BOOLEAN DEFAULT FALSE,
    webhook_set_at DATETIME,
    webhook_tested_at DATETIME,
    
    -- Auditoria
    created_by BIGINT (FK users),
    updated_by BIGINT (FK users),
    timestamps,
    soft_deletes
);
```

**Índices**: status, created_by, bot_token, webhook_secret

### 2. `bot_flows` - Fluxos de Mensagens

```sql
CREATE TABLE bot_flows (
    id BIGINT PRIMARY KEY,
    bot_id BIGINT (FK telegram_bots),
    name VARCHAR(255),
    description TEXT,
    
    -- Versionamento
    version INT DEFAULT 1,
    based_on_flow_id BIGINT (FK bot_flows),
    
    -- Conteúdo
    flow_data JSON,
    
    -- Status
    status ENUM('draft', 'active', 'archived'),
    is_default BOOLEAN DEFAULT FALSE,
    
    -- Auditoria
    created_by BIGINT (FK users),
    updated_by BIGINT (FK users),
    timestamps,
    soft_deletes
);
```

**Índices**: bot_id + status, is_default

### 3. `bot_flow_steps` - Passos do Fluxo

```sql
CREATE TABLE bot_flow_steps (
    id BIGINT PRIMARY KEY,
    flow_id BIGINT (FK bot_flows),
    type ENUM('message', 'buttons', 'input', 'validation', 'condition', 'action'),
    order INT,
    
    data JSON,
    metadata JSON,
    
    timestamps,
    soft_deletes
);
```

**Índices**: flow_id + order

### 4. `bot_users` - Usuários que Interagiram

```sql
CREATE TABLE bot_users (
    id BIGINT PRIMARY KEY,
    bot_id BIGINT (FK telegram_bots),
    telegram_user_id BIGINT UNIQUE,
    first_name VARCHAR(255),
    username VARCHAR(255),
    email VARCHAR(255),
    
    status ENUM('new', 'validating', 'validated', 'failed'),
    
    first_interaction_at DATETIME,
    validated_at DATETIME,
    
    metadata JSON,
    
    timestamps
);
```

**Índices**: bot_id + telegram_user_id (unique), bot_id + status, bot_id + email

### 5. `bot_messages` - Log de Mensagens

```sql
CREATE TABLE bot_messages (
    id BIGINT PRIMARY KEY,
    bot_id BIGINT (FK telegram_bots),
    bot_user_id BIGINT (FK bot_users, nullable),
    telegram_message_id BIGINT,
    
    direction ENUM('incoming', 'outgoing'),
    type VARCHAR(255),
    content TEXT,
    status ENUM('sent', 'delivered', 'failed'),
    
    metadata JSON,
    
    timestamps
);
```

**Índices**: bot_id + created_at, bot_user_id + created_at

### 6. `bot_statistics` - Analytics

```sql
CREATE TABLE bot_statistics (
    id BIGINT PRIMARY KEY,
    bot_id BIGINT (FK telegram_bots),
    date DATE,
    
    messages_sent INT DEFAULT 0,
    messages_received INT DEFAULT 0,
    users_new INT DEFAULT 0,
    validations_success INT DEFAULT 0,
    validations_failed INT DEFAULT 0,
    
    timestamps
);
```

**Índices**: bot_id + date (unique), date

---

## 🔧 Models Eloquent

### TelegramBot

**Arquivo**: [app/Models/Domain/Telegram/TelegramBot.php](app/Models/Domain/Telegram/TelegramBot.php)

**Relacionamentos**:
- `createdBy()` → User
- `updatedBy()` → User
- `flows()` → HasMany BotFlow
- `botUsers()` → HasMany BotUser
- `messages()` → HasMany BotMessage
- `statistics()` → HasMany BotStatistic

**Scopes**:
- `active()` - Apenas bots ativos
- `inactive()` - Apenas bots inativos

**Métodos**:
- `generateWebhookSecret()` - Gera secret único
- `hasWebhookConfigured()` - Verifica se webhook está setup
- `hasWebhookTested()` - Verifica se webhook foi testado

### BotFlow

**Arquivo**: [app/Models/Domain/Telegram/BotFlow.php](app/Models/Domain/Telegram/BotFlow.php)

**Relacionamentos**:
- `bot()` → BelongsTo TelegramBot
- `basedOnFlow()` → BelongsTo BotFlow (self)
- `derivedFlows()` → HasMany BotFlow (self)
- `steps()` → HasMany BotFlowStep (ordenado)
- `createdBy()` → BelongsTo User
- `updatedBy()` → BelongsTo User

**Scopes**:
- `active()` - Status = 'active'
- `draft()` - Status = 'draft'
- `default()` - Is_default = true
- `forBot($botId)` - Filtro por bot

**Métodos**:
- `getPreviousVersion()` - Obtém versão anterior
- `getNextVersion()` - Obtém número da próxima versão
- `createVersion($data)` - Cria nova versão
- `publish()` - Publica fluxo (arquiva versões antigas)

### BotFlowStep

**Arquivo**: [app/Models/Domain/Telegram/BotFlowStep.php](app/Models/Domain/Telegram/BotFlowStep.php)

**Constantes**:
```php
const TYPE_MESSAGE = 'message';
const TYPE_BUTTONS = 'buttons';
const TYPE_INPUT = 'input';
const TYPE_VALIDATION = 'validation';
const TYPE_CONDITION = 'condition';
const TYPE_ACTION = 'action';
```

**Métodos**:
- `isMessage()`, `isButtons()`, `isInput()`, `isValidation()`, `isCondition()`, `isAction()` - Verificadores de tipo
- `getNextStep()` - Obtém próximo step
- `getPreviousStep()` - Obtém step anterior

### BotUser

**Arquivo**: [app/Models/Domain/Telegram/BotUser.php](app/Models/Domain/Telegram/BotUser.php)

**Constantes de Status**:
```php
const STATUS_NEW = 'new';
const STATUS_VALIDATING = 'validating';
const STATUS_VALIDATED = 'validated';
const STATUS_FAILED = 'failed';
```

**Métodos**:
- `markAsValidated()` - Marca como validado
- `markAsFailed($reason)` - Marca como falhado
- `markAsValidating()` - Marca como validando
- `isValidated()`, `hasFailed()` - Verificadores
- `getInteractionDuration()` - Tempo desde primeira interação

### BotMessage

**Arquivo**: [app/Models/Domain/Telegram/BotMessage.php](app/Models/Domain/Telegram/BotMessage.php)

**Constantes**:
```php
const DIRECTION_INCOMING = 'incoming';
const DIRECTION_OUTGOING = 'outgoing';
const STATUS_SENT = 'sent';
const STATUS_DELIVERED = 'delivered';
const STATUS_FAILED = 'failed';
```

### BotStatistic

**Arquivo**: [app/Models/Domain/Telegram/BotStatistic.php](app/Models/Domain/Telegram/BotStatistic.php)

**Métodos**:
- `getTotalMessages()` - Total de mensagens (sent + received)
- `getValidationSuccessRate()` - Percentual de sucesso
- `getTodayOrCreate($botId)` - Obtém ou cria para hoje

---

## 🔐 Form Requests (Validações)

### StoreTelegramBotRequest

**Arquivo**: [app/Http/Requests/Telegram/StoreTelegramBotRequest.php](app/Http/Requests/Telegram/StoreTelegramBotRequest.php)

```php
// Campos obrigatórios
'name' => 'required|string|max:255',
'bot_token' => 'required|string|unique:telegram_bots',
'username' => 'required|string|unique:telegram_bots|regex:/^[a-zA-Z0-9_]{5,32}$/',
'api_url' => 'required|url',
'api_key' => 'required|string|min:10',
'portal_id' => 'required|integer|min:1',

// Campos opcionais
'description' => 'nullable|string|max:1000',
'group_chat_id' => 'nullable|string',
'group_invite_link' => 'nullable|url',
'register_url' => 'nullable|url',
'debug_mode' => 'boolean',
```

### UpdateTelegramBotRequest

Semelhante a `StoreTelegramBotRequest`, mas:
- Não valida `bot_token` e `username` (read-only)
- Inclui `status` (required, in: active|inactive|paused)

### StoreBotFlowRequest

```php
'name' => 'required|string|max:255',
'description' => 'nullable|string|max:1000',
'flow_data' => 'required|array',
'flow_data.*.id' => 'required|string',
'flow_data.*.type' => 'required|in:message,buttons,input,validation,condition,action',
'flow_data.*.order' => 'required|integer|min:1',
'flow_data.*.data' => 'required|array',
'is_default' => 'boolean',
```

### UpdateBotFlowRequest

Semelhante a `StoreBotFlowRequest`, mais:
- Inclui `status` (required, in: draft|active|archived)

---

## 🚀 Services

### TelegramBotService

**Arquivo**: [app/Services/Telegram/TelegramBotService.php](app/Services/Telegram/TelegramBotService.php)

**Métodos principais**:

```php
// Setup e testes
setupWebhook(TelegramBot $bot, string $webhookUrl): array
testWebhook(TelegramBot $bot): array
resetWebhook(TelegramBot $bot): array

// Envio de mensagens
sendMessage(TelegramBot $bot, int|string $chatId, string $text, array $options): array
answerCallbackQuery(TelegramBot $bot, string $callbackQueryId, string $text): array

// Gerenciamento de grupos
createInviteLink(TelegramBot $bot, int|string $chatId): ?string

// Info
getMe(TelegramBot $bot): ?array

// Logging
logMessage(TelegramBot $bot, int|string $chatId, string $direction, string $content, string $type, array $metadata): BotMessage
```

**Exemplo de Uso**:
```php
$botService = app(TelegramBotService::class);

// Enviar mensagem
$result = $botService->sendMessage($bot, $chatId, "Olá!", [
    'reply_markup' => ['inline_keyboard' => [...]]
]);

// Setup webhook
$webhookUrl = url("/webhooks/telegram/{$bot->id}");
$result = $botService->setupWebhook($bot, $webhookUrl);
```

### BotFlowExecutorService

**Arquivo**: [app/Services/Telegram/BotFlowExecutorService.php](app/Services/Telegram/BotFlowExecutorService.php)

**Métodos principais**:

```php
// Execução
executeFlow(TelegramBot $bot, BotFlow $flow, int|string $chatId, ?BotUser $botUser): void

// Métodos protegidos (step handlers)
executeMessageStep(BotFlowStep $step): void
executeButtonsStep(BotFlowStep $step): void
executeInputStep(BotFlowStep $step): void
executeValidationStep(BotFlowStep $step): void
executeActionStep(BotFlowStep $step): void

// Validação
validateEmailViaApi(BotFlowStep $step, string $email): void
validateEmailFormat(string $email): bool
validateCpf(string $cpf): bool

// Ações
actionSendGroupLink(BotFlowStep $step): void
actionOfferRegistration(BotFlowStep $step): void
actionMarkValidated(BotFlowStep $step): void

// Helpers
interpolateVariables(string $content): string
```

**Exemplo de Uso**:
```php
$executor = app(BotFlowExecutorService::class);

$flow = $bot->flows()->where('is_default', true)->first();
$executor->executeFlow($bot, $flow, $chatId, $botUser);
```

### WebhookService

**Arquivo**: [app/Services/Telegram/WebhookService.php](app/Services/Telegram/WebhookService.php)

**Métodos principais**:

```php
// Processa updates do Telegram
processUpdate(TelegramBot $bot, array $update): void

// Handlers
handleMessage(TelegramBot $bot, array $message): void
handleCallbackQuery(TelegramBot $bot, array $callbackQuery): void
handleStartCommand(TelegramBot $bot, BotUser $botUser, int|string $chatId): void
handleUserInput(TelegramBot $bot, BotUser $botUser, int|string $chatId, string $input): void
handleStartValidation(TelegramBot $bot, BotUser $botUser, int|string $chatId): void
```

**Fluxo de Processamento**:
1. Recebe update do Telegram
2. Valida secret token
3. Parse message ou callback_query
4. Obtém/cria BotUser
5. Dispatch apropriado (comando, input, callback)
6. Executa ações correspondentes

### BotStatisticService

**Arquivo**: [app/Services/Telegram/BotStatisticService.php](app/Services/Telegram/BotStatisticService.php)

**Métodos principais**:

```php
// Analytics
getSummary(TelegramBot $bot, int $days): array
getChartData(TelegramBot $bot, int $days): array

// Usuários
getValidatedUsers(TelegramBot $bot, int $limit): Collection
getFailedUsers(TelegramBot $bot, int $limit): Collection

// Mensagens
getMessageLogs(TelegramBot $bot, int $limit): Collection

// Export
exportToCSV(TelegramBot $bot, int $days): string
```

**Exemplo de Uso**:
```php
$statsService = app(BotStatisticService::class);

// Resumo do bot
$summary = $statsService->getSummary($bot, 30);
// Retorna: [
//     'total_messages_sent' => 1200,
//     'total_messages_received' => 850,
//     'validation_success_rate' => 85.5,
//     ...
// ]

// Dados para gráfico
$chartData = $statsService->getChartData($bot, 7);
```

---

## 🎮 Controllers

### TelegramBotController

**Arquivo**: [app/Http/Controllers/Api/V1/TelegramBotController.php](app/Http/Controllers/Api/V1/TelegramBotController.php)

**Endpoints**:

| Método | Rota | Descrição | Permissão |
|--------|------|-----------|-----------|
| GET | `/api/v1/telegram-bots` | Listar bots do usuário | telegram_bot.read |
| POST | `/api/v1/telegram-bots` | Criar novo bot | telegram_bot.create |
| GET | `/api/v1/telegram-bots/{id}` | Detalhes de um bot | telegram_bot.read |
| PUT | `/api/v1/telegram-bots/{id}` | Atualizar bot | telegram_bot.update |
| DELETE | `/api/v1/telegram-bots/{id}` | Deletar bot | telegram_bot.delete |
| POST | `/api/v1/telegram-bots/{id}/setup-webhook` | Configurar webhook | telegram_bot.webhook_manage |
| POST | `/api/v1/telegram-bots/{id}/test-webhook` | Testar webhook | telegram_bot.webhook_manage |
| POST | `/api/v1/telegram-bots/{id}/reset-webhook` | Remover webhook | telegram_bot.webhook_manage |

### BotFlowController

**Arquivo**: [app/Http/Controllers/Api/V1/BotFlowController.php](app/Http/Controllers/Api/V1/BotFlowController.php)

**Endpoints**:

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api/v1/telegram-bots/{bot_id}/flows` | Listar fluxos |
| POST | `/api/v1/telegram-bots/{bot_id}/flows` | Criar fluxo |
| GET | `/api/v1/telegram-bots/{bot_id}/flows/{flow_id}` | Detalhes do fluxo |
| PUT | `/api/v1/telegram-bots/{bot_id}/flows/{flow_id}` | Atualizar fluxo |
| DELETE | `/api/v1/telegram-bots/{bot_id}/flows/{flow_id}` | Deletar fluxo |
| POST | `/api/v1/telegram-bots/{bot_id}/flows/{flow_id}/duplicate` | Duplicar fluxo |
| POST | `/api/v1/telegram-bots/{bot_id}/flows/{flow_id}/publish` | Publicar fluxo |

### BotStatisticController

**Arquivo**: [app/Http/Controllers/Api/V1/BotStatisticController.php](app/Http/Controllers/Api/V1/BotStatisticController.php)

**Endpoints**:

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api/v1/telegram-bots/{bot_id}/statistics` | Resumo geral |
| GET | `/api/v1/telegram-bots/{bot_id}/statistics/chart` | Dados para gráfico |
| GET | `/api/v1/telegram-bots/{bot_id}/statistics/validated-users` | Usuários validados |
| GET | `/api/v1/telegram-bots/{bot_id}/statistics/failed-users` | Usuários com falha |
| GET | `/api/v1/telegram-bots/{bot_id}/statistics/messages` | Log de mensagens |
| GET | `/api/v1/telegram-bots/{bot_id}/statistics/export` | Exportar em CSV |

### TelegramWebhookController

**Arquivo**: [app/Http/Controllers/Webhooks/TelegramWebhookController.php](app/Http/Controllers/Webhooks/TelegramWebhookController.php)

**Endpoint**:

```
POST /webhooks/telegram/{botId}
```

**Validações**:
- Valida header `X-Telegram-Bot-API-Secret-Token`
- Sempre retorna 200 (Telegram espera resposta rápida)
- Processa async via `WebhookService`

---

## 📡 Rotas Implementadas

**Arquivo**: [routes/api.php](routes/api.php)

```php
// Bots CRUD
Route::apiResource('telegram-bots', TelegramBotController::class);

// Webhook Management
Route::post('telegram-bots/{bot}/setup-webhook', [TelegramBotController::class, 'setupWebhook']);
Route::post('telegram-bots/{bot}/test-webhook', [TelegramBotController::class, 'testWebhook']);
Route::post('telegram-bots/{bot}/reset-webhook', [TelegramBotController::class, 'resetWebhook']);

// Flows
Route::post('telegram-bots/{bot}/flows', [BotFlowController::class, 'store']);
Route::get('telegram-bots/{bot}/flows', [BotFlowController::class, 'index']);
Route::get('telegram-bots/{bot}/flows/{flow}', [BotFlowController::class, 'show']);
Route::put('telegram-bots/{bot}/flows/{flow}', [BotFlowController::class, 'update']);
Route::delete('telegram-bots/{bot}/flows/{flow}', [BotFlowController::class, 'destroy']);
Route::post('telegram-bots/{bot}/flows/{flow}/duplicate', [BotFlowController::class, 'duplicate']);
Route::post('telegram-bots/{bot}/flows/{flow}/publish', [BotFlowController::class, 'publish']);

// Statistics
Route::get('telegram-bots/{bot}/statistics', [BotStatisticController::class, 'summary']);
Route::get('telegram-bots/{bot}/statistics/chart', [BotStatisticController::class, 'chartData']);
Route::get('telegram-bots/{bot}/statistics/validated-users', [BotStatisticController::class, 'validatedUsers']);
Route::get('telegram-bots/{bot}/statistics/failed-users', [BotStatisticController::class, 'failedUsers']);
Route::get('telegram-bots/{bot}/statistics/messages', [BotStatisticController::class, 'messageLogs']);
Route::get('telegram-bots/{bot}/statistics/export', [BotStatisticController::class, 'export']);

// Public Webhook
Route::post('/webhooks/telegram/{botId}', [TelegramWebhookController::class, 'handle']);
```

---

## 🔐 Permissões (RBAC)

Adicione as seguintes permissões no seu `RolePermissionSeeder`:

```php
// Telegram Bot Permissions
Permission::firstOrCreate(['name' => 'telegram_bot.create']);
Permission::firstOrCreate(['name' => 'telegram_bot.read']);
Permission::firstOrCreate(['name' => 'telegram_bot.update']);
Permission::firstOrCreate(['name' => 'telegram_bot.delete']);
Permission::firstOrCreate(['name' => 'telegram_bot.webhook_manage']);

// Atribua ao role admin
$adminRole = Role::where('name', 'admin')->first();
$adminRole?->givePermissionTo([
    'telegram_bot.create',
    'telegram_bot.read',
    'telegram_bot.update',
    'telegram_bot.delete',
    'telegram_bot.webhook_manage',
]);
```

---

## 📥 Instalação e Setup

### 1. Executar Migrations

```bash
php artisan migrate
```

### 2. Registrar Permissões

```bash
# Adicione ao seu RolePermissionSeeder
php artisan db:seed
```

### 3. Criar Primeiro Bot

```bash
# Via API (com token autenticado)
POST /api/v1/telegram-bots
{
    "name": "Meu Bot",
    "bot_token": "123456:ABCdef...",
    "username": "meu_bot",
    "api_url": "https://api.base.com/validate",
    "api_key": "sua-chave-api",
    "portal_id": 1,
    "group_chat_id": "-1001234567890",
    "register_url": "https://betaki.bet.br/register",
    "debug_mode": false
}
```

### 4. Configurar Webhook

```bash
# Via API
POST /api/v1/telegram-bots/{bot_id}/setup-webhook
```

O sistema gerará automaticamente a URL do webhook:
```
https://seudominio.com/webhooks/telegram/{bot_id}
```

### 5. Criar Primeiro Fluxo

```bash
POST /api/v1/telegram-bots/{bot_id}/flows
{
    "name": "Fluxo Boas-vindas",
    "description": "Fluxo inicial de boas-vindas e validação",
    "is_default": true,
    "flow_data": [
        {
            "id": "step-1",
            "type": "message",
            "order": 1,
            "data": {
                "content": "Bem vindo ao {bot_name}! 👋",
                "parse_mode": "Markdown"
            }
        },
        {
            "id": "step-2",
            "type": "buttons",
            "order": 2,
            "data": {
                "message": "Clique para começar:",
                "buttons": [
                    {
                        "text": "🚀 Iniciar",
                        "callback_data": "start_validation"
                    }
                ]
            }
        },
        // ... mais steps
    ]
}
```

---

## 🧪 Exemplos de Uso

### Exemplo 1: Criar Bot Completo

```php
$botService = app(TelegramBotService::class);

// 1. Criar bot
$bot = TelegramBot::create([
    'name' => 'Test Bot',
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'username' => 'test_bot_123',
    'api_url' => env('BASE_API_URL'),
    'api_key' => env('BASE_API_KEY'),
    'portal_id' => 1,
    'group_chat_id' => env('TELEGRAM_GROUP_ID'),
    'register_url' => 'https://betaki.bet.br/register',
    'webhook_secret' => TelegramBot::generateWebhookSecret(),
    'created_by' => auth()->id(),
]);

// 2. Configurar webhook
$webhookUrl = url("/webhooks/telegram/{$bot->id}");
$botService->setupWebhook($bot, $webhookUrl);

// 3. Criar fluxo padrão
$flow = $bot->flows()->create([
    'name' => 'Fluxo Padrão',
    'is_default' => true,
    'status' => 'draft',
    'flow_data' => [...],
    'created_by' => auth()->id(),
]);

// 4. Publicar fluxo
$flow->publish();
```

### Exemplo 2: Processar Webhook

```php
// No TelegramWebhookController
$webhookService = app(WebhookService::class);
$bot = TelegramBot::find($botId);

$update = request()->json()->all();
$webhookService->processUpdate($bot, $update);
```

### Exemplo 3: Obter Estatísticas

```php
$statsService = app(BotStatisticService::class);
$bot = TelegramBot::find($botId);

// Resumo dos últimos 30 dias
$summary = $statsService->getSummary($bot, 30);

// Dados para gráfico
$chartData = $statsService->getChartData($bot, 7);

// Usuários validados
$validatedUsers = $statsService->getValidatedUsers($bot, 50);

// Exportar em CSV
$csv = $statsService->exportToCSV($bot, 30);
```

---

## 🔄 Fluxo de Mensagens - Exemplo Completo

```json
{
  "flow_data": [
    {
      "id": "step-1",
      "type": "message",
      "order": 1,
      "data": {
        "content": "Bem vindo, {first_name}! 👋",
        "parse_mode": "Markdown"
      }
    },
    {
      "id": "step-2",
      "type": "buttons",
      "order": 2,
      "data": {
        "message": "Escolha uma opção:",
        "buttons": [
          {
            "text": "🚀 Iniciar verificação",
            "callback_data": "start_validation"
          }
        ]
      }
    },
    {
      "id": "step-3",
      "type": "message",
      "order": 3,
      "data": {
        "content": "Informações sobre a BetAki...",
        "parse_mode": "Markdown"
      }
    },
    {
      "id": "step-4",
      "type": "input",
      "order": 4,
      "data": {
        "prompt": "Qual seu e-mail de cadastro?",
        "validate_type": "email"
      }
    },
    {
      "id": "step-5",
      "type": "validation",
      "order": 5,
      "data": {
        "type": "email_api"
      }
    },
    {
      "id": "step-6",
      "type": "action",
      "order": 6,
      "data": {
        "action": "send_group_link",
        "message": "✅ Acesso liberado!",
        "button_text": "Entrar no grupo"
      }
    },
    {
      "id": "step-7",
      "type": "action",
      "order": 7,
      "data": {
        "action": "mark_validated",
        "message": "Você já pode acessar todos os benefícios!"
      }
    }
  ]
}
```

---

## ❌ Tratamento de Erros

O sistema implementa tratamento robusto de erros:

### API Errors
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "bot_token": ["Este token de bot já está registrado"],
    "username": ["O usuário deve ter entre 5 e 32 caracteres"]
  }
}
```

### Webhook Errors
Todos os erros são logados mas retornam 200 (Telegram quer resposta rápida)

### Validação de Email
Se a API retornar erro, o usuário é marcado como `status = failed` com motivo

---

## 🔍 Logging

O sistema loga tudo relevante:

```php
// TelegramBotService
Log::info('Telegram webhook configurado com sucesso', ['bot_id' => ...]);
Log::error('Erro ao configurar webhook do Telegram', ['error' => ...]);

// WebhookService
Log::warning('Webhook secret inválido', ['received_token' => ...]);
Log::debug('Webhook received', ['bot_id' => ..., 'update_id' => ...]);
Log::error('Erro ao processar webhook', ['error' => ...]);

// BotFlowExecutorService
Log::error('Erro ao executar fluxo do bot', ['error' => ...]);
Log::warning('Nenhum valor para validar', ['bot_user_id' => ...]);
```

---

## 📈 Próximos Passos (Fase Frontend)

Para completar o sistema, ainda falta:

1. **Dashboard de Bots**
   - Lista de bots com cards
   - Estatísticas rápidas (mensagens, usuarios, validações)
   - Status do webhook

2. **Formulário de Criação**
   - Campos do bot
   - Setup de webhook
   - Teste de conexão

3. **Builder Visual de Flows**
   - Drag-and-drop de steps
   - Paleta de componentes
   - Preview do fluxo
   - Publicação

4. **Painel de Analytics**
   - Gráficos de atividade
   - Tabelas de usuários
   - Logs de mensagens
   - Exportação de dados

---

## ✅ Checklist de Implementação

- ✅ 6 Migrations criadas
- ✅ 6 Models Eloquent com relacionamentos
- ✅ 4 Form Requests com validações
- ✅ 4 Controllers API
- ✅ 1 Controller Webhook
- ✅ 4 Services (Bot, Flow, Webhook, Statistics)
- ✅ 4 Resources (serialização)
- ✅ 15 Rotas API
- ✅ Logging completo
- ✅ Tratamento de erros
- ✅ Documentação

---

## 📚 Referências

- [Laravel Documentation](https://laravel.com/docs)
- [Telegram Bot API](https://core.telegram.org/bots/api)
- [Spatie/laravel-permission](https://spatie.be/docs/laravel-permission)
- [GuzzleHttp Documentation](http://docs.guzzlephp.org/)

---

**Desenvolvimento Concluído**: 25 de janeiro de 2026  
**Pronto para**: Testes e Frontend
