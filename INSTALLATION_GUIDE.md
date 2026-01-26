# 🚀 GUIA DE INSTALAÇÃO - Sistema Telegram Bots

**Data**: 25 de janeiro de 2026  
**Versão**: 1.0  
**Status**: Production Ready

---

## 📋 Pré-requisitos

### Obrigatório
- **Laravel**: 10.x ou superior
- **PHP**: 8.1 ou superior
- **MySQL/PostgreSQL**: 5.7+ ou superior
- **Composer**: Instalado e funcional
- **Node.js**: 16+ (opcional, apenas se usar npm build)

### Recomendado
- **Git**: Para versionamento
- **Postman/Insomnia**: Para testar APIs
- **Telegram**: Para testar bots reais

---

## 🔧 Instalação Passo a Passo

### Passo 1: Preparar o Banco de Dados

```bash
# Certifique-se de que o banco está criado
# No arquivo .env, configure:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seu_banco
DB_USERNAME=root
DB_PASSWORD=sua_senha
```

### Passo 2: Executar Migrations

```bash
# No diretório raiz do projeto
php artisan migrate

# Saída esperada:
# Migrating: 2026_01_25_000001_create_telegram_bots_table
# Migrated:  2026_01_25_000001_create_telegram_bots_table (0.12s)
# ... (e mais 5 migrations)
```

**O que será criado**:
```
✓ telegram_bots
✓ bot_flows
✓ bot_flow_steps
✓ bot_users
✓ bot_messages
✓ bot_statistics
```

### Passo 3: Configurar Permissões (Se usar RBAC)

Se estiver usando **Spatie/laravel-permission**:

```bash
# 1. Edite seu seeder (ex: RolePermissionSeeder.php)
# 2. Adicione este código:

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Criar permissões
Permission::firstOrCreate(['name' => 'telegram_bot.create']);
Permission::firstOrCreate(['name' => 'telegram_bot.read']);
Permission::firstOrCreate(['name' => 'telegram_bot.update']);
Permission::firstOrCreate(['name' => 'telegram_bot.delete']);
Permission::firstOrCreate(['name' => 'telegram_bot.webhook_manage']);

// Atribuir ao admin (ou role desejado)
$adminRole = Role::firstOrCreate(['name' => 'admin']);
$adminRole->givePermissionTo([
    'telegram_bot.create',
    'telegram_bot.read',
    'telegram_bot.update',
    'telegram_bot.delete',
    'telegram_bot.webhook_manage',
]);

# 3. Execute o seeder:
php artisan db:seed --class=RolePermissionSeeder
```

### Passo 4: Configurar Variáveis de Ambiente

Edite `.env`:

```bash
# Se não quiser definir por bot (opcional)
TELEGRAM_BOT_TOKEN=seu_token_aqui

# Se usar S3 para uploads
AWS_ACCESS_KEY_ID=xxx
AWS_SECRET_ACCESS_KEY=xxx
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=seu-bucket

# Log
LOG_CHANNEL=stack
LOG_LEVEL=debug # ou production
```

### Passo 5: Limpar Cache (Recomendado)

```bash
php artisan config:cache
php artisan view:cache
php artisan cache:clear
```

### Passo 6: Testar Acesso

```bash
# 1. Inicie o servidor
php artisan serve
# Servidor rodando em http://localhost:8000

# 2. Acesse o backoffice
# http://localhost:8000/telegram-bots

# 3. Faça login com sua conta

# 4. Clique em "Telegram Bots" no menu
```

---

## 🤖 Configurar Primeiro Bot

### Passo 1: Obter Token no Telegram

```
1. Abra o Telegram
2. Procure por @BotFather
3. Envie: /newbot
4. Escolha um nome (ex: "Meu Bot BetAki")
5. Escolha um username (ex: "meu_bot_betaki")
6. Copie o token (ex: 123456789:ABCdefGHIjklmnoPQRstUVwxyzABCdefGH)
7. Salve em local seguro
```

### Passo 2: Criar Bot no Backoffice

```
1. Acesse http://seu-dominio/telegram-bots
2. Clique em "Novo bot"
3. Preencha:
   - Nome: "Meu Bot BetAki"
   - Username: "meu_bot_betaki" (sem @)
   - Bot Token: [Cole o token do @BotFather]
   - Status: "active"
   - API URL: "https://seu-dominio/validate" (sua API)
   - API Key: [Sua chave de API]
   - Portal ID: 1 (ou ID do seu portal)
4. Clique em "Salvar"
```

### Passo 3: Configurar Webhook

```
1. Clique em "Editar" no bot criado
2. Scroll para "Webhook"
3. Copie a URL mostrada
4. Clique em "Setup Webhook"
5. Aguarde confirmação de sucesso
6. Clique em "Testar Webhook"
7. Verifique se aparece "✓ Webhook configurado"
```

### Passo 4: Criar Fluxo Padrão

```
1. Clique em "Fluxos" (linha do bot)
2. Clique em "Novo fluxo"
3. Preencha:
   - Nome: "Boas-vindas"
   - Descrição: "Fluxo inicial"
   - Status: "draft"
   - ☑️ Usar como fluxo padrão
4. Clique "+ Adicionar Step"
5. Adicione um step de tipo "message":
   - Conteúdo: "Bem vindo ao {bot_name}! 👋"
   - Parse mode: "Markdown"
6. Clique em "Salvar Fluxo"
7. Mude status para "active"
8. Clique em "Publicar"
```

### Passo 5: Testar no Telegram

```
1. Abra o Telegram
2. Procure pelo bot criado (@meu_bot_betaki)
3. Envie: /start
4. O bot deve responder com a mensagem do fluxo
5. Acesse "Stats" para ver o log
```

---

## 📁 Estrutura de Arquivos

### Arquivos Criados

```
projeto/
├── app/
│   ├── Models/Domain/Telegram/
│   │   ├── TelegramBot.php
│   │   ├── BotFlow.php
│   │   ├── BotFlowStep.php
│   │   ├── BotUser.php
│   │   ├── BotMessage.php
│   │   └── BotStatistic.php
│   │
│   ├── Services/Telegram/
│   │   ├── TelegramBotService.php
│   │   ├── BotFlowExecutorService.php
│   │   ├── WebhookService.php
│   │   └── BotStatisticService.php
│   │
│   ├── Http/Controllers/
│   │   ├── Api/V1/
│   │   │   ├── TelegramBotController.php
│   │   │   ├── BotFlowController.php
│   │   │   └── BotStatisticController.php
│   │   └── Webhooks/
│   │       └── TelegramWebhookController.php
│   │
│   ├── Http/Requests/Telegram/
│   │   ├── StoreTelegramBotRequest.php
│   │   ├── UpdateTelegramBotRequest.php
│   │   ├── StoreBotFlowRequest.php
│   │   └── UpdateBotFlowRequest.php
│   │
│   └── Http/Resources/Telegram/
│       ├── TelegramBotResource.php
│       ├── TelegramBotDetailResource.php
│       ├── BotFlowResource.php
│       └── BotFlowDetailResource.php
│
├── database/migrations/
│   ├── 2026_01_25_000001_create_telegram_bots_table.php
│   ├── 2026_01_25_000002_create_bot_flows_table.php
│   ├── 2026_01_25_000003_create_bot_flow_steps_table.php
│   ├── 2026_01_25_000004_create_bot_users_table.php
│   ├── 2026_01_25_000005_create_bot_messages_table.php
│   └── 2026_01_25_000006_create_bot_statistics_table.php
│
├── resources/views/content/telegram-bots/
│   ├── index.blade.php
│   ├── flows.blade.php
│   └── statistics.blade.php
│
├── public/js/
│   ├── telegram-bots.js
│   ├── bot-flows.js
│   └── bot-statistics.js
│
├── routes/
│   ├── web.php (atualizado)
│   └── api.php (já criado)
│
├── .development/sprints/2026-01-25/
│   ├── 01-analysis.md
│   ├── 02-development.md
│   ├── 03-frontend.md
│   └── TESTING_CHECKLIST.md
│
├── TELEGRAM_BOTS_README.md (novo)
└── INSTALLATION_GUIDE.md (este arquivo)
```

---

## 🔍 Verificar Instalação

### Checklist de Instalação

```bash
# 1. Verificar migrations
php artisan migrate:status
# Deve mostrar todas as 6 migrations executadas ✓

# 2. Testar rota web
curl http://localhost:8000/telegram-bots -H "Authorization: Bearer YOUR_TOKEN"

# 3. Testar rota API
curl http://localhost:8000/api/v1/telegram-bots -H "Authorization: Bearer YOUR_TOKEN"

# 4. Testar acesso ao backoffice
# Acesse http://localhost:8000/telegram-bots no navegador
# Faça login se necessário
```

### Troubleshooting Comum

**Problema**: 404 ao acessar `/telegram-bots`  
**Solução**: 
```bash
php artisan route:cache
php artisan route:clear
# Verifique se routes/web.php contém as rotas Telegram
```

**Problema**: 500 ao salvar bot  
**Solução**:
```bash
# Verifique se migrations executaram
php artisan migrate:status

# Verifique logs
tail -f storage/logs/laravel.log

# Limpe cache
php artisan config:clear
php artisan cache:clear
```

**Problema**: Webhook não funciona  
**Solução**:
```bash
# Verifique se URL é acessível (não localhost)
curl https://seu-dominio/webhooks/telegram/1

# Verifique token do bot está correto
php artisan tinker
# > $bot = App\Models\Domain\Telegram\TelegramBot::find(1);
# > $bot->bot_token
```

---

## 🌐 Deploy para Produção

### Passos Finais

```bash
# 1. Definir APP_ENV=production
echo "APP_ENV=production" >> .env.production

# 2. Gerar APP_KEY
php artisan key:generate --env=production

# 3. Executar migrations
php artisan migrate --env=production

# 4. Otimizar performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Reiniciar workers (se usar queue)
php artisan queue:restart

# 6. Restart servidor
# Usar supervisor, systemd ou seu gerenciador de processos
```

### Configuração de Webhook para Produção

```bash
# A URL do webhook será:
https://seu-dominio.com/webhooks/telegram/{bot_id}

# Certifique-se de:
# ✓ URL é HTTPS (obrigatório)
# ✓ URL é acessível publicamente
# ✓ Firewall permite acesso
# ✓ SSL certificate é válido
```

---

## 📞 Suporte & Documentação

### Arquivos de Documentação

- **TELEGRAM_BOTS_README.md** - Guia completo de uso
- **01-analysis.md** - Análise de requisitos
- **02-development.md** - Documentação técnica (backend)
- **03-frontend.md** - Documentação técnica (frontend)
- **TESTING_CHECKLIST.md** - Checklist de testes

### Contato

Para dúvidas ou problemas:

1. Consulte a documentação em `.development/sprints/2026-01-25/`
2. Verifique os logs: `storage/logs/laravel.log`
3. Teste APIs com Postman: importar `postman_collection.json`

---

## ✅ Checklist Final

- [ ] PHP 8.1+ instalado
- [ ] Laravel 10+ instalado
- [ ] Banco de dados criado
- [ ] Migrations executadas com sucesso
- [ ] Permissões configuradas (se usar RBAC)
- [ ] Variáveis .env configuradas
- [ ] Acesso ao backoffice funcionando
- [ ] Primeiro bot criado e configurado
- [ ] Webhook testado com sucesso
- [ ] Fluxo padrão criado
- [ ] Bot responde no Telegram
- [ ] Estatísticas funcionando

---

**Instalação Concluída**: 25 de janeiro de 2026

