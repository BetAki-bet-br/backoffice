# 📊 Análise - Sistema de Bots Telegram

**Data**: 25 de janeiro de 2026  
**Sprint**: 2026-01-25  
**Status**: ✅ Em Análise

---

## 📋 Sumário Executivo

Sistema para criar e gerenciar bots do Telegram dentro do backoffice, permitindo:
- Gerenciamento de múltiplos bots com diferentes configurações
- Criação visual/intuitiva de fluxos de mensagens
- Validação de cadastro via API externa
- Liberação de acesso a grupos Telegram
- Coleta de estatísticas e analytics

---

## 🔍 Análise do Fluxo Atual (exemplo.php)

### Arquitetura Atual
```
Webhook Telegram → Validação Secret → Parse Update → Dispatcher
                                          ↓
                              Message Handler / Callback Handler
                                          ↓
                              Validação Email → API → Resultado
                                          ↓
                              Envio de Links / Mensagens
```

### Fluxo de Mensagens Atual
```
1. User: /start
   ↓
2. Bot: Mensagem boas-vindas + botão "Iniciar verificação"
   ↓
3. User: Clica botão
   ↓
4. Bot: Explica sobre BetAki (várias mensagens)
   ↓
5. Bot: Botão pra cadastro
   ↓
6. User: Envia e-mail
   ↓
7. Bot: Valida na API
   ├─ ✅ EmailExist → Libera acesso (cria/usa invite link)
   └─ ❌ EmailInvalid → Oferece cadastro
   ↓
8. Bot: Mensagens finais com link do grupo
```

### Componentes Principais do Exemplo
1. **Validação de Secret**: Header `X-Telegram-Bot-API-Secret-Token`
2. **HTTP Client**: GuzzleHttp para chamadas à API de validação
3. **Logging**: Debug condicional via `DEBUG` env var
4. **Tratamento de Erros**: Try-catch robusto com fallbacks
5. **Integração API**: Valida e-mail contra `BASE_API_URL`
6. **Criação de Invite Link**: Dynamic link creation via Telegram API

### Configurações do Exemplo
```
BOT_TOKEN           - Token do Telegram
API_URL             - URL da API de validação
API_KEY             - Chave da API
PORTAL_ID           - ID do portal/casa
GROUP_CHAT_ID       - ID do grupo Telegram
GROUP_INVITE_LINK   - Link fallback do grupo
REGISTER_URL        - URL de cadastro
WEBHOOK_SECRET      - Secret para validar webhook
DEBUG               - Flag de debug
```

---

## 🎯 Requisitos Funcionais

### RF-01: Gerenciamento de Bots
- [ ] Criar novo bot (linkado ao BotFather externamente)
- [ ] Listar todos os bots
- [ ] Visualizar detalhes do bot
- [ ] Editar configurações do bot
- [ ] Ativar/desativar bot
- [ ] Deletar bot com soft delete

### RF-02: Builder de Fluxo de Mensagens
- [ ] Criar fluxo com editor visual/drag-and-drop (ou formulário)
- [ ] Adicionar mensagens de texto
- [ ] Adicionar botões (URL, Callback)
- [ ] Condicional: Validação de email
- [ ] Condicional: Resultado da validação (sucesso/erro)
- [ ] Ação: Enviar link do grupo
- [ ] Ação: Redirecionar para registro
- [ ] Salvar fluxo como template
- [ ] Duplicar fluxo existente

### RF-03: Estatísticas e Analytics
- [ ] Total de mensagens enviadas
- [ ] Total de usuários validados
- [ ] Taxa de sucesso/erro
- [ ] Mensagens mais clicadas
- [ ] Gráfico de atividade por período
- [ ] Exportar dados

### RF-04: Configurações do Bot
- [ ] Bot Token (read-only após criação)
- [ ] URL da API de validação
- [ ] Chave da API
- [ ] Portal ID
- [ ] ID do grupo Telegram
- [ ] Link do grupo (fallback)
- [ ] URL de cadastro
- [ ] Secret do webhook
- [ ] Debug mode

### RF-05: Webhook Management
- [ ] Setup automático de webhook no Telegram
- [ ] Validação de webhook
- [ ] Teste de webhook
- [ ] Reset de webhook

---

## 📊 Modelos de Dados

### 1. **TelegramBot**
```php
Schema::create('telegram_bots', function (Blueprint $table) {
    $table->id();
    $table->string('name');                    // Nome do bot
    $table->string('bot_token')->unique();     // Token do Telegram
    $table->string('username')->unique();      // @username do bot
    $table->text('description')->nullable();   // Descrição
    
    // Config
    $table->string('api_url');                 // URL API validação
    $table->string('api_key');                 // Chave API
    $table->unsignedInteger('portal_id');      // Portal ID
    $table->string('group_chat_id')->nullable(); // ID do grupo
    $table->string('group_invite_link')->nullable();
    $table->string('register_url')->nullable();
    $table->string('webhook_secret')->unique();
    $table->boolean('debug_mode')->default(false);
    
    // Status
    $table->enum('status', ['active', 'inactive', 'paused'])->default('active');
    $table->datetime('webhook_set_at')->nullable();
    $table->datetime('webhook_tested_at')->nullable();
    
    // Audit
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});
```

### 2. **BotFlow** (Fluxo de Mensagens)
```php
Schema::create('bot_flows', function (Blueprint $table) {
    $table->id();
    $table->foreignId('bot_id')->constrained('telegram_bots')->onDelete('cascade');
    $table->string('name');                    // Ex: "Fluxo Boas-vindas"
    $table->text('description')->nullable();
    
    // Versioning
    $table->unsignedInteger('version')->default(1);
    $table->foreignId('based_on_flow_id')->nullable()->constrained('bot_flows');
    
    // Conteúdo JSON com o fluxo
    $table->json('flow_data');                 // Estrutura do fluxo
    
    // Status
    $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
    $table->boolean('is_default')->default(false); // Flow padrão ao /start
    
    // Audit
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();
});
```

### 3. **BotFlowStep** (Passos do Fluxo)
```php
Schema::create('bot_flow_steps', function (Blueprint $table) {
    $table->id();
    $table->foreignId('flow_id')->constrained('bot_flows')->onDelete('cascade');
    $table->string('type'); // message, button, validation, condition, action
    $table->unsignedInteger('order');
    
    // Conteúdo por tipo
    $table->json('data'); // {
                          //   "type": "message",
                          //   "content": "Bem vindo!",
                          //   "parse_mode": "Markdown"
                          // }
                          // ou
                          // {
                          //   "type": "button",
                          //   "buttons": [{"text": "...", "url|callback": "..."}]
                          // }
    
    $table->timestamps();
    $table->softDeletes();
});
```

### 4. **BotUser** (Usuários que interagiram com bot)
```php
Schema::create('bot_users', function (Blueprint $table) {
    $table->id();
    $table->foreignId('bot_id')->constrained('telegram_bots')->onDelete('cascade');
    $table->unsignedBigInteger('telegram_user_id')->unique();
    $table->string('first_name')->nullable();
    $table->string('username')->nullable();
    $table->string('email')->nullable();
    $table->enum('status', ['new', 'validating', 'validated', 'failed'])->default('new');
    
    $table->datetime('first_interaction_at');
    $table->datetime('validated_at')->nullable();
    
    $table->json('metadata')->nullable(); // Dados da validação, etc
    
    $table->timestamps();
});
```

### 5. **BotMessage** (Log de Mensagens)
```php
Schema::create('bot_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('bot_id')->constrained('telegram_bots')->onDelete('cascade');
    $table->foreignId('bot_user_id')->nullable()->constrained('bot_users');
    $table->unsignedBigInteger('telegram_message_id')->nullable();
    
    $table->enum('direction', ['incoming', 'outgoing']);
    $table->string('type'); // text, button_callback, etc
    $table->text('content');
    $table->enum('status', ['sent', 'delivered', 'failed'])->default('sent');
    
    $table->json('metadata')->nullable();
    
    $table->timestamps();
});
```

### 6. **BotStatistic** (Analytics)
```php
Schema::create('bot_statistics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('bot_id')->constrained('telegram_bots')->onDelete('cascade');
    $table->date('date');
    
    // Contadores
    $table->unsignedInteger('messages_sent')->default(0);
    $table->unsignedInteger('messages_received')->default(0);
    $table->unsignedInteger('users_new')->default(0);
    $table->unsignedInteger('validations_success')->default(0);
    $table->unsignedInteger('validations_failed')->default(0);
    
    $table->timestamps();
    
    $table->unique(['bot_id', 'date']);
});
```

---

## 🏗️ Arquitetura Proposta

### API Endpoints

#### **Bots (CRUD)**
```
POST   /api/v1/telegram-bots                    # Criar bot
GET    /api/v1/telegram-bots                    # Listar bots
GET    /api/v1/telegram-bots/{id}               # Detalhes
PUT    /api/v1/telegram-bots/{id}               # Editar
DELETE /api/v1/telegram-bots/{id}               # Deletar

# Webhook
POST   /api/v1/telegram-bots/{id}/setup-webhook    # Setup webhook
POST   /api/v1/telegram-bots/{id}/test-webhook     # Testar webhook
POST   /api/v1/telegram-bots/{id}/reset-webhook    # Reset webhook
```

#### **Flows (Builder)**
```
POST   /api/v1/telegram-bots/{bot_id}/flows              # Criar flow
GET    /api/v1/telegram-bots/{bot_id}/flows              # Listar flows
GET    /api/v1/telegram-bots/{bot_id}/flows/{flow_id}    # Detalhes
PUT    /api/v1/telegram-bots/{bot_id}/flows/{flow_id}    # Editar
DELETE /api/v1/telegram-bots/{bot_id}/flows/{flow_id}    # Deletar
POST   /api/v1/telegram-bots/{bot_id}/flows/{flow_id}/duplicate  # Duplicar
```

#### **Statistics**
```
GET    /api/v1/telegram-bots/{bot_id}/statistics         # Resumo
GET    /api/v1/telegram-bots/{bot_id}/statistics/users   # Usuários
GET    /api/v1/telegram-bots/{bot_id}/messages           # Logs de msg
```

#### **Webhook (Public)**
```
POST   /webhooks/telegram/{bot_id}              # Recebe updates do Telegram
```

### Controllers Necessários
1. **TelegramBotController** - CRUD de bots
2. **BotFlowController** - CRUD de flows
3. **BotStatisticController** - Analytics
4. **WebhookController** - Recebe updates (reutiliza exemplo.php)

### Services Necessários
1. **TelegramBotService** - Gerenciamento de bots
2. **BotFlowExecutorService** - Executa fluxo baseado em steps
3. **WebhookService** - Recebe/valida webhooks
4. **BotStatisticService** - Coleta analytics

### Models
- `TelegramBot`
- `BotFlow`
- `BotFlowStep`
- `BotUser`
- `BotMessage`
- `BotStatistic`

---

## 🎨 Frontend Components

### Páginas Principais
1. **Dashboard de Bots**
   - Lista de bots com status
   - Cards com estatísticas rápidas
   - Botão "Novo Bot"

2. **Criar/Editar Bot**
   - Formulário com configurações
   - Setup de webhook
   - Teste de conexão

3. **Builder de Flow** (Core)
   - Canvas para drag-and-drop de steps
   - Paleta de componentes (Message, Button, Validation, Condition, Action)
   - Preview do fluxo
   - Versioning

4. **Detalhes do Bot**
   - Estatísticas em tempo real
   - Gráficos de atividade
   - Log de mensagens
   - Gerenciamento de usuários validados

---

## 📐 Estrutura de Flow (JSON)

```json
{
  "id": "uuid",
  "name": "Fluxo Boas-vindas",
  "version": 1,
  "steps": [
    {
      "id": "step-1",
      "type": "message",
      "trigger": "on_start",
      "data": {
        "content": "Bem vindo, {first_name}!",
        "parse_mode": "Markdown"
      },
      "next_step": "step-2"
    },
    {
      "id": "step-2",
      "type": "buttons",
      "data": {
        "buttons": [
          {
            "text": "🚀 Iniciar verificação",
            "callback_data": "start_validation"
          }
        ]
      },
      "next_step": "step-3"
    },
    {
      "id": "step-3",
      "type": "conditional",
      "data": {
        "condition": "callback_data == 'start_validation'"
      },
      "next_step": "step-4"
    },
    {
      "id": "step-4",
      "type": "message",
      "data": {
        "content": "Informações sobre a BetAki..."
      },
      "next_step": "step-5"
    },
    {
      "id": "step-5",
      "type": "input",
      "data": {
        "prompt": "Qual seu e-mail?",
        "validate_type": "email"
      },
      "next_step": "step-6"
    },
    {
      "id": "step-6",
      "type": "validation",
      "data": {
        "type": "email_api",
        "api_key": "from_bot_config"
      },
      "on_success": "step-7",
      "on_failure": "step-8"
    },
    {
      "id": "step-7",
      "type": "action",
      "data": {
        "action": "send_group_link",
        "message": "✅ Acesso liberado!"
      }
    },
    {
      "id": "step-8",
      "type": "action",
      "data": {
        "action": "offer_registration",
        "register_url": "from_bot_config"
      }
    }
  ]
}
```

---

## 🔄 Fluxo de Desenvolvimento

### Fase 1: Backend Infrastructure
- [ ] Migrations dos models
- [ ] Models Eloquent
- [ ] Controllers base
- [ ] Form Requests de validação
- [ ] Services

### Fase 2: Bot Management
- [ ] CRUD de bots
- [ ] Setup de webhook
- [ ] Testes de webhook
- [ ] Validação de configurações

### Fase 3: Flow Builder
- [ ] CRUD de flows
- [ ] Executor de flows
- [ ] Parser de JSON
- [ ] Handler de steps

### Fase 4: Webhook Handler
- [ ] Recepção de updates
- [ ] Parsing de updates
- [ ] Dispatch de handlers
- [ ] Persistência de logs

### Fase 5: Frontend
- [ ] Dashboard de bots
- [ ] Formulário de criação
- [ ] Builder visual (drag-and-drop)
- [ ] Estatísticas e analytics

### Fase 6: Analytics
- [ ] Coleta de estatísticas
- [ ] Gráficos
- [ ] Exportação de dados

---

## 🔐 Segurança

- [ ] Validação de webhook secret
- [ ] Rate limiting no webhook
- [ ] Encryption de API keys
- [ ] Audit log de mudanças
- [ ] Permission checks (RBAC)
- [ ] Sanitização de inputs
- [ ] CORS headers corretos
- [ ] API key rotation

---

## 📝 Permissões (Spatie/Laravel-permission)

```php
// Roles
- telegram_bot.create
- telegram_bot.read
- telegram_bot.update
- telegram_bot.delete
- telegram_bot.webhook_manage

// Assignment
- admin: todas
- manager: create, read, update
- viewer: read
```

---

## ⏱️ Estimativas

| Fase | Estimativa |
|------|-----------|
| Backend Infrastructure | 2-3 dias |
| Bot Management | 2 dias |
| Flow Builder Backend | 3 dias |
| Flow Builder Frontend | 4-5 dias |
| Webhook Handler | 2 dias |
| Analytics | 2-3 dias |
| **Total** | **15-18 dias** |

---

## 🚀 Próximos Passos

1. ✅ Revisar e validar análise
2. Criar migrations e models
3. Implementar controllers
4. Desenvolver o executador de flows
5. Criar frontend components
6. Integração e testes

---

**Autor**: IA Assistant  
**Data**: 25 de janeiro de 2026  
**Status**: Pronto para implementação
