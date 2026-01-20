# 🚀 Sprint Analysis - 2026-01-19
## Betaki Admin API - Product Owner Requirements

**Data**: 19 de Janeiro de 2026  
**Versão**: 1.0  
**Status**: Em Planejamento  

---

## 📋 Visão Geral da Sprint

Esta sprint compreende 5 requisitos principais relacionados a:
- ✅ **Categorização avançada de jogos** (diferenciação Casino vs Live)
- ✅ **Tipificação dinâmica de categorias** (game-list, recent-games, etc)
- ✅ **Enriquecimento de dados de slots** (RTP, volatilidade, aposta mínima)
- ✅ **Relacionamento entre categorias e jogos** (GET by ID com associações)
- ✅ **Armazenamento de imagens em S3** (ao invés de apenas URLs)

**Esforço Estimado**: 21-25 story points  
**Duração Esperada**: 2-3 sprints (dependendo da equipe)  
**Prioridade**: Alta (impacto direto na experiência do usuário)

---

## 🎯 Requisitos Detalhados

### REQ-001: Diferenciar Categorias (Casino vs Live)

#### Descrição
Permitir que as categorias sejam separadas entre **Cassino** (slots) e **Cassino ao Vivo** (live games), com um filtro na view/API para distinguir as duas vertentes.

#### Análise Técnica

**Banco de Dados:**
- Coluna `vertical` já existe em `categories` como JSON array
- Valores atuais: `['slots']`, `['live']`, `['slots', 'live']`
- **Não requer migração** (já suporta múltiplos tipos)

**API:**
```
GET /api/v1/categories?vertical=slots       # Apenas slots
GET /api/v1/categories?vertical=live        # Apenas live
GET /api/v1/categories                      # Todos (sem filtro)
```

**Frontend/View:**
- Query parameter `vertical` ou tabs separadas
- Exibir icon/badge indicando tipo

#### Tarefas
1. ✅ Adicionar validação no `CategoryRequest` (valores: `slots`, `live`)
2. ✅ Implementar scope no Model `Category` para filtrar por `vertical`
3. ✅ Adicionar query parameter `vertical` no controller index
4. ✅ Adicionar filtro nos testes
5. ✅ Documentar no OpenAPI (Swagger)
6. ✅ Atualizar frontend com tabs/filtros

**Estimativa**: 3-4 story points  
**Dependências**: Nenhuma  
**Complexidade**: Baixa

---

### REQ-002: Adicionar Type para Categorias

#### Descrição
Criar um novo campo `type` em categorias com valores predefinidos:
- `game-list` - Lista genérica de jogos
- `recent-games` - Jogos recentes
- `mais-premiados` - Jogos com maiores prêmios
- `winners-list` - Jogos com mais vencedores
- `top-10-list` - Top 10 jogos
- `providers-carousel` - Carousel de produtoras

#### Análise Técnica

**Banco de Dados:**
```sql
ALTER TABLE categories ADD COLUMN type VARCHAR(50) DEFAULT 'game-list';
ALTER TABLE categories ADD CHECK (type IN ('game-list', 'recent-games', 'mais-premiados', 'winners-list', 'top-10-list', 'providers-carousel'));
```

**Model Migration:**
```php
Schema::table('categories', function (Blueprint $table) {
    $table->enum('type', [
        'game-list',
        'recent-games',
        'mais-premiados',
        'winners-list',
        'top-10-list',
        'providers-carousel'
    ])->default('game-list')->after('vertical');
});
```

**Model Category:**
```php
protected $fillable = [..., 'type'];
protected $casts = [
    'vertical' => 'array',
    'meta' => 'array',
];

const TYPES = [
    'game-list' => 'Lista de Jogos',
    'recent-games' => 'Jogos Recentes',
    'mais-premiados' => 'Mais Premiados',
    'winners-list' => 'Vencedores',
    'top-10-list' => 'Top 10',
    'providers-carousel' => 'Carousel de Produtoras',
];
```

**Request Validation:**
```php
'type' => 'required|in:game-list,recent-games,mais-premiados,winners-list,top-10-list,providers-carousel'
```

#### Tarefas
1. ✅ Criar migration adicionando coluna `type` (com default)
2. ✅ Atualizar Model `Category` com fillable, casts e constantes
3. ✅ Atualizar `CategoryRequest` com validação do campo
4. ✅ Atualizar controller para aceitar o campo
5. ✅ Documentar tipos no OpenAPI
6. ✅ Criar seeder para categorias padrão com tipos
7. ✅ Adicionar testes para validação do tipo

**Estimativa**: 4-5 story points  
**Dependências**: Nenhuma  
**Complexidade**: Baixa-Média

**⚠️ Nota Importante**: Será um campo obrigatório. Categorias existentes receberão `game-list` como default durante a migração.

---

### REQ-003: Enriquecer Dados de Slots (RTP, Volatilidade, Aposta Mínima/Máxima)

#### Descrição
Adicionar três novos campos na criação/edição de slots:
- `rtp` (Return to Player) - percentual (0-100)
- `volatility` (Volatilidade) - enum (low, medium, high)
- `min_bet` (Aposta Mínima) - decimal
- `max_bet` (Aposta Máxima) - decimal (optional)

#### Análise Técnica

**Banco de Dados:**
```sql
ALTER TABLE slots ADD COLUMN rtp DECIMAL(5, 2) NULL;
ALTER TABLE slots ADD COLUMN volatility VARCHAR(20) NULL;
ALTER TABLE slots ADD COLUMN min_bet DECIMAL(12, 2) NULL;
ALTER TABLE slots ADD COLUMN max_bet DECIMAL(12, 2) NULL;
```

**Migration:**
```php
Schema::table('slots', function (Blueprint $table) {
    $table->decimal('rtp', 5, 2)->nullable()->after('provider_game_id')->comment('Return to Player percentage');
    $table->enum('volatility', ['low', 'medium', 'high'])->nullable()->after('rtp')->comment('Game volatility');
    $table->decimal('min_bet', 12, 2)->nullable()->after('volatility')->comment('Minimum bet allowed');
    $table->decimal('max_bet', 12, 2)->nullable()->after('min_bet')->comment('Maximum bet allowed');
});
```

**Model Slot:**
```php
protected $fillable = [
    'title', 'cover_url', 'status', 'provider',
    'provider_game_id', 'tags', 'position',
    'rtp', 'volatility', 'min_bet', 'max_bet'
];

protected $casts = [
    'tags' => 'array',
    'rtp' => 'float',
    'min_bet' => 'decimal:2',
    'max_bet' => 'decimal:2',
];

const VOLATILITY_TYPES = ['low', 'medium', 'high'];
```

**Request Validation:**
```php
'rtp' => 'nullable|numeric|min:0|max:100',
'volatility' => 'nullable|in:low,medium,high',
'min_bet' => 'nullable|numeric|min:0.01',
'max_bet' => 'nullable|numeric|min:min_bet',
```

**Integração com GameExtra:**
- Campo `game_extras` já armazena metadados em JSON
- Considerar unificar dados aqui ou sincronizar com `GameExtra`
- **Decisão**: Manter em `slots` para simplicidade, `game_extras` para dados de terceiros

#### Tarefas
1. ✅ Criar migration adicionando 4 colunas (rtp, volatility, min_bet, max_bet)
2. ✅ Atualizar Model `Slot` com fillable, casts e constantes
3. ✅ Atualizar `SlotRequest` com validações
4. ✅ Atualizar `SlotController` store/update
5. ✅ Documentar campos no OpenAPI
6. ✅ Criar factory com dados realistas para testes
7. ✅ Adicionar testes de validação
8. ✅ Considerar sincronização com `game_extras` (se aplicável)

**Estimativa**: 5-6 story points  
**Dependências**: Nenhuma (pode ser feito em paralelo)  
**Complexidade**: Média

**⚠️ Validação Crítica**: 
- `rtp` deve estar entre 0 e 100
- `max_bet` deve ser >= `min_bet` (validação custom)
- Todos são opcionais inicialmente (muitos slots atuais podem não ter dados)

---

### REQ-004: GET /api/v1/categories/{id} com Jogos Associados

#### Descrição
Ao recuperar uma categoria por ID, retornar também os jogos (slots) associados a essa categoria, com paginação opcional.

#### Análise Técnica

**Endpoint Atual:**
```
GET /api/v1/categories/{id}
```

**Resposta Desejada:**
```json
{
  "id": 1,
  "name": "Slots Populares",
  "slug": "slots-populares",
  "type": "game-list",
  "vertical": ["slots"],
  "status": "active",
  "slots": [
    {
      "id": 1,
      "title": "Book of Ra",
      "cover_url": "...",
      "provider": "Novomatic",
      "rtp": 96.5,
      "position": 1
    },
    ...
  ],
  "slots_count": 15
}
```

**Query String (opcional):**
```
GET /api/v1/categories/{id}?with_slots=true&slots_per_page=10&slots_page=1
```

**Relacionamento:**
- `Category` já possui `hasMany('slots', 'category_slot')`
- Usar eager loading: `with('slots')`

#### Tarefas
1. ✅ Atualizar `CategoryController::show()` com eager loading
2. ✅ Implementar paginação opcional para slots (query params)
3. ✅ Criar resource/transformer para resposta estruturada
4. ✅ Adicionar filtros (status, ordem, limite)
5. ✅ Documentar no OpenAPI
6. ✅ Testar N+1 queries (usar eager loading)
7. ✅ Adicionar testes

**Estimativa**: 3-4 story points  
**Dependências**: REQ-003 (para dados completos de slots)  
**Complexidade**: Baixa-Média

**⚠️ Consideração de Performance**:
- Se categoria tem 1000+ slots, vai retornar muitos dados
- **Solução**: Implementar paginação por padrão
- Query limite: máx 50 slots por página

---

### REQ-005: Armazenar Imagens em S3 (AWS) ao criar Anexos

#### Descrição
Ao fazer upload de imagens (banners, slots, categorias), armazenar o arquivo física em bucket S3 da AWS ao invés de apenas armazenar a URL. Configurar via variáveis `.env`.

#### Análise Técnica

**Estrutura Atual:**
- Banners, slots, categorias usam `cover_url` (string)
- Atualmente apenas URL linkada

**Novo Fluxo:**
```
Upload → Validar → S3 Upload → Obter URL → Salvar no DB
```

**Configuração S3 (.env):**
```ini
AWS_ACCESS_KEY_ID=seu-access-key
AWS_SECRET_ACCESS_KEY=seu-secret-key
AWS_DEFAULT_REGION=sa-east-1
AWS_BUCKET=betaki-admin-assets
AWS_URL=https://betaki-admin-assets.s3.sa-east-1.amazonaws.com
```

**Config Laravel:**
```php
// config/filesystems.php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => false,
    ]
]
```

**Serviço de Upload:**
```php
// app/Services/FileUploadService.php
class FileUploadService {
    public static function uploadBannerImage(UploadedFile $file): string {
        $path = 'banners/' . now()->format('Y/m/d');
        $filename = Str::random(32) . '.' . $file->getClientOriginalExtension();
        
        Storage::disk('s3')->putFileAs($path, $file, $filename, 'public');
        return Storage::disk('s3')->url("{$path}/{$filename}");
    }
    
    public static function deleteImage(string $url): bool {
        // Extract path from S3 URL and delete
        $path = str_replace(env('AWS_URL') . '/', '', $url);
        return Storage::disk('s3')->delete($path);
    }
}
```

**Request Validation:**
```php
'cover_url' => 'required|image|mimes:jpeg,png,webp,gif|max:2048',
// Aceita arquivo ao invés de URL
```

**Model Update:**
```php
class Banner extends Model {
    protected static function booting() {
        static::updating(function ($banner) {
            if (request()->hasFile('cover_url')) {
                // Delete old image
                if ($banner->cover_url) {
                    FileUploadService::deleteImage($banner->cover_url);
                }
                $banner->cover_url = FileUploadService::uploadBannerImage(
                    request()->file('cover_url')
                );
            }
        });
    }
}
```

#### Tarefas
1. ✅ Instalar/configurar pacote `aws/aws-sdk-php-laravel`
2. ✅ Configurar `config/filesystems.php` para S3
3. ✅ Adicionar variáveis no `.env.example`
4. ✅ Criar serviço `FileUploadService` com métodos de upload/delete
5. ✅ Atualizar Form Requests para aceitar arquivo
6. ✅ Atualizar Models (Banner, Slot, Category) com mutadores
7. ✅ Implementar soft delete de arquivos antigos
8. ✅ Criar comando artisan para migrar imagens existentes
9. ✅ Documentar novo fluxo de upload
10. ✅ Adicionar testes de upload

**Estimativa**: 7-8 story points  
**Dependências**: Credenciais AWS disponíveis  
**Complexidade**: Média-Alta

**⚠️ Questões Críticas**:
1. **Imagens existentes**: Como migrar URLs existentes?
   - Solução: Comando artisan que faz download e upload para S3
2. **Validação de acesso**: Arquivos devem ser públicos ou privados?
   - Recomendação: Público (melhor performance, sem signed URLs)
3. **Cleanup**: Deletar imagens antigas ao atualizar?
   - Solução: Implementar soft delete com job background
4. **Performance**: Cache de URLs em Redis?
   - Recomendação: Deixar para otimização futura

**Necessidades Pré-Requisito**:
- Bucket S3 criado
- IAM user com permissões (GetObject, PutObject, DeleteObject)
- Variáveis `.env` configuradas

---

## 📊 Priorização e Dependências

```
┌─────────────────────────────────────────┐
│  REQ-001: Vertical Filter               │
│  (3-4 pts) - SEM DEPENDÊNCIAS           │
└─────────────────────────────────────────┘
                    ↓
        ┌───────────┴───────────┐
        ↓                       ↓
┌─────────────────┐  ┌──────────────────┐
│ REQ-002: Type   │  │ REQ-003: Slots   │
│ (4-5 pts)       │  │ Data (5-6 pts)   │
│ SEM DEP.        │  │ SEM DEPENDÊNCIAS │
└─────────────────┘  └──────────────────┘
        ↓                       ↓
        └───────────┬───────────┘
                    ↓
        ┌──────────────────────┐
        │ REQ-004: Get by ID   │
        │ (3-4 pts)            │
        │ ← Depende de REQ-003 │
        └──────────────────────┘
                    ↓
        ┌──────────────────────┐
        │ REQ-005: S3 Upload   │
        │ (7-8 pts)            │
        │ SEM DEPENDÊNCIAS     │
        └──────────────────────┘
```

**Ordem Recomendada de Implementação:**

1. **Fase 1 (Paralelo)** - Dias 1-2
   - REQ-001: Vertical Filter (3-4 pts)
   - REQ-002: Category Type (4-5 pts)
   - REQ-003: Slots Data (5-6 pts)
   - **Total**: 12-15 pts

2. **Fase 2** - Dia 3
   - REQ-004: Get by ID com Slots (3-4 pts)
   - **Depende**: REQ-003 completo
   - **Total**: 3-4 pts

3. **Fase 3** - Dias 4-5
   - REQ-005: S3 Upload (7-8 pts)
   - **Independente**, pode ser paralelo com fase 2
   - **Total**: 7-8 pts

**Duração Total**: 21-25 story points ≈ 2-3 sprints (3 devs) ou 1 sprint (5+ devs)

---

## 🔧 Implementação por Requisito

### REQ-001: Vertical Filter

**Arquivos a Modificar:**
```
database/migrations/XXXX_xx_xx_xxxxxx_categories_table.php (já existe)
app/Models/Domain/Casino/Category.php
app/Http/Requests/Casino/CategoryRequest.php
app/Http/Controllers/Api/V1/CategoryController.php
tests/Feature/CategoryApiTest.php
```

**Exemplo de Scope:**
```php
// Category.php
public function scopeByVertical($query, string $vertical) {
    return $query->whereJsonContains('vertical', $vertical);
}

// Uso
Category::byVertical('slots')->get();
```

---

### REQ-002: Category Type

**Arquivos a Criar:**
```
database/migrations/2026_01_19_XXXXXX_add_type_to_categories_table.php (NEW)
```

**Arquivos a Modificar:**
```
app/Models/Domain/Casino/Category.php
app/Http/Requests/Casino/CategoryRequest.php
app/Http/Controllers/Api/V1/CategoryController.php
database/seeders/CategorySeeder.php
tests/Feature/CategoryApiTest.php
```

---

### REQ-003: Slots Data

**Arquivos a Criar:**
```
database/migrations/2026_01_19_XXXXXX_add_betting_data_to_slots_table.php (NEW)
database/factories/SlotFactory.php (UPDATE)
```

**Arquivos a Modificar:**
```
app/Models/Domain/Casino/Slot.php
app/Http/Requests/Casino/SlotRequest.php
app/Http/Controllers/Api/V1/SlotController.php
tests/Feature/SlotApiTest.php
```

---

### REQ-004: Get by ID com Slots

**Arquivos a Modificar:**
```
app/Http/Controllers/Api/V1/CategoryController.php (show method)
app/Http/Resources/CategoryResource.php (CREATE NEW)
app/Http/Resources/SlotResource.php (CREATE NEW)
tests/Feature/CategoryApiTest.php
```

**Controller Atualizado:**
```php
public function show(Category $category) {
    return response()->json(
        CategoryResource::make(
            $category->load('slots')
        )
    );
}
```

---

### REQ-005: S3 Upload

**Arquivos a Criar:**
```
app/Services/FileUploadService.php (NEW)
app/Console/Commands/MigrateImagesToS3.php (NEW)
database/migrations/2026_01_19_XXXXXX_add_s3_columns.php (optional)
tests/Feature/FileUploadTest.php (NEW)
```

**Arquivos a Modificar:**
```
config/filesystems.php
.env.example
app/Models/Domain/Banners/Banner.php
app/Models/Domain/Casino/Slot.php
app/Http/Requests/Banners/BannerRequest.php (file validation)
app/Http/Requests/Casino/SlotRequest.php (file validation)
tests/Feature/BannerApiTest.php
```

---

## 🚨 Riscos e Mitigações

| Risco | Severidade | Mitigação |
|-------|-----------|-----------|
| Imagens existentes em S3 (REQ-005) | Alto | Criar comando de migração; testar em staging primeiro |
| Validação circular min_bet/max_bet (REQ-003) | Média | Usar validação custom Rule class |
| N+1 queries em GET categoria (REQ-004) | Média | Eager load slots; adicionar testes de query count |
| Credenciais AWS não configuradas | Médio | Usar .env.example com placeholders; documentar setup |
| Performance com muitos slots (REQ-004) | Médio | Implementar paginação; limitar default a 10 slots |
| Soft delete de imagens S3 | Médio | Usar job background; implementar retry logic |

---

## ✅ Critérios de Aceitação

### REQ-001
- [ ] Categorias filtram por `vertical=slots` ou `vertical=live`
- [ ] Query param `vertical` é opcional
- [ ] Testes validam filtro
- [ ] OpenAPI documentado

### REQ-002
- [ ] Coluna `type` adicionada com enum values
- [ ] Default é `game-list`
- [ ] Categorias existentes recebem default
- [ ] Validação funciona em store/update
- [ ] Testes validam enum

### REQ-003
- [ ] Colunas `rtp`, `volatility`, `min_bet`, `max_bet` adicionadas
- [ ] Validação: rtp 0-100, volatility enum, min_bet > 0, max_bet >= min_bet
- [ ] Factory gera dados realistas
- [ ] Testes validam ranges

### REQ-004
- [ ] GET /api/v1/categories/{id} retorna slots associados
- [ ] Resposta inclui `slots_count`
- [ ] Paginação funciona (query params)
- [ ] Eager loading otimizado (sem N+1)

### REQ-005
- [ ] Arquivo upload salva em S3
- [ ] URL retornada aponta para S3
- [ ] Arquivo antigo deletado ao atualizar
- [ ] Comando migra imagens existentes
- [ ] Testes simulam S3 (mock)

---

## 📈 Métricas de Sucesso

- ✅ Todos os requisitos implementados
- ✅ 100% cobertura de testes (>80% coverage)
- ✅ Zero erros de validação em produção
- ✅ Performance: <200ms para GET /categories
- ✅ Documentação OpenAPI completa
- ✅ Documentação em README.md

---

## 📅 Timeline Estimada

```
Sprint: 19-29 de Janeiro (2 semanas)

Seg 20  ├─ REQ-001 + REQ-002 (análise + setup)
Ter 21  ├─ REQ-001 + REQ-002 + REQ-003 (implementação)
Qua 22  ├─ REQ-001 + REQ-002 + REQ-003 (testes)
Qui 23  ├─ REQ-004 (implementação + testes)
Sex 24  ├─ REQ-005 (setup S3 + implementação)

Seg 27  ├─ REQ-005 (testes + command migração)
Ter 28  ├─ Code Review + Fixes
Qua 29  ├─ Staging + QA
Qui 30  ├─ Production Deploy
Sex 31  ├─ Monitoring + Hotfixes (se necessário)
```

---

## 🔄 Next Steps

1. **Aprovação**: Validar priorização com Product Owner
2. **Setup**: Preparar credenciais AWS (S3 bucket + IAM user)
3. **DB**: Criar migrations para REQ-002, REQ-003, REQ-005
4. **Implementation**: Seguir ordem de priorização
5. **Testing**: Testes para cada requisito
6. **Documentation**: Atualizar OpenAPI + README
7. **Deploy**: Staging → Production

---

## 📚 Referências

- [Laravel Storage S3](https://laravel.com/docs/12.x/filesystem#s3)
- [Spatie File Upload Best Practices](https://spatie.be/docs/laravel-medialibrary)
- [AWS S3 Best Practices](https://docs.aws.amazon.com/AmazonS3/latest/userguide/BestPractices.html)
- [OpenAPI 3.0.3 Docs](https://spec.openapis.org/oas/v3.0.3)

---

**Data**: 19 de Janeiro de 2026  
**Versão**: 1.0  
**Status**: Pronto para Implementação ✅
