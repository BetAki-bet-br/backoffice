# 🧠 Guia de Desenvolvimento

Este documento fornece uma visão técnica completa do projeto **Betaki Admin API**, permitindo que agentes de IA entendam a arquitetura, padrões, e convenções do projeto.

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Stack Tecnológico](#stack-tecnológico)
3. [Estrutura do Projeto](#estrutura-do-projeto)
4. [Padrões de Arquitetura](#padrões-de-arquitetura)
5. [Modelos de Dados](#modelos-de-dados)
6. [API REST](#api-rest)
7. [Autenticação e Autorização](#autenticação-e-autorização)
8. [Serviços e Sincronização](#serviços-e-sincronização)
9. [Guia de Desenvolvimento](#guia-de-desenvolvimento)
10. [Configuração de Ambiente](#configuração-de-ambiente)
11. [Padrões de Código](#padrões-de-código)
12. [Banco de Dados](#banco-de-dados)

---

## 🎯 Visão Geral

**Betaki Admin API** é um sistema de gerenciamento administrativo para a plataforma Betaki, especializada em gestão de jogos de cassino (slots) e jogos ao vivo (live games). Atua como Dashboard Central para:

- 🎰 Gerenciamento de Slots e Jogos
- 🎞️ Administração de Banners e Carousels
- 📂 Organização de Categorias de Jogos
- 📋 Configuração de Menus e Rodapés
- 👥 Gestão de Usuários e Permissões (RBAC)
- 🔄 Sincronização com APIs externas
- ⚙️ Configurações Globais do Sistema

---

## 🔧 Stack Tecnológico

### Backend
- **Framework**: Laravel 12
- **PHP**: 8.2+
- **Banco de Dados**: PostgreSQL 16
- **Cache/Session**: Redis 7
- **Queue Worker**: Laravel Horizon
- **Auth**: Laravel Sanctum (Token Bearer)
- **RBAC**: Spatie/laravel-permission v6.22
- **API Docs**: L5-Swagger (OpenAPI 3.0.3)

### Frontend (Assets)
- **Vite**: v7.0.7
- **TailwindCSS**: v4.0.0
- **Axios**: v1.11.0

### DevTools
- **Testing**: PHPUnit 11.5.3
- **Code Quality**: 
  - PHPStan (static analysis)
  - Larastan (PHPStan + Laravel)
  - Laravel Pint (code formatting)
- **Monitoring**: Laravel Pail (logs)
- **Faker**: FakerPHP (test data)

---

## 📁 Estrutura do Projeto

```
/
├── app/                              # Código da aplicação
│   ├── Console/Commands/             # Comandos Artisan
│   ├── Exceptions/
│   │   └── Handler.php              # Handler global de exceções
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php   # Login/Logout/Me
│   │   │   └── Api/V1/              # Controllers da API v1
│   │   ├── Middleware/              # Middlewares customizados
│   │   └── Requests/                # Form Requests (validação)
│   ├── Jobs/
│   │   └── PublishScheduledBannersJob.php
│   ├── Models/
│   │   ├── User.php                 # Modelo de usuário
│   │   ├── Setting.php              # Configurações do sistema
│   │   └── Domain/                  # Modelos do domínio
│   │       ├── Casino/              # Modelos de jogos
│   │       │   ├── Slot.php         # Slots/jogos
│   │       │   ├── Category.php     # Categorias
│   │       │   ├── TopList.php      # Top lists
│   │       │   ├── PortalGame.py    # Jogos sincronizados
│   │       │   └── GameExtra.php    # Metadados de jogos
│   │       ├── Banners/             # Modelos de banners
│   │       └── Navigation/          # Menus, footers
│   ├── OpenApi/
│   │   ├── OpenApiSpec.php          # Definição OpenAPI
│   │   └── Schemas/                 # Schemas OpenAPI
│   ├── Policies/                    # Authorization policies
│   ├── Providers/
│   │   └── AppServiceProvider.php   # Service provider
│   ├── Repositories/                # Repository pattern
│   │   └── PortalGameRepository.php
│   ├── Requests/
│   │   ├── Auth/                    # Validações auth
│   │   ├── Banners/
│   │   ├── Casino/
│   │   └── Navigation/
│   ├── Services/
│   │   └── BaseApi/                 # Serviços de sincronização
│   │       ├── BasePortalApiClient.php     # HTTP client
│   │       ├── PortalGamesSyncService.php  # Sincronizar jogos
│   │       ├── CategorySyncService.php     # Sincronizar categorias
│   │       └── ProviderSyncService.php     # Sincronizar providers
│   └── Support/
│       └── Casino/                  # Helpers/Utilities
│
├── bootstrap/                        # Bootstrap da aplicação
├── config/                           # Arquivos de configuração
│   ├── app.php
│   ├── auth.php
│   ├── database.php
│   ├── permission.php               # Config Spatie/permission
│   ├── services.php                 # Config de serviços externos
│   └── l5-swagger.php               # Config do Swagger
│
├── database/
│   ├── factories/
│   │   └── UserFactory.php          # Factory para testes
│   ├── migrations/                  # Migrations (schema)
│   └── seeders/                     # Database seeders
│
├── docker/                          # Configuração Docker
│   ├── nginx/
│   ├── php/
│   └── Dockerfile
│
├── public/
│   ├── index.php                    # Entrypoint
│   └── api-docs/                    # Docs gerados pelo Swagger
│
├── resources/
│   ├── css/
│   │   └── app.css                  # Tailwind CSS
│   ├── js/
│   │   └── app.js
│   └── views/                       # Views Blade (se houver)
│
├── routes/
│   ├── api.php                      # Rotas da API (principal)
│   ├── web.php                      # Rotas web (se houver)
│   └── console.php                  # Console routes
│
├── storage/
│   ├── app/
│   ├── logs/
│   └── api-docs/                    # Cache de docs
│
├── tests/
│   ├── Feature/                     # Testes de integração
│   ├── Unit/                        # Testes unitários
│   └── TestCase.php
│
├── vendor/                          # Dependências Composer
├── node_modules/                    # Dependências npm
│
├── .env.example                     # Exemplo de variáveis
├── artisan                          # CLI do Laravel
├── composer.json                    # Dependências PHP
├── docker-compose.yml               # Orquestração Docker
├── vite.config.js                   # Config do Vite
├── phpunit.xml                      # Config do PHPUnit
└── README.md                        # Setup inicial
```

---

## 🏗️ Padrões de Arquitetura

### 1. **MVC + Repository Pattern**

O projeto segue o padrão MVC do Laravel com uso opcional de **Repository Pattern**:

```
Request → Route → Controller → Service/Repository → Model → DB
                                                    ↓
                           Response ← Serialization
```

**Exemplo**:
```php
// routes/api.php
Route::apiResource('slots', SlotController::class);

// app/Http/Controllers/Api/V1/SlotController.php
class SlotController extends Controller {
    public function index() {
        $slots = Slot::query()->where('status', 'active')->get();
        return response()->json($slots);
    }
}

// app/Models/Domain/Casino/Slot.php
class Slot extends Model {
    public function categories() {
        return $this->belongsToMany(Category::class, 'category_slot');
    }
}
```

### 2. **Service Layer para Lógica Complexa**

Operações de sincronização e integrações externas usam a **Service Layer**:

```php
// app/Services/BaseApi/PortalGamesSyncService.php
class PortalGamesSyncService {
    public static function make(): self {
        return new self(BasePortalApiClient::fromConfig());
    }
    
    public function syncPortal(int $portalId): array {
        $json = $this->client->getPortalGames($portalId);
        $list = $json['gameMainList'] ?? [];
        
        // Processamento e upsert
        PortalGame::upsert($rows, ...);
    }
}

// Uso no controller
$result = PortalGamesSyncService::make()->syncPortal($portalId);
return response()->json($result);
```

### 3. **Domain-Driven Design (DDD)**

O projeto organiza modelos por domínio:

```
Models/Domain/
├── Casino/          # Domínio de Jogos/Cassino
├── Banners/         # Domínio de Banners/Promoções
└── Navigation/      # Domínio de Navegação/Menu
```

Benefícios:
- Separação clara de contextos
- Facilita manutenção em projetos grandes
- Modelos relacionados ficarão próximos

### 4. **API RESTful com Versionamento**

- **Versão**: `/api/v1/`
- **Padrão**: JSON
- **Autenticação**: Bearer Token (Sanctum)
- **Rate Limiting**: 120 req/min por IP (customizável)

---

## 📊 Modelos de Dados

### Diagrama de Relações

```
Users (1) ──→ (N) Tokens
    ↓
    └─→ Roles ──→ Permissions

Slots (N) ↔ (N) Categories (many-to-many com position)
Slots (N) ↔ (N) TopLists
Slots (1) → (1) GameExtra (by external_id)

Categories (N) → (1) PortalGames
TopLists (N) → (1) PortalGames

Banners (1) → (N) BannerTranslations
Footers (1) → (N) FooterLinks
         (1) → (N) FooterTranslations

Menus (1) → (N) MenuItems (hierarchical)
```

### Principais Modelos

#### **User** (`app/Models/User.php`)
```php
class User extends Authenticatable {
    use HasApiTokens, HasRoles, HasFactory, Notifiable;
    
    protected $fillable = ['name', 'email', 'password', 'status'];
    protected $guard_name = 'api';  // Guard do Spatie
}
```
- **Relações**: Roles (Spatie), Tokens (Sanctum), created_by/updated_by em vários modelos
- **Guard**: `api`
- **Hash**: bcrypt automático

#### **Slot** (`app/Models/Domain/Casino/Slot.php`)
```php
class Slot extends Model {
    protected $fillable = [
        'title', 'cover_url', 'status', 'provider', 
        'provider_game_id', 'tags', 'position'
    ];
    protected $casts = ['tags' => 'array'];
    
    public function categories() {
        return $this->belongsToMany(Category::class, 'category_slot')
            ->withPivot('position');
    }
}
```
- **SoftDeletes**: Deletar logicamente
- **Muitos-para-muitos**: Categories, TopLists com pivot data
- **Relacionamento**: GameExtra (by external_id)

#### **Category** (`app/Models/Domain/Casino/Category.php`)
```php
class Category extends Model {
    protected $casts = [
        'vertical' => 'array',  // ['slots', 'live']
        'meta' => 'array'
    ];
}
```
- **JSON Columns**: `vertical` (tipo de jogo), `meta` (external_id, etc)
- **Slots**: many-to-many

#### **PortalGame** (`app/Models/Domain/Casino/PortalGame.php`)
- **Propósito**: Cache de jogos sincronizados da API base
- **Colunas**: `external_id`, `portal_id`, `name`, `supplier_name`, `payload` (JSON)
- **Sem Model**: Apenas para sincronização (raw queries)

#### **Setting** (`app/Models/Setting.php`)
```php
class Setting extends Model {
    protected $fillable = ['key', 'group', 'type', 'value', 'is_public'];
    protected $casts = ['value' => 'array'];
    
    public function getScalarValue(): mixed {
        return match($this->type) {
            'string'  => is_array($v) ? $v['value'] : $v,
            'boolean' => (bool)(is_array($v) ? $v['value'] : $v),
            // ...
        };
    }
}
```
- **Tipos**: string, number, boolean, array
- **Público**: `is_public` (para endpoints públicos)

#### **Banner** (`app/Models/Domain/Banners/Banner.php`)
- **Translatable**: Has many `BannerTranslations`
- **Status**: draft, scheduled, published
- **Publishing**: Job `PublishScheduledBannersJob`

#### **Footer** (`app/Models/Domain/Navigation/Footer.php`)
```php
class Footer extends Model {
    use SoftDeletes;
    
    public function links() {
        return $this->hasMany(FooterLink::class);
    }
    
    public function translations() {
        return $this->hasMany(FooterTranslation::class);
    }
}
```

---

## 🌐 API REST

### Estrutura de Rotas

#### **Públicas** (sem autenticação)
```
GET    /api/v1/banners              # Listar banners
GET    /api/v1/banners/{id}         # Ver banner
GET    /api/v1/categories           # Listar categorias
GET    /api/v1/categories/slots     # Categorias de slots
GET    /api/v1/slots                # Listar slots
GET    /api/v1/carousels/casino     # Carousel de slots
GET    /api/v1/carousels/live       # Carousel de live
GET    /api/v1/lobbies/casino       # Layout lobby slots
GET    /api/v1/lobbies/live         # Layout lobby live
POST   /api/v1/auth/login           # Login (rate limited)
GET    /api/v1/settings/public      # Configurações públicas
```

#### **Autenticadas** (Bearer Token)
```
GET    /api/v1/auth/me              # Dados do usuário
POST   /api/v1/auth/logout          # Logout

# Banners
POST   /api/v1/banners              # Criar
PUT    /api/v1/banners/{id}         # Editar
DELETE /api/v1/banners/{id}         # Deletar
POST   /api/v1/banners/{id}/publish # Publicar

# Slots
POST   /api/v1/slots                # Criar
PUT    /api/v1/slots/{id}           # Editar
DELETE /api/v1/slots/{id}           # Deletar

# Categorias
POST   /api/v1/categories           # Criar
PUT    /api/v1/categories/{id}      # Editar
POST   /api/v1/categories/sync      # Sincronizar da API base

# Sincronizações
POST   /api/v1/portal-games/sync    # Sincronizar jogos
POST   /api/v1/providers/sync       # Sincronizar providers

# Usuários e Roles
GET    /api/v1/users                # Listar usuários
POST   /api/v1/users                # Criar usuário
GET    /api/v1/roles                # Listar roles
POST   /api/v1/roles                # Criar role

# Menus e Rodapés
GET    /api/v1/menus                # Listar menus
POST   /api/v1/menus/{menu}/items   # Criar item
POST   /api/v1/footers              # Criar footer

# Configurações
GET    /api/v1/settings             # Listar settings
PUT    /api/v1/settings/{id}        # Editar setting
```

### Padrões de Resposta

#### **Sucesso**
```json
{
  "id": 1,
  "name": "Book of Ra",
  "status": "active",
  "created_at": "2025-01-19T10:30:00Z"
}
```

#### **Erro**
```json
{
  "error": {
    "code": "INVALID_CREDENTIALS",
    "message": "Credenciais inválidas."
  }
}
```

#### **Paginação (Cursor)**
```json
{
  "data": [...],
  "meta": {
    "path": "http://...",
    "per_page": 20,
    "next_cursor": "eyJk...",
    "prev_cursor": null
  }
}
```

### Métodos HTTP

| Método | Uso | Status |
|--------|-----|--------|
| GET | Listar/Buscar | 200 OK |
| POST | Criar | 201 Created |
| PUT/PATCH | Atualizar | 200 OK |
| DELETE | Deletar | 204 No Content |

### Validação (Form Requests)

```php
// app/Http/Requests/Casino/SlotRequest.php
class SlotRequest extends FormRequest {
    public function rules(): array {
        return [
            'title' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
            'cover_url' => 'nullable|url',
        ];
    }
}
```

**Erro de Validação**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

### Documentação OpenAPI/Swagger

- **Endpoint**: `http://localhost:8080/api/documentation`
- **Spec JSON**: `http://localhost:8080/api-docs/api-docs.json`
- **Config**: `config/l5-swagger.php`
- **Annotations**: Comentários `@OA\*` nos controllers e requests

Exemplo:
```php
/**
 * @OA\Get(
 *   path="/api/v1/slots",
 *   tags={"Slots"},
 *   summary="Listar slots",
 *   @OA\Response(response=200, description="OK")
 * )
 */
public function index(Request $request) { ... }
```

---

## 🔐 Autenticação e Autorização

### Laravel Sanctum (Token-based)

#### **Login**
```bash
POST /api/v1/auth/login
{
  "email": "admin@example.com",
  "password": "senha123"
}

Resposta:
{
  "token": "1|9HQ5gNSOurJz...",
  "token_type": "Bearer",
  "expires_at": "2025-12-22T14:03:16+00:00"
}
```

#### **Usar Token**
```http
Authorization: Bearer 1|9HQ5gNSOurJz...
```

#### **Logout**
```bash
POST /api/v1/auth/logout
```

Implementação:
```php
// app/Http/Controllers/AuthController.php
public function login(LoginRequest $request) {
    $user = User::where('email', $request->email)->first();
    
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'error' => ['code' => 'INVALID_CREDENTIALS']
        ], 401);
    }
    
    $token = $user->createToken('admin-panel', ['*'], now()->addDays(60));
    return response()->json([...]);
}
```

### Spatie/laravel-permission (RBAC)

#### **Estrutura**
```
User (N:M) ↔ Role (N:M) ↔ Permission
User (N:M) ↔ Permission (direto)
```

#### **Uso**
```php
// Atribuir role
$user->assignRole('admin');

// Verificar permission
if ($user->hasPermissionTo('banners.publish')) {
    // Permitir ação
}

// Na rota
Route::post('banners/{id}/publish', [BannerController::class, 'publish'])
    ->middleware('permission:banners.publish');
```

#### **Roles Padrão**
(Definido em seeders - verificar `RolePermissionSeeder`)
- `admin`: Todas as permissions
- `manager`: Gerenciar conteúdo
- `viewer`: Apenas leitura

#### **Guard**
- User model usa `guard_name = 'api'` (Sanctum)
- Config: `config/permission.php`

---

## 🔄 Serviços e Sincronização

### Integração com API Base

O projeto sincroniza dados com uma **API Base externa** (`services.base_api.url`).

#### **BasePortalApiClient**
```php
class BasePortalApiClient {
    public function getPortalGames(int $portalId): array { ... }
    public function getGameCategories(int $portalId): array { ... }
}
```

**Configuração**:
```php
// config/services.php
'base_api' => [
    'url' => env('BASE_API_URL', 'https://api.base.com'),
    'api_key' => env('BASE_API_KEY'),
    'portal_id' => env('BASE_API_PORTAL_ID', 1),
    'timeout' => 20,
]
```

#### **Serviços de Sincronização**

##### **PortalGamesSyncService**
- **O quê**: Sincroniza jogos da API base para tabela `portal_games`
- **Como**: Faz upsert baseado em `external_id`
- **Endpoint**: `POST /api/v1/portal-games/sync`
- **Payload**: `payload` (JSON completo da API)

```php
$result = PortalGamesSyncService::make()->syncPortal($portalId);
// Retorna: ['portal_id' => 1, 'fetched' => 500, 'upserted' => 150]
```

##### **CategorySyncService**
- **O quê**: Sincroniza categorias de jogos
- **Lógica**: Cria/atualiza via `meta->external_id`
- **Fallback**: Slug único se external_id não existir

```php
$stats = CategorySyncService::make()->syncPortal($portalId);
// ['fetched' => 50, 'created' => 10, 'updated' => 15, 'games_synced' => 200]
```

##### **ProviderSyncService**
- **O quê**: Sincroniza produtoras/providers de jogos
- **Mapping**: `productId` → `external_id`, `productName` → `name`

### Jobs em Background

#### **PublishScheduledBannersJob**
```php
class PublishScheduledBannersJob implements ShouldQueue {
    public function handle() {
        // Publica banners com publish_at <= now()
        Banner::where('status', 'scheduled')
            ->where('publish_at', '<=', now())
            ->update(['status' => 'published']);
    }
}
```

**Queue**: Configurável via `QUEUE_CONNECTION` (default: database)
**Worker**: Laravel Horizon (container `betaki_horizon`)

---

## 🛠️ Guia de Desenvolvimento

### Criando um Novo Endpoint

#### **1. Criar Migration**
```bash
php artisan make:migration create_posts_table
```

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->foreignId('user_id')->constrained();
    $table->timestamps();
});
```

#### **2. Criar Model**
```bash
php artisan make:model Domain/Banners/Post
```

```php
namespace App\Models\Domain\Banners;

class Post extends Model {
    protected $fillable = ['title', 'content', 'user_id'];
    
    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

#### **3. Criar Form Request (Validação)**
```bash
php artisan make:request Banners/PostRequest
```

```php
namespace App\Http\Requests\Banners;

class PostRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }
    
    public function rules(): array {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ];
    }
}
```

#### **4. Criar Controller**
```bash
php artisan make:controller Api/V1/PostController --api --model=Domain/Banners/Post
```

```php
namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Banners\PostRequest;
use App\Models\Domain\Banners\Post;
use OpenApi\Annotations as OA;

class PostController extends Controller {
    /**
     * @OA\Get(path="/api/v1/posts", tags={"Posts"})
     */
    public function index(Request $request) {
        $posts = Post::orderByDesc('id')
            ->cursorPaginate(20);
        return response()->json($posts);
    }
    
    /**
     * @OA\Post(path="/api/v1/posts", tags={"Posts"})
     */
    public function store(PostRequest $request) {
        $post = Post::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);
        return response()->json($post, 201);
    }
}
```

#### **5. Registrar Rota**
```php
// routes/api.php
Route::apiResource('posts', PostController::class);
```

#### **6. Gerar Docs**
```bash
php artisan l5-swagger:generate
```

#### **7. Testar**
```bash
php artisan test
```

### Padrões de Implementação

#### **Query Scope**
```php
// Model
class Post extends Model {
    public function scopeActive($query) {
        return $query->where('status', 'active');
    }
}

// Controller
$posts = Post::active()->get();
```

#### **Eager Loading**
```php
// Sempre fazer para evitar N+1
$posts = Post::with('author', 'comments')->get();
```

#### **Transactions para Múltiplas Operações**
```php
$post = DB::transaction(function () use ($request) {
    $post = Post::create($request->validated());
    
    foreach ($request->tags as $tag) {
        $post->tags()->create($tag);
    }
    
    return $post->load('tags');
});
```

#### **Seeding Data**
```bash
php artisan db:seed
php artisan db:seed --class=PostSeeder
```

---

## ⚙️ Configuração de Ambiente

### Setup Inicial

#### **1. Clone e Instale**
```bash
git clone <repo>
cd backoffice
composer install
npm install
```

#### **2. Configure .env**
```bash
cp .env.example .env
```

```ini
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

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_CONNECTION=redis
# ou database (mais lento)

BASE_API_URL=https://api.base.com
BASE_API_KEY=seu-api-key
BASE_API_PORTAL_ID=1

L5_SWAGGER_GENERATE_ALWAYS=true
```

#### **3. Suba Containers**
```bash
docker compose up -d
```

#### **4. Configure Aplicação**
```bash
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan l5-swagger:generate
```

#### **5. Acesse**
- App: `http://localhost:8080`
- API: `http://localhost:8080/api/v1`
- Docs: `http://localhost:8080/api/documentation`

### Variáveis de Ambiente

| Chave | Padrão | Descrição |
|-------|--------|-----------|
| `APP_ENV` | local | Environment (local, staging, production) |
| `APP_DEBUG` | true | Debug mode |
| `DB_CONNECTION` | pgsql | Database driver |
| `QUEUE_CONNECTION` | redis | Queue driver |
| `CACHE_STORE` | database | Cache store |
| `BASE_API_URL` | — | URL da API externa |
| `L5_SWAGGER_GENERATE_ALWAYS` | true | Regenerar docs em cada request |

### Docker Compose

```yaml
Services:
  - app (PHP-FPM 8.4)
  - web (Nginx)
  - db (PostgreSQL 16)
  - redis (Redis 7)
  - horizon (Laravel Horizon)
```

**Comandos Úteis**:
```bash
docker compose up -d              # Iniciar
docker compose down               # Parar
docker compose logs -f app        # Ver logs do app
docker compose exec app bash      # Entrar no container
```

---

## 📝 Padrões de Código

### Nomeação

| Tipo | Padrão | Exemplo |
|------|--------|---------|
| Classes | PascalCase | `SlotController`, `PortalGame` |
| Métodos | camelCase | `getSlots()`, `syncPortal()` |
| Variáveis | snake_case | `$portal_id`, `$game_data` |
| Constantes | UPPER_SNAKE_CASE | `DEFAULT_TIMEOUT`, `API_VERSION` |
| Arquivos | PascalCase | `SlotController.php`, `BannerRequest.php` |
| Rotas | kebab-case | `/api/v1/portal-games/sync` |
| Banco | snake_case | `portal_games`, `category_slot` |

### Organização de Imports

```php
<?php

namespace App\Http\Controllers\Api\V1;

// Illuminate (Laravel core)
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// App models
use App\Models\Domain\Casino\Slot;

// App requests/responses
use App\Http\Requests\Casino\SlotRequest;

// OpenAPI annotations
use OpenApi\Annotations as OA;
```

### Type Hints

**Sempre usar type hints** (PHP 8.2+):

```php
public function store(SlotRequest $request): JsonResponse {
    $data = $request->validated();
    $slot = Slot::create($data);
    return response()->json($slot, 201);
}

public function getPortalGames(int $portalId): array {
    return $this->client->get("/portal/{$portalId}/games");
}
```

### Comentários e Documentação

**DocBlocks** para métodos públicos:

```php
/**
 * Sincronizar jogos da API base.
 * 
 * @param int $portalId ID do portal
 * @return array Estatísticas de sincronização
 */
public function syncPortal(int $portalId): array {
    // ...
}
```

**Inline comments** para lógica complexa:

```php
// Usar meta->external_id para evitar duplicação
$category = Category::query()
    ->where('meta->external_id', $externalIdStr)
    ->first();
```

### Formatação

- **Indentação**: 4 espaços
- **Linha máxima**: ~120 caracteres
- **Tool**: Laravel Pint (PSR-12)

```bash
./vendor/bin/pint
```

---

## 🗄️ Banco de Dados

### Estrutura Principal

```sql
-- Autenticação
users
├── id (pk)
├── name
├── email (unique)
├── password
├── status
└── timestamps

personal_access_tokens (Sanctum)
├── id (pk)
├── tokenable_id (user_id)
├── name
├── token (unique, hashed)
├── expires_at
└── created_at

-- RBAC (Spatie)
roles
├── id (pk)
├── name
├── guard_name
└── timestamps

permissions
├── id (pk)
├── name
├── guard_name
└── timestamps

model_has_roles (user_id, role_id)
role_has_permissions (role_id, permission_id)

-- Sistema
settings
├── id (pk)
├── key
├── group
├── type (string|number|boolean|array)
├── value (json)
├── is_public (boolean)
├── description
└── timestamps

-- Jogos
slots
├── id (pk)
├── title
├── cover_url
├── status (active|inactive)
├── provider
├── provider_game_id
├── tags (json array)
├── position
├── created_by (fk user_id)
├── updated_by (fk user_id)
├── deleted_at (soft delete)
└── timestamps

categories
├── id (pk)
├── name
├── slug (unique)
├── type (default|featured)
├── vertical (json: ['slots', 'live'])
├── meta (json: {external_id, ...})
├── status (active|inactive)
├── position
└── timestamps

category_slot (pivot)
├── id (pk)
├── category_id (fk)
├── slot_id (fk)
├── position
└── timestamps

portal_games (cache de API)
├── id (pk)
├── portal_id
├── external_id
├── name
├── product_name
├── supplier_name
├── payload (json)
└── timestamps

game_extras
├── id (pk)
├── external_id
├── rtp (float)
├── volatility (string)
├── min_bet (decimal)
├── max_bet (decimal)
├── source
├── meta (json)
└── timestamps

-- Conteúdo
banners
├── id (pk)
├── status (draft|scheduled|published)
├── publish_at
├── published_at
├── created_by (fk)
├── updated_by (fk)
├── published_by (fk)
├── deleted_at
└── timestamps

banner_translations
├── id (pk)
├── banner_id (fk)
├── language_code (pt, en, es)
├── title
├── description
├── cta_text
├── link_url
└── timestamps

footers
├── id (pk)
├── key (unique)
├── status
├── country
├── brand
├── publish_at
├── published_at
└── timestamps

footer_links
├── id (pk)
├── footer_id (fk)
├── title
├── url
├── position
└── timestamps

-- Navegação
menus
├── id (pk)
├── name
├── key (unique)
├── status
└── timestamps

menu_items (hierarchical)
├── id (pk)
├── menu_id (fk)
├── parent_id (fk self)
├── label
├── link_url
├── position
├── created_by (fk)
└── timestamps

-- Jobs
jobs
├── id (pk)
├── queue
├── payload
├── attempts
├── reserved_at
├── available_at
├── created_at

-- Cache
cache
├── key (pk)
├── value
└── expiration
```

### Migrações

Localização: `database/migrations/`

**Convenção de nomes**:
```
YYYY_MM_DD_HHMMSS_action_table.php
```

**Exemplo**:
```php
// 2025_11_03_203428_create_banners_table.php
Schema::create('banners', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->enum('status', ['draft', 'scheduled', 'published'])->default('draft');
    $table->timestamp('publish_at')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->softDeletes();
    $table->timestamps();
});
```

**Executar**:
```bash
php artisan migrate              # Todas as pendentes
php artisan migrate:rollback     # Desfazer último batch
php artisan migrate:refresh      # Refazer todas (⚠️ perde dados)
```

### Seeds

Localização: `database/seeders/`

```php
class DatabaseSeeder extends Seeder {
    public function run() {
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
```

**Executar**:
```bash
php artisan db:seed
php artisan db:seed --class=RolePermissionSeeder
```

### ⚠️ Comandos de Banco em Ambiente Docker

**IMPORTANTE**: Em ambiente Docker, todos os comandos relacionados ao banco de dados (exceto a **criação de migrations**) devem ser executados **dentro do container do PHP**.

#### **Criação de Migrations** (local)
✅ Pode ser executado na máquina local (fora do container):
```bash
php artisan make:migration create_posts_table
php artisan make:migration add_field_to_posts_table
```

#### **Executar Migrations** (dentro do container)
❌ **NÃO fazer** na máquina local
✅ **Fazer** dentro do container:
```bash
# Entrar no container
docker compose exec app bash

# Dentro do container:
php artisan migrate              # Todas as pendentes
php artisan migrate:rollback     # Desfazer último batch
php artisan migrate:refresh      # Refazer todas (⚠️ perde dados)
php artisan migrate:reset        # Desfazer todas as migrations
```

#### **Executar Seeds** (dentro do container)
❌ **NÃO fazer** na máquina local
✅ **Fazer** dentro do container:
```bash
# Entrar no container
docker compose exec app bash

# Dentro do container:
php artisan db:seed              # Executar todos os seeders
php artisan db:seed --class=RolePermissionSeeder  # Seeder específico
```

#### **Refresh Completo** (dentro do container)
```bash
docker compose exec app bash

# Dentro do container - equivalente a migrate:refresh + seed
php artisan migrate:refresh --seed
```

#### **Por quê?**
1. **Conexão com BD**: O container tem acesso direto ao PostgreSQL via `db` (hostname no docker-compose)
2. **Variáveis de Ambiente**: O `.env` está configurado para o container
3. **Dependências**: O container tem PHP e todas as dependências instaladas
4. **Segurança**: Evita problemas com permissões de arquivo e locks

### Relacionamentos

#### **One-to-Many**
```php
// Model
public function slots() {
    return $this->hasMany(Slot::class);
}

// Uso
$category->slots()->attach($slotId);
```

#### **Many-to-Many**
```php
public function categories() {
    return $this->belongsToMany(Category::class, 'category_slot')
        ->withPivot('position')
        ->withTimestamps();
}

// Usar
$slot->categories()->attach($categoryId, ['position' => 1]);
```

#### **Polymorphic** (se usar)
```php
public function created_by() {
    return $this->morphTo();
}
```

---

## 🧪 Testes

### Estrutura

```
tests/
├── Feature/           # Testes de integração (com DB)
├── Unit/              # Testes unitários (sem DB)
└── TestCase.php       # Base para todos os testes
```

### Exemplo: Feature Test
```php
// tests/Feature/SlotApiTest.php
namespace Tests\Feature;

class SlotApiTest extends TestCase {
    public function test_list_slots() {
        $response = $this->getJson('/api/v1/slots');
        
        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }
    
    public function test_create_slot_requires_auth() {
        $response = $this->postJson('/api/v1/slots', []);
        $response->assertStatus(401);
    }
    
    public function test_create_slot_with_permission() {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/v1/slots', [
                'title' => 'Book of Ra',
            ]);
        
        $response->assertStatus(201);
    }
}
```

### Executar Testes
```bash
php artisan test                    # Todos
php artisan test --filter=Slot      # Específico
php artisan test tests/Feature/SlotApiTest.php
```

---

## 📊 Monitoring e Logs

### Laravel Pail (Logs em Tempo Real)
```bash
php artisan pail
php artisan pail --filter=SlotController
```

### Horizon (Queue Monitor)
```bash
# Web dashboard
http://localhost:8080/horizon

# CLI
php artisan horizon
php artisan horizon:pause
php artisan horizon:continue
```

### Logging
```php
use Illuminate\Support\Facades\Log;

Log::info('Sincronização iniciada', ['portal_id' => 1]);
Log::error('Erro na sincronização', ['exception' => $e]);
```

**Canais**: `stack`, `single`, `stderr` (ver `config/logging.php`)

---

## 🚀 Deployment

### Checklist Pre-Deploy
- [ ] Variáveis `.env` configuradas
- [ ] Migrations executadas: `php artisan migrate`
- [ ] Seeds executadas: `php artisan db:seed`
- [ ] Assets compilados: `npm run build`
- [ ] Docs geradas: `php artisan l5-swagger:generate`
- [ ] Tests passando: `php artisan test`
- [ ] Cache otimizado: `php artisan config:cache`
- [ ] Routes cached: `php artisan route:cache`

### Builds Docker
```bash
docker build -f docker/php/Dockerfile -t betaki-api:latest .

docker compose -f docker-compose.yml up -d

# ou com eb
./deploy-eb.sh
```

---

## 🐛 Debugging

### Modo Debug
```php
// Ligar no .env
APP_DEBUG=true

// Usar dd() (dump and die)
dd($variable);

// Usar dump (sem morrer)
dump($variable);
```

### Tinker (REPL)
```bash
php artisan tinker

# Dentro:
> User::count()
> \App\Models\Domain\Casino\Slot::first()
> Auth::user()
```

### Error Handling
```php
// app/Exceptions/Handler.php
public function render($request, Throwable $exception) {
    if ($exception instanceof ValidationException) {
        return response()->json([
            'message' => $exception->getMessage(),
            'errors' => $exception->errors(),
        ], 422);
    }
}
```

---

## 💡 Tips para Agentes IA

1. **Sempre use eager loading**: `with(['relations'])` para evitar N+1
2. **Validação em Form Requests**: Centralizar todas as rules
3. **Transactions para múltiplas operações**: `DB::transaction(fn() => ...)`
4. **Logging em operações críticas**: sincronizações, deletes, etc
5. **Type hints**: Sempre especificar tipos de parâmetros e retorno
6. **Testes**: Criar testes para lógica crítica
7. **Soft deletes**: Usar quando dados não devem ser deletados permanentemente
8. **Rate limiting**: Aplicar para endpoints sensíveis
9. **Authorization**: Sempre verificar permissões em endpoints autenticados
10. **OpenAPI docs**: Manter documentação sincronizada com código

---

## 📚 Recursos Úteis

- [Laravel 12 Docs](https://laravel.com/docs/12.x)
- [Sanctum Docs](https://laravel.com/docs/12.x/sanctum)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
- [L5-Swagger Docs](https://github.com/DarkaOnline/L5-Swagger)
- [OpenAPI 3.0.3 Spec](https://spec.openapis.org/oas/v3.0.3)

---

**Última atualização**: 19 de Janeiro de 2026  
**Versão do Projeto**: 1.0.0  
**Stack**: Laravel 12 / PHP 8.4 / PostgreSQL 16
