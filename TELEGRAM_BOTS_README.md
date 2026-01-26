# 🤖 Sistema de Gerenciamento de Bots Telegram

## 📋 Visão Geral

Sistema completo de gerenciamento de Bots Telegram integrado ao backoffice, com:
- **Backend**: APIs RESTful para operações CRUD
- **Frontend**: Interfaces web modernas e responsivas
- **Builder Visual**: Criador visual de fluxos de mensagens
- **Analytics**: Dashboard com estatísticas e gráficos

---

## 🚀 Começando

### Pré-requisitos

1. Laravel 10+
2. PHP 8.1+
3. MySQL/PostgreSQL
4. Composer
5. Node.js (para build de assets, se necessário)

### Instalação Rápida

#### 1. Executar Migrations

```bash
php artisan migrate
```

Isso criará as 6 tabelas:
- `telegram_bots`
- `bot_flows`
- `bot_flow_steps`
- `bot_users`
- `bot_messages`
- `bot_statistics`

#### 2. Registrar Permissões (Opcional)

Se estiver usando RBAC (Spatie permission):

```bash
# Adicione ao seu seeder:
Permission::create(['name' => 'telegram_bot.create']);
Permission::create(['name' => 'telegram_bot.read']);
Permission::create(['name' => 'telegram_bot.update']);
Permission::create(['name' => 'telegram_bot.delete']);
Permission::create(['name' => 'telegram_bot.webhook_manage']);

$adminRole = Role::firstOrCreate(['name' => 'admin']);
$adminRole->givePermissionTo([
    'telegram_bot.create',
    'telegram_bot.read',
    'telegram_bot.update',
    'telegram_bot.delete',
    'telegram_bot.webhook_manage',
]);
```

#### 3. Acessar o Backoffice

1. Navegue para `/telegram-bots`
2. Clique em **Novo bot**
3. Preencha as informações

---

## 📱 Guia de Uso

### 1️⃣ Criar um Bot

**Passo 1**: Obter Token do Telegram
1. Converse com [@BotFather](https://t.me/botfather) no Telegram
2. Envie `/newbot`
3. Siga as instruções
4. Copie o token gerado (formato: `123456789:ABCdefGHIjklmnoPQRstUVwxyz...`)

**Passo 2**: Criar no Backoffice
1. Acesse `/telegram-bots`
2. Clique em **Novo bot**
3. Preencha os campos:

```
Nome: Meu Bot (qualquer nome descritivo)
Username: meu_bot_123 (sem @, 5-32 caracteres)
Bot Token: 123456789:ABCdef... (do BotFather)
Status: active
API URL: https://api.seu-dominio.com/validate
API Key: sua-chave-api
Portal ID: 1
```

4. Opcionalmente preencha:
- Descrição
- ID do Grupo Telegram (se usar `@IDBot` no grupo)
- Link de convite
- URL de cadastro
- Debug mode (para logs detalhados)

5. Clique em **Salvar**

**Passo 3**: Configurar Webhook
1. Após criar o bot, clique em **Editar**
2. Na seção "Webhook", clique em **Setup Webhook**
3. Clique em **Testar Webhook** para validar
4. Se tudo OK, aparecerá mensagem de sucesso

### 2️⃣ Criar um Fluxo de Mensagens

**Passo 1**: Acessar Fluxos
1. Na lista de bots, clique em **Fluxos** (coluna Ações)
2. Clique em **Novo fluxo**

**Passo 2**: Configurar Fluxo Básico
```
Nome: Fluxo de Boas-vindas
Descrição: Fluxo inicial que o usuário recebe
Status: draft (mude para active apenas após testar)
☑️ Usar como fluxo padrão (para comando /start)
```

**Passo 3**: Adicionar Steps

Clique em **+ Adicionar Step** para cada etapa:

#### Example 1: Message Simples
```
Step 1: Type = message
  Conteúdo: "Bem vindo ao {bot_name}! 👋"
  Parse mode: Markdown
```

#### Example 2: Botões
```
Step 2: Type = buttons
  Mensagem: "Escolha uma opção:"
  Botões (JSON):
  [
    {"text": "🚀 Iniciar", "callback_data": "start"},
    {"text": "ℹ️ Info", "url": "https://site.com"}
  ]
```

#### Example 3: Input de Email
```
Step 3: Type = input
  Prompt: "Qual seu email de cadastro?"
  Validação: email
```

#### Example 4: Validar Email
```
Step 4: Type = validation
  Tipo: email_api
```

#### Example 5: Ação com Sucesso
```
Step 5: Type = condition
  Condição: validation_success
  
Step 6: Type = action
  Ação: send_group_link
  Mensagem: "✅ Acesso liberado!"
```

#### Example 6: Ação com Falha
```
Step 7: Type = condition
  Condição: validation_failed
  
Step 8: Type = action
  Ação: offer_registration
  Mensagem: "Você não possui cadastro. Clique para registrar!"
```

**Passo 4**: Publicar
1. Clique em **Salvar Fluxo**
2. Mude status para **active**
3. Clique em **Publicar**

Pronto! Seu fluxo está ativo e o bot usará ao receber `/start`

### 3️⃣ Visualizar Estatísticas

1. Na lista de bots, clique em **Stats** (coluna Ações)
2. Você verá:

**KPI Cards**:
- 📤 Mensagens Enviadas
- 📥 Mensagens Recebidas
- 👥 Usuários Novos
- ✅ Taxa de Sucesso

**Gráficos**:
- Atividade ao longo do tempo
- Distribuição sucesso/falha

**Tabelas**:
- Usuários validados
- Usuários com falha na validação
- Log completo de mensagens

**Exportação**:
- Clique em **Exportar CSV** para baixar dados

---

## 🛠️ Arquitetura Técnica

### Backend (API)

**Base URL**: `/api/v1/`

#### Bots
```
GET    /telegram-bots              # Listar
POST   /telegram-bots              # Criar
GET    /telegram-bots/{id}         # Detalhes
PUT    /telegram-bots/{id}         # Atualizar
DELETE /telegram-bots/{id}         # Deletar
POST   /telegram-bots/{id}/setup-webhook
POST   /telegram-bots/{id}/test-webhook
POST   /telegram-bots/{id}/reset-webhook
```

#### Fluxos
```
GET    /telegram-bots/{bot}/flows
POST   /telegram-bots/{bot}/flows
GET    /telegram-bots/{bot}/flows/{flow}
PUT    /telegram-bots/{bot}/flows/{flow}
DELETE /telegram-bots/{bot}/flows/{flow}
POST   /telegram-bots/{bot}/flows/{flow}/duplicate
POST   /telegram-bots/{bot}/flows/{flow}/publish
```

#### Estatísticas
```
GET    /telegram-bots/{bot}/statistics
GET    /telegram-bots/{bot}/statistics/chart
GET    /telegram-bots/{bot}/statistics/validated-users
GET    /telegram-bots/{bot}/statistics/failed-users
GET    /telegram-bots/{bot}/statistics/messages
GET    /telegram-bots/{bot}/statistics/export
```

#### Webhook
```
POST   /webhooks/telegram/{botId}   # Recebe updates do Telegram
```

### Frontend (Views & Scripts)

**Views**:
- `resources/views/content/telegram-bots/index.blade.php` - Lista de bots
- `resources/views/content/telegram-bots/flows.blade.php` - Gerenciador de fluxos
- `resources/views/content/telegram-bots/statistics.blade.php` - Painel de stats

**Scripts**:
- `public/js/telegram-bots.js` - Lógica de bots
- `public/js/bot-flows.js` - Lógica de fluxos
- `public/js/bot-statistics.js` - Lógica de stats

### Models

**Estrutura de Dados**:

```
TelegramBot (1)
├── BotFlow (N) [versioning]
│   └── BotFlowStep (N) [ordered]
├── BotUser (N) [usuários que interagiram]
├── BotMessage (N) [log de mensagens]
└── BotStatistic (N) [analytics por dia]
```

---

## 📚 Exemplos de Uso

### Exemplo 1: Criar Bot via API

```bash
curl -X POST http://localhost/api/v1/telegram-bots \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Meu Bot",
    "bot_token": "123456789:ABCdef...",
    "username": "meu_bot",
    "api_url": "https://api.betaki.com/validate",
    "api_key": "abc123",
    "portal_id": 1,
    "status": "active"
  }'
```

### Exemplo 2: Criar Fluxo via API

```bash
curl -X POST http://localhost/api/v1/telegram-bots/1/flows \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Boas-vindas",
    "status": "draft",
    "is_default": true,
    "flow_data": [
      {
        "id": "step-1",
        "type": "message",
        "order": 1,
        "data": {
          "content": "Bem-vindo!",
          "parse_mode": "Markdown"
        }
      },
      {
        "id": "step-2",
        "type": "buttons",
        "order": 2,
        "data": {
          "message": "Escolha:",
          "buttons": [
            {"text": "Continuar", "callback_data": "continue"}
          ]
        }
      }
    ]
  }'
```

### Exemplo 3: Usar em Controller

```php
// Em seu controller
$bot = TelegramBot::find(1);
$service = app(TelegramBotService::class);

// Enviar mensagem
$service->sendMessage($bot, $chatId, "Olá!");

// Configurar webhook
$webhookUrl = url("/webhooks/telegram/{$bot->id}");
$service->setupWebhook($bot, $webhookUrl);
```

---

## 🧪 Testes Manuais

### Teste 1: Fluxo Completo

1. Crie um bot
2. Configure webhook
3. Crie um fluxo padrão
4. No Telegram, envie `/start` ao bot
5. Verifique se o bot responde com o fluxo

### Teste 2: Validação de Email

1. Crie fluxo com input de email
2. Adicione step de validação (email_api)
3. Teste com email válido e inválido
4. Verifique estatísticas

### Teste 3: Webhook

1. Configure webhook do bot
2. Clique em "Testar Webhook"
3. Verifique logs: `storage/logs/laravel.log`
4. Confirme que mensagem foi recebida

---

## 🔧 Troubleshooting

### Bot não responde

**Problema**: Webhook enviando 500  
**Solução**:
1. Verifique logs: `tail -f storage/logs/laravel.log`
2. Confirme que `TELEGRAM_BOT_TOKEN` está correto
3. Teste webhook: `/telegram-bots/{id}/test-webhook`

### Email não valida

**Problema**: Validação falha mesmo com email correto  
**Solução**:
1. Verifique URL da API: `api_url`
2. Verifique chave API: `api_key`
3. Teste manualmente a API:
```bash
curl -X POST https://api.seu-dominio.com/validate \
  -H "Authorization: Bearer abc123" \
  -d '{"email": "user@example.com"}'
```

### Webhook não configurado

**Problema**: Botão "Setup Webhook" não funciona  
**Solução**:
1. Verifique `TELEGRAM_BOT_TOKEN` do bot
2. Confirme que sua URL é acessível (não localhost)
3. Verifique se há firewall bloqueando
4. Teste webhook com cURL:
```bash
curl https://api.telegram.org/bot123456:ABCdef/setWebhook \
  -F url="https://seu-dominio.com/webhooks/telegram/1"
```

---

## 📊 Modelo de Dados

### telegram_bots
```sql
id, name, bot_token, username, description,
api_url, api_key, portal_id,
group_chat_id, group_invite_link, register_url,
webhook_secret, debug_mode,
status, webhook_set_at, webhook_tested_at,
created_by, updated_by, created_at, updated_at, deleted_at
```

### bot_flows
```sql
id, bot_id, name, description,
version, based_on_flow_id,
flow_data (JSON),
status, is_default,
created_by, updated_by, created_at, updated_at, deleted_at
```

### bot_flow_steps
```sql
id, flow_id, type, order,
data (JSON), metadata (JSON),
created_at, updated_at, deleted_at
```

### bot_users
```sql
id, bot_id, telegram_user_id, first_name, username, email,
status, first_interaction_at, validated_at,
metadata (JSON),
created_at, updated_at
```

### bot_messages
```sql
id, bot_id, bot_user_id, telegram_message_id,
direction, type, content, status,
metadata (JSON),
created_at, updated_at
```

### bot_statistics
```sql
id, bot_id, date,
messages_sent, messages_received, users_new,
validations_success, validations_failed,
created_at, updated_at
```

---

## 🔐 Segurança

### Headers de Segurança
- `X-Telegram-Bot-API-Secret-Token` - Validado em webhooks
- Bearer Token - Autenticação nas APIs

### Validações
- Username: 5-32 caracteres, regex: `^[a-zA-Z0-9_]{5,32}$`
- Email: validação de formato
- CPF: validação de formato
- API URL: validação de URL

### Soft Deletes
- Todos os dados têm `soft_deletes`
- Deletar bot não remove dados históricos

---

## 📈 Performance

### Índices
```sql
-- telegram_bots
INDEX status
INDEX created_by
INDEX webhook_secret

-- bot_flows
INDEX bot_id, status
INDEX is_default

-- bot_flow_steps
INDEX flow_id, order

-- bot_users
INDEX bot_id, telegram_user_id (UNIQUE)
INDEX bot_id, status
INDEX bot_id, email

-- bot_messages
INDEX bot_id, created_at
INDEX bot_user_id, created_at

-- bot_statistics
INDEX bot_id, date (UNIQUE)
INDEX date
```

### Queries Otimizadas
- Eager loading de relacionamentos
- Paginação padrão: 15 items
- Cache de bots frequentes

---

## 🚀 Deploy

### Checklist Pre-Produção

- [ ] Executar migrations: `php artisan migrate`
- [ ] Gerar APP_KEY: `php artisan key:generate`
- [ ] Configurar variáveis .env
- [ ] Registrar permissões (se usar RBAC)
- [ ] Testar webhook em produção
- [ ] Configurar logs
- [ ] Backup do banco de dados

### Variáveis .env Necessárias

```bash
# Telegram (opcional, pode ser por bot)
TELEGRAM_BOT_TOKEN=seu_token_aqui

# Arquivos
AWS_BUCKET=seu_bucket (se usar S3)
```

---

## 📞 Suporte

Para dúvidas ou problemas:

1. Verifique os logs: `storage/logs/laravel.log`
2. Teste a API com Postman ou cURL
3. Verifique documentação do [Telegram Bot API](https://core.telegram.org/bots/api)

---

## 📄 Licença

Este projeto faz parte do Backoffice BetAki.

**Desenvolvido**: 25 de janeiro de 2026

