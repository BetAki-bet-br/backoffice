# 🚀 Setup e Deployment Guide - Telegram Bot Management System

---

## 📋 Pré-requisitos

### Sistema Operacional
- macOS, Linux ou Windows (WSL)
- 4GB+ RAM
- 2GB+ espaço em disco

### Software Obrigatório
- PHP 8.2+ (`php -v`)
- Composer 2.x (`composer --version`)
- Node.js 18+ (`node -v`)
- PostgreSQL 15+ (`psql --version`)
- Redis 6+ (`redis-cli --version`)

### Opcional Recomendado
- Docker + Docker Compose (para ambiente consistente)
- Git (`git --version`)
- VS Code com extensions

---

## ⚙️ Configuração Local (Desenvolvimento)

### 1. Preparar Ambiente Backend

```bash
# 1.1 Navegar até projeto
cd /Users/luizbrunolopesreimann/Documents/Repos/backoffice

# 1.2 Instalar dependências PHP
composer install

# 1.3 Copiar arquivo de configuração
cp .env.example .env

# 1.4 Gerar chave de aplicação
php artisan key:generate

# 1.5 Configurar banco de dados em .env
# Editar .env com as credenciais:
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=betaki_backoffice_dev
DB_USERNAME=postgres
DB_PASSWORD=sua_senha

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Telegram Bot Testing (opcional)
TELEGRAM_BOT_TOKEN=seu_token_aqui
```

### 1.6 Criar banco de dados

```bash
# Via psql
psql -U postgres

# Dentro do psql:
CREATE DATABASE betaki_backoffice_dev WITH ENCODING 'UTF8';
\q

# Ou via comando direto
createdb -U postgres betaki_backoffice_dev
```

### 1.7 Executar migrations

```bash
php artisan migrate

# Com seed de dados (se houver)
php artisan migrate:fresh --seed
```

### 1.8 Verificar instalação

```bash
# Verificar que tudo funciona
php artisan tinker

# Dentro do tinker, teste:
>>> App\Models\User::count()
>>> exit
```

### 2. Preparar Ambiente Frontend

```bash
# 2.1 Instalar dependências Node
npm install

# 2.2 Verificar instalação
npm -v
node -v

# 2.3 Iniciar servidor de desenvolvimento
npm run dev

# Frontend estará em: http://localhost:5173
```

### 3. Iniciar Servidor Backend

```bash
# Em outro terminal:
php artisan serve

# Backend estará em: http://localhost:8000
# API em: http://localhost:8000/api/v1
```

### 4. Verificar Configuração

```bash
# Testar chamada à API
curl -X GET http://localhost:8000/api/v1/telegram-bots \
  -H "Authorization: Bearer seu_token_aqui"

# Esperado: lista de bots (vazia inicialmente)
```

---

## 🐳 Configuração com Docker

### Docker Compose Setup

```yaml
# docker-compose.dev.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    ports:
      - "8000:8000"
    environment:
      - DB_HOST=postgres
      - REDIS_HOST=redis
    volumes:
      - .:/var/www/html
    depends_on:
      - postgres
      - redis

  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: betaki_backoffice_dev
      POSTGRES_PASSWORD: secret
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  node:
    image: node:18-alpine
    working_dir: /app
    volumes:
      - .:/app
    ports:
      - "5173:5173"
    command: npm run dev

volumes:
  postgres_data:
```

### Usar Docker Compose

```bash
# Iniciar todos serviços
docker-compose -f docker-compose.dev.yml up -d

# Ver logs
docker-compose -f docker-compose.dev.yml logs -f

# Parar serviços
docker-compose -f docker-compose.dev.yml down

# Executar migration dentro container
docker-compose exec app php artisan migrate
```

---

## 🧪 Testes Local

### Health Check

```bash
# 1. Backend está rodando?
curl http://localhost:8000/health

# 2. Banco de dados conectado?
php artisan tinker
>>> DB::connection()->getPdo()

# 3. Redis conectado?
redis-cli ping
# Esperado: PONG

# 4. Frontend carrega?
open http://localhost:5173
```

### Criar Dados de Teste

```bash
# Via artisan tinker
php artisan tinker

# Criar usuário de teste
>>> $user = User::factory()->create([
    'email' => 'test@betaki.bet',
    'password' => bcrypt('password123')
]);

# Gerar token
>>> $token = $user->createToken('test-token')->plainTextToken;
>>> echo $token;

# Usar em requisições:
curl -H "Authorization: Bearer $token" http://localhost:8000/api/v1/telegram-bots
```

---

## 📱 Configuração do Bot Telegram

### 1. Criar Bot no BotFather

```
No Telegram, abra @BotFather

/newbot
Nome: Meu Bot de Teste
Username: meu_bot_teste_12345

Resultado:
Done! Congratulations on your new bot. You will find it at t.me/meu_bot_teste_12345.
Use this token to access the HTTP API:
123456:ABCdefGHIJKLmnopqrstuvwxyz

Keep your token secure and store it safely
```

### 2. Configurar no Sistema

```bash
# 1. Adicionar token ao .env
TELEGRAM_BOT_TOKEN=123456:ABCdefGHIJKLmnopqrstuvwxyz

# 2. Criar bot via API
curl -X POST http://localhost:8000/api/v1/telegram-bots \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Bot Teste",
    "username": "meu_bot_teste_12345",
    "bot_token": "123456:ABCdefGHIJKLmnopqrstuvwxyz",
    "api_url": "https://api.exemplo.com/validate",
    "api_key": "sua_chave_api",
    "portal_id": 1
  }'

# 3. Configurar webhook
curl -X POST http://localhost:8000/api/v1/telegram-bots/1/webhook/setup \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Expor Localhost (Ngrok)

Para Telegram receber webhooks, URL deve ser pública HTTPS:

```bash
# Instalar ngrok (brew, apt ou direct)
brew install ngrok

# Criar túnel (em novo terminal)
ngrok http 8000

# Saída:
# Forwarding    https://1234-56-78-910-111.ngrok.io -> http://localhost:8000

# Usar URL no webhook setup (sem trailing slash)
https://1234-56-78-910-111.ngrok.io/api/v1/telegram-bots/webhook/{id}/updates
```

---

## 🚀 Deploy em Produção

### Checklist Pré-Deployment

- [ ] Testes passando (100%)
- [ ] Build frontend otimizado
- [ ] Variáveis de ambiente configuradas
- [ ] Banco de dados limpo/migrado
- [ ] Cache limpo
- [ ] Logs configurados
- [ ] Backup feito
- [ ] SSL/TLS ativo
- [ ] Rate limiting ativo
- [ ] Monitoring setup

### 1. Preparar Código

```bash
# 1.1 Atualizar dependências
composer update --no-dev
npm run build

# 1.2 Otimizar autoloader
composer dump-autoload --optimize --no-dev

# 1.3 Limpar cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. Variáveis de Produção (.env.production)

```bash
APP_NAME="Telegram Bot Manager"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com

DB_CONNECTION=pgsql
DB_HOST=seu-db-host.rds.amazonaws.com
DB_PORT=5432
DB_DATABASE=betaki_backoffice_prod
DB_USERNAME=admin
DB_PASSWORD=senha_super_secreta

REDIS_HOST=seu-redis-host.elasticache.amazonaws.com
REDIS_PASSWORD=senha_redis

SANCTUM_STATEFUL_DOMAINS="seu-dominio.com"
SESSION_DOMAIN="seu-dominio.com"

MAIL_HOST=smtp.mailtrap.io
MAIL_USERNAME=seu_username
MAIL_PASSWORD=seu_password

LOG_CHANNEL=stack
LOG_LEVEL=notice

TELEGRAM_BOT_TOKEN=sua_token_produção
```

### 3. Deploy via GitHub Actions

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Install Composer dependencies
        run: composer install --no-dev --optimize-autoloader
      
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install Node dependencies
        run: npm ci
      
      - name: Build frontend
        run: npm run build
      
      - name: Run migrations
        run: php artisan migrate --force
      
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/html
            git pull origin main
            composer install --no-dev
            npm install
            npm run build
            php artisan cache:clear
            php artisan config:cache
            systemctl restart php-fpm
```

### 4. Nginx Configuration

```nginx
# /etc/nginx/sites-available/telegram-bots.conf

server {
    listen 443 ssl http2;
    server_name seu-dominio.com;

    # SSL
    ssl_certificate /etc/ssl/certs/seu-cert.crt;
    ssl_certificate_key /etc/ssl/private/seu-key.key;
    ssl_protocols TLSv1.2 TLSv1.3;

    # Root
    root /var/www/html/public;
    index index.php index.html;

    # Logs
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;

    # PHP
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Vue Router fallback
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Rate limiting webhook
    location ~ /api/v1/telegram-bots/webhook/ {
        limit_req zone=webhook burst=10 nodelay;
        fastcgi_pass 127.0.0.1:9000;
    }
}

# Rate limiting
limit_req_zone $binary_remote_addr zone=webhook:10m rate=1r/s;
```

### 5. Systemd Service

```ini
# /etc/systemd/system/laravel-queue.service

[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html
ExecStart=/usr/bin/php /var/www/html/artisan queue:work
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
# Ativar serviço
sudo systemctl enable laravel-queue.service
sudo systemctl start laravel-queue.service
```

---

## 📊 Monitoring em Produção

### Logs

```bash
# Ver logs em tempo real
tail -f storage/logs/laravel.log

# Buscar erros
grep "ERROR" storage/logs/laravel.log | tail -20

# Logs estruturados (JSON)
cat storage/logs/laravel.log | jq '.message'
```

### Metrics

```bash
# Status da aplicação
php artisan tinker
>>> DB::connection()->getPdo();       // Check DB
>>> Cache::get('test-key');           // Check Redis
>>> Storage::exists('test.txt');      // Check filesystem
```

### Alertas Recomendados

- [ ] CPU > 80%
- [ ] Memory > 85%
- [ ] Disk > 90%
- [ ] Error rate > 1%
- [ ] Response time > 2s
- [ ] Database connection failures

---

## 🔄 Maintenance

### Updates Regulares

```bash
# Atualizar PHP deps
composer update

# Atualizar Node deps
npm update

# Segurança - rodar após updates
php artisan tinker
>>> \Illuminate\Support\Facades\DB::connection()->getPdo()
```

### Backup

```bash
# Backup automático (cron job)
0 2 * * * /usr/bin/pg_dump -U postgres betaki_backoffice_prod > /backups/db_$(date +\%Y\%m\%d).sql

# Backup storage/uploads
0 3 * * * tar -czf /backups/uploads_$(date +\%Y\%m\%d).tar.gz /var/www/html/storage/app/
```

### Cleanup

```bash
# Limpar logs antigos
php artisan logs:clear

# Limpar cache expirado
php artisan cache:prune-stale-tags

# Limpar uploads antigos
php artisan storage:link
```

---

## 🐛 Troubleshooting

### Error: "SQLSTATE[08006]"

```bash
# PostgreSQL não conectando
# Solução 1: Verificar credenciais .env
# Solução 2: Verificar serviço PostgreSQL
pg_isready -h localhost

# Solução 3: Recriar conexão
php artisan migrate:refresh
```

### Error: "Port already in use"

```bash
# Encontrar processo usando porta
lsof -i :8000

# Matar processo
kill -9 PID

# Ou usar porta diferente
php artisan serve --port=8001
```

### Error: "CORS policy"

```bash
# Verificar CORS em config/cors.php
'allowed_origins' => [
    'http://localhost:5173',
    'https://seu-dominio.com'
],

# Limpar cache
php artisan cache:clear
php artisan config:cache
```

### Frontend não conecta API

```javascript
// Verificar URL base em composables
const api = axios.create({
  baseURL: '/api/v1'  // Relativo ao domínio
})

// Em desenvolvimento com vite.config.js
server: {
  proxy: {
    '/api': {
      target: 'http://localhost:8000',
      changeOrigin: true,
    }
  }
}
```

---

## 📞 Suporte

Para problemas ou dúvidas:

1. **Verificar documentação**
   - 01-analysis.md (Arquitetura)
   - 02-development.md (Implementação)
   - 03-interface.md (Frontend)

2. **Logs**
   ```bash
   tail -f storage/logs/laravel.log
   npm run dev  # Ver erros frontend
   ```

3. **Contactar time**
   - Email: dev@betaki.bet
   - Slack: #telegram-bots-dev

---

**Setup Guide - Versão 1.0**  
*Última atualização: 25 de janeiro de 2026*
