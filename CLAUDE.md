# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

Laravel 12 / PHP 8.4 REST API for the Betaki admin backoffice. PostgreSQL 16 (production), Redis 7 (cache/queues), Laravel Sanctum (auth), Spatie Laravel Permission v6 (RBAC, guard `api`), L5-Swagger for OpenAPI 3 docs, Laravel Horizon (queue worker), AWS S3 (file storage). Frontend assets use Vite 7 + TailwindCSS 4; E2E tests run via Cypress.

## Commands

```bash
# Start all containers (app, web/nginx, db, redis, horizon)
docker compose up -d

# Run all tests (clears config first)
composer test
# or
php artisan test

# Run a single test file / filter
php artisan test tests/Feature/SlotApiTest.php
php artisan test --filter=test_list_slots

# Regenerate Swagger docs (required after changing @OA annotations)
php artisan l5-swagger:generate

# Code formatting (Pint / PSR-12)
vendor/bin/pint

# Static analysis (Larastan on top of PHPStan)
vendor/bin/phpstan analyse

# List all routes
php artisan route:list

# Tail logs in real time
php artisan pail

# Cypress E2E (needs app running at localhost:8080)
npm run cypress:open
npm run cypress:run
```

Tests run against SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`), not PostgreSQL — see `phpunit.xml`. Keep migrations/factories compatible with both drivers.

### Docker-first DB workflow

In this project, DB-touching Artisan commands must run **inside the `app` container** (the host `.env` points at the `db` service hostname). Only scaffolding (`make:migration`, `make:model`, etc.) is safe to run on the host.

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan db:seed --class=RolePermissionSeeder
```

## Architecture

### Request lifecycle

Routes are registered in `routes/api.php` (all under `/api/v1/`) and `bootstrap/app.php` wires middleware. The `api` group is deliberately minimal — no stateful session/CSRF, just `throttle:api`, `SubstituteBindings`, and `App\Http\Middleware\ParseJsonFormData`. CORS is global.

All API errors are rendered as a standardized envelope (see `bootstrap/app.php`):

```json
{ "error": { "code": "VALIDATION_ERROR", "message": "...", "details": {...}, "trace_id": "uuid" } }
```

Status → code map: 401 `UNAUTHENTICATED`, 403 `FORBIDDEN`, 404 `NOT_FOUND`, 405 `METHOD_NOT_ALLOWED`, 422 `VALIDATION_ERROR`, 429 `TOO_MANY_REQUESTS`, else `SERVER_ERROR`. New handlers should match this shape.

Rate limiters are defined in `AppServiceProvider::boot()`: `api` = 300 req/min per IP, `login` = 5/min per IP and per (email+IP). Horizon UI is only exposed in `local`/`staging`.

### Route structure

`routes/api.php` is split into **Public** (no auth) and **Authenticated** (`auth:sanctum`) groups. The dominant pattern per resource is: public `apiResource(...)->only(['index','show'])`, then inside the authenticated group `apiResource(...)->except(['index','show'])`. Specific write actions gate on Spatie perms via `->middleware('permission:<resource>.<action>')` (e.g. `banners.publish`).

### Domain layout

Domain models live under `app/Models/Domain/`:

- `Banners/` — `Banner`, `BannerTranslation`
- `Carousels/` — `Carousel`, `CarouselSlide`
- `Casino/` — `Slot`, `Category`, `Showcase`, `Provider`, `GameExtra`, `PortalGame`, `TopList`, `TopWinner`, `TopWinnerBatch`, `AwardedGame`, `AwardedGameBatch`
- `Navigation/` — `Footer`, `FooterLink`, `FooterTranslation`, `Menu`, `MenuItem`
- `Telegram/` — `BotStatistic`

Root `Models/`: `User`, `Setting`, `SyncJob`, `EarningsReportLog`. Traits live in `Models/Traits/` (`HasS3FileUpload`). Shared enums live in `app/Enums/` (`ActiveStatus`, `BatchStatus`, `ContentStatus`, plus `Casino/` subfolder).

All domain models use `SoftDeletes`. N:M pivot tables (`category_slot`, `showcase_slot`, `top_list_slot`) carry a `position` column for ordering — preserve that when adding new pivots.

### Auth & RBAC

`User` model uses `guard_name = 'api'`. All Spatie roles/permissions are also created under the `api` guard — see `database/seeders/RolePermissionSeeder.php`, which is the canonical list. Tokens are Sanctum personal access tokens, issued by `AuthController::login` with 60-day expiry.

### Key patterns

**Cursor pagination.** `SlotController::index` uses `cursorPaginate(20)` — not standard pagination — because slot lists are large. Follow this for similar high-cardinality listings.

**Slot response enrichment.** Slot endpoints (`index`, `show`, `byIds`, `getByExternalId`) join in `GameExtra` (RTP, volatility, min_bet) and `PortalGame` (gameTypeName) keyed by `provider_game_id` / `external_id`. Changes to the Slot payload should be applied consistently across all four.

**`ParseJsonFormData` middleware** (`app/Http/Middleware/ParseJsonFormData.php`) decodes JSON strings embedded inside multipart FormData requests (e.g. `translations[0][media]='{"desktop":"..."}'`). Runs on every POST/PUT/PATCH with a body; recursive, opt-out by validating types downstream.

**`FileUploadService`** (`app/Services/FileUploadService.php`) handles S3 uploads for banners/slots/categories. Uses static methods; pass the `UploadedFile` and it returns a public S3 URL. Always call `deleteImageByUrl()` before replacing an existing image to avoid orphans. `CarouselSlideUploadService` is a related service for carousel slide assets.

**External portal sync.** `App\Services\BaseApi\BasePortalApiClient` wraps the external Portal HTTP API (configured via `services.base_api` → `BASE_API_URL`, `BASE_API_KEY`, `BASE_PORTAL_ID`). `PortalGamesSyncService`, `CategorySyncService`, and `ProviderSyncService` use it and do bulk upserts keyed by `external_id`. Long-running syncs dispatch through Horizon jobs (`SyncCategoriesJob`, `SyncProvidersJob`) and the client polls `GET /sync-jobs/{syncJob}` (backed by the `SyncJob` model) for status.

**Scheduled background jobs.** `PublishScheduledBannersJob` flips scheduled banners to `published` once `publish_at <= now()`. `SendPlayerEarningsEmailJob` drives per-player dispatch of the annual earnings email (see `app/Mail/AnnualEarningsReportMail.php` and `EarningsReportService`). All jobs run via Horizon.

**Earnings reporting.** `EarningsReportService` + `ProductIncomeService` read the bundled XLSX sources and produce per-player summaries; results are tracked via `EarningsReportLog`. Custom artisan command `SendAnnualEarningsReports` kicks off the batch.

**OpenAPI annotations** live directly in controllers (`@OA\...` docblocks); the root spec is `app/OpenApi/OpenApiSpec.php` with shared schemas under `app/OpenApi/Schemas/`. Run `php artisan l5-swagger:generate` after any annotation change (or set `L5_SWAGGER_GENERATE_ALWAYS=true` in `.env` for auto-regen).

**Custom artisan commands** live in `app/Console/Commands/` — they handle one-off imports and data migrations (`ImportCategories`, `ImportGameExtras`, `ImportEarningsLogHistory`, `MigrateImagesToS3`, `CleanupSlotsCommand`, `FixCategoryTypes`, `SyncProvidersVerticalsCommand`, `UpdateProvidersFromGames`, `SendAnnualEarningsReports`, `EarningsEmailStatus`). Prefer adding new one-offs here rather than ad-hoc scripts.

### Endpoints

- **API base:** `http://localhost:8080/api/v1`
- **Swagger UI:** `http://localhost:8080/api/documentation`
- **Spec JSON:** `http://localhost:8080/api-docs/api-docs.json`
- **Horizon (local/staging only):** `http://localhost:8080/horizon`
