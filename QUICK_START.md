# 🎯 QUICK START - Sistema Telegram Bots

**Sprint**: 2026-01-25  
**Status**: ✅ PRONTO PARA USO

---

## ⚡ Instalação (5 minutos)

```bash
# 1. Execute migrations
php artisan migrate

# 2. Limpe cache (recomendado)
php artisan config:clear && php artisan cache:clear

# 3. Pronto! Acesse:
http://localhost:8000/telegram-bots
```

---

## 🤖 Criar Bot (5 minutos)

### 1️⃣ Obter Token Telegram
```
1. Abra Telegram → Procure @BotFather
2. Envie: /newbot
3. Escolha nome e username
4. Copie o token
```

### 2️⃣ Criar no Backoffice
```
1. Acesse /telegram-bots
2. Clique "Novo bot"
3. Preencha:
   - Nome: Meu Bot
   - Username: meu_bot_123
   - Bot Token: [cole aqui]
   - API URL: https://api.seu-dominio.com/validate
   - API Key: sua-chave-api
   - Portal ID: 1
4. Salve
```

### 3️⃣ Configurar Webhook
```
1. Clique "Editar" no bot
2. Seção Webhook → Clique "Setup Webhook"
3. Clique "Testar Webhook"
4. Pronto!
```

---

## 📝 Criar Fluxo (10 minutos)

```
1. Clique em "Fluxos" do bot
2. Clique "Novo fluxo"
3. Preencha:
   - Nome: Boas-vindas
   - Status: draft
   - ☑️ Fluxo padrão
4. Clique "+ Adicionar Step"
5. Escolha tipo: message
6. Preencha conteúdo: "Bem vindo! 👋"
7. Salve
8. Mude para "active"
9. Publique
```

---

## 📊 Ver Estatísticas (2 minutos)

```
1. Clique em "Stats" do bot
2. Veja KPIs, gráficos e logs
3. Exporte em CSV se desejar
```

---

## 🧪 Testar no Telegram (2 minutos)

```
1. Abra Telegram
2. Procure seu bot (@meu_bot_123)
3. Envie /start
4. Bot responde com seu fluxo
5. Veja log em Stats
```

---

## 📂 Arquivos Principais

### Frontend
- `/telegram-bots` → Lista de bots
- `/telegram-bots/{id}/flows` → Gerenciar fluxos
- `/telegram-bots/{id}/statistics` → Estatísticas

### Backend
- `GET /api/v1/telegram-bots` → Listar
- `POST /api/v1/telegram-bots` → Criar
- `GET /api/v1/telegram-bots/{id}/flows` → Fluxos
- `GET /api/v1/telegram-bots/{id}/statistics` → Stats

---

## 🔗 Documentação

| Tipo | Arquivo | Para Quem |
|------|---------|----------|
| Guia Rápido | Este arquivo | Qualquer um |
| Uso Completo | TELEGRAM_BOTS_README.md | Usuários |
| Instalação | INSTALLATION_GUIDE.md | DevOps |
| Backend | 02-development.md | Devs |
| Frontend | 03-frontend.md | Devs |
| Testes | TESTING_CHECKLIST.md | QA |
| Índice | INDEX.md | Todos |

---

## 📧 Suporte

### Erro ao criar bot?
→ Verifique se Bot Token está correto  
→ Veja TELEGRAM_BOTS_README.md seção "Troubleshooting"

### Webhook não funciona?
→ Certifique-se URL é HTTPS  
→ Verifique firewall/proxy  
→ Use "Testar Webhook" para debug

### Stats não atualizam?
→ Verifique que bot tem mensagens  
→ Mude o período de filtro  
→ Recarregue a página

---

## ✅ Próximos Passos

1. ✅ Executar migrations
2. ✅ Criar primeiro bot
3. ✅ Configurar webhook
4. ✅ Criar fluxo padrão
5. ✅ Testar no Telegram
6. ✅ Ler documentação completa
7. ✅ Deploy em produção

---

**Pronto para começar!** 🚀

