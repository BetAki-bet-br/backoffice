# Betaki Admin API

API RESTful para a **Dashboard Administrativa da Betaki**, desenvolvida com **Laravel 12** e **PHP 8.4**, utilizando **PostgreSQL**, **Redis**, **Sanctum** (auth), **Spatie/laravel-permission** (RBAC) e **Swagger (L5-Swagger)** para documentação.

---

## 🚀 Como rodar o projeto

### 1️⃣ Clonar e instalar dependências
git clone <repo-url> backend-backoffice
cd backend-backoffice
composer install

---

### 2️⃣ Configurar variáveis de ambiente
Crie o `.env` baseado no exemplo:

cp .env.example .env

Ajuste os valores principais:
APP_NAME="Betaki Admin API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=betaki
DB_USERNAME=betaki
DB_PASSWORD=betaki

CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

L5_SWAGGER_GENERATE_ALWAYS=true

---

### 3️⃣ Subir containers
docker compose up -d

Containers disponíveis:
- app (PHP-FPM 8.4)
- nginx
- db (PostgreSQL)
- redis
- horizon (filas)

---

### 4️⃣ Configuração inicial (Rode no diretório raiz)
php artisan key:generate
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
php artisan l5-swagger:generate

---

### 5️⃣ Acessos

API Base: http://localhost:8080/api/v1  
Swagger UI: http://localhost:8080/api/documentation  
Spec JSON: http://localhost:8080/api-docs/api-docs.json

---

## 🔐 Autenticação (Laravel Sanctum)

POST /api/v1/auth/login
{
  "email": "admin@exemplo.com",
  "password": "senha123"
}

Resposta:
{
  "token": "1|9HQ5gNSOurJzYR2bCPfw4zuZoyuj1svkDMPQ44ilccd62cba",
  "token_type": "Bearer",
  "expires_at": "2025-12-22T14:03:16+00:00"
}

Use o token em cada requisição:
Authorization: Bearer <token>

---

## 📚 Endpoints disponíveis

### Autenticação
POST /auth/login  
GET /auth/me  
POST /auth/logout

### Settings
GET /settings/public  
CRUD /settings

### Usuários e RBAC
GET /users  
POST /users  
PUT /users/{id}  
DELETE /users/{id}  
GET /roles  
POST /roles  
GET /permissions

### Banners
GET /banners  
POST /banners  
PUT /banners/{id}  
DELETE /banners/{id}  
POST /banners/{id}/publish  

Campos:
- slug, status (draft/scheduled/published)
- countries[], publish_at, expire_at
- media.desktop, media.mobile, translations[]

### Slots (Cassino)
GET /slots  
POST /slots  
GET /slots/{id}  
PUT /slots/{id}  
DELETE /slots/{id}

Campos:
- title, cover_url, status (active/inactive)
- provider, provider_game_id (único por provedor)
- tags[], position

---

## 🧱 Estrutura do projeto

app/
 ├── Http/
 │   ├── Controllers/Api/V1/
 │   ├── Requests/
 │   └── Middleware/
 ├── Models/
 │   └── Domain/
 │       ├── Banners/
 │       └── Casino/
 ├── OpenApi/
 │   ├── Schemas/
 │   └── OpenApiSpec.php
config/
 ├── l5-swagger.php
 └── permission.php
database/
 ├── migrations/
 └── seeders/

---

## 🧩 Status atual

✅ Autenticação (Sanctum)  
✅ RBAC (Roles & Permissions)  
✅ CRUD de Usuários  
✅ CRUD de Settings  
✅ CRUD de Banners + Scheduler  
✅ CRUD de Slots (Cassino)  
✅ Documentação Swagger funcional  

🔜 Próxima etapa: Categorias de Slots e Vitrines (associações N:N + ordenação).

---

## 🧰 Comandos úteis
php artisan key:generate  
php artisan optimize:clear  
php artisan migrate:fresh --seed  
php artisan l5-swagger:generate  
php artisan route:list  
docker compose logs -f