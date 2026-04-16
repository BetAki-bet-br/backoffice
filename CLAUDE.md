# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

Laravel 12 / PHP 8.4 REST API for the Betaki admin backoffice. PostgreSQL (production), Redis (cache/queues), Laravel Sanctum (auth), Spatie Laravel Permission (RBAC), L5-Swagger for OpenAPI docs, Laravel Horizon (queue worker), AWS S3 (file storage). Cypress for E2E tests on the Blade-based admin UI; PHPUnit for Unit/Feature tests on the API.

## Commands

```bash
# Start all containers (app, web/nginx, db, redis, horizon)
docker compose up -d

# Run all tests (clears config first — see composer.json)
composer test
# or
php artisan test

# Run a single test file / filter
php artisan test tests/Feature/SlotApiTest.php
php artisan test --filter=SlotApiTest::test_index

# Regenerate Swagger docs (required after changing @OA annotations)
php artisan l5-swagger:generate

# Code formatting
vendor/bin/pint

# Static analysis
vendor/bin/phpstan analyse

# List all routes
php artisan route:list

# Fresh migration + seed (dev only)
php artisan migrate:fresh --seed

# Initial seed (roles & permissions)
php artisan db:seed --class=RolePermissionSeeder

# Cypress E2E (against http://localhost:8080)
npm run cypress:open   # interactive
npm run cypress:run    # headless
```

Tests run against SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `QUEUE_CONNECTION=sync`), not PostgreSQL — see `phpunit.xml`.

## Architecture

### Route Structure

All API routes live under `/api/v1/` in `routes/api.php`, split into two groups:

- **Public** — unauthenticated reads (banners, slots, categories, showcases, menus, providers, lobbies, carousels, top-lists, footers, public portal-games, etc.).
- **Authenticated** (`auth:sanctum`) — writes plus admin-only reads.

Convention for most resources: public `apiResource(...)->only(['index','show'])` is declared first, then an authenticated `apiResource(...)->except(['index','show'])` registers the CRUD writes. Sensitive actions (e.g. `banners.publish`) add `->middleware('permission:<resource>.<action>')` from Spatie. Non-CRUD action routes (e.g. `/slots/by-ids`, `/categories/sync`, `/top-lists/{id}/publish`, `/menus/{menu}/items/tree`, `/providers/reorder`) are declared alongside their `apiResource`.

API middleware group is minimal: `ThrottleRequests:api`, `SubstituteBindings`, `ParseJsonFormData` (see `bootstrap/app.php`). No stateful session/CSRF. Spatie `permission` and `role` aliases are registered in `AppServiceProvider`. Rate limits: global `api` = 300 req/min/IP; `login` = 5/min per IP and per (IP+email).

### Error Envelope

`bootstrap/app.php` renders **all** API errors as a consistent JSON envelope:

```json
{ "error": { "code": "VALIDATION_ERROR", "message": "...", "details": { ... }, "trace_id": "<uuid>" } }
```

Codes are mapped from HTTP status (`UNAUTHENTICATED`, `FORBIDDEN`, `NOT_FOUND`, `METHOD_NOT_ALLOWED`, `TOO_MANY_REQUESTS`, `SERVER_ERROR`) plus `VALIDATION_ERROR` (422). Preserve this shape when adding new error paths — don't hand-roll different shapes in controllers.

### Domain Model Organization

Models live under `app/Models/Domain/<Domain>/`:

- `Banners/` — `Banner`, `BannerTranslation`
- `Carousels/` — `Carousel`, `CarouselSlide`
- `Casino/` — `Slot`, `Category`, `Showcase`, `Provider`, `GameExtra`, `PortalGame`, `TopList`, `TopWinner`, `TopWinnerBatch`, `AwardedGame`, `AwardedGameBatch`
- `Navigation/` — `Footer`, `FooterLink`, `FooterTranslation`, `Menu`, `MenuItem`
- `Telegram/` — `BotStatistic`

Root `app/Models/` — `User`, `Setting`, `SyncJob`, `EarningsReportLog`, plus the `Traits/HasS3FileUpload` trait.

All domain models use `SoftDeletes`. N:M pivot tables (`category_slot`, `showcase_slot`, `top_list_slot`) carry a `position` column used for ordering.

### Enums and Status Conventions

- `App\Enums\ActiveStatus` → `active | inactive` (e.g., Slot, Provider).
- `App\Enums\ContentStatus` → `draft | scheduled | published | archived` (e.g., Banner, Carousel, Footer, TopList).
- `App\Enums\BatchStatus` → `draft | review | published | archived` (e.g., AwardedGameBatch, TopWinnerBatch).

Controllers and Requests validate against `<Enum>::values()`.

### Key Patterns

**Cursor pagination for feed-like lists.** `SlotController@index` uses `cursorPaginate(20)`, not `paginate()`. Maintain this when adding similar list endpoints that the front-end scrolls.

**Slot response enrichment.** After paginating `slots`, join `GameExtra` (rtp, volatility, min_bet) and `PortalGame` (gameTypeName) keyed by `provider_game_id` / `external_id`. This same enrichment is repeated in `index`, `show`, `byIds`, and `getByExternalId`. Helpers live in `app/Support/Casino/` — prefer `GameExtraResolver::preloadByExternalIds()` + `enrich()` with `GameMainDTO`/`GameMainMapper` where applicable.

**`ParseJsonFormData` middleware** (`app/Http/Middleware/`) recursively decodes JSON strings embedded in multipart FormData on POST/PUT/PATCH (so images can be uploaded alongside nested JSON fields like `translations[0][media]`). Always applied globally to the `api` group.

**`FileUploadService`** (`app/Services/`) handles S3 uploads for banners, slots, categories. Pass the `UploadedFile`; it returns the public S3 URL (dated path `{resource}/Y/m/d/<random>.<ext>`). Call `deleteImageByUrl()` before replacing an image. Models using the `HasS3FileUpload` trait (see `app/Models/Traits/`) auto-delete old S3 objects on `updating` (when the attribute is dirty) and on `deleting` — declare which attributes via `s3Attributes()`.

**External Portal sync.** `BasePortalApiClient` (`app/Services/BaseApi/`) wraps HTTP calls to the external Betaki portal API (configured in `config/services.php` under `base_api` via `BASE_API_URL` / `BASE_API_KEY` / `BASE_PORTAL_ID`). Sync services (`PortalGamesSyncService`, `CategorySyncService`, `ProviderSyncService`) consume it. Long-running sync endpoints (e.g., `POST /categories/sync`, `POST /providers/sync`, `POST /portal-games/sync`, `POST /game-extras/sync`, `POST /slots/sync-from-portal`) dispatch Horizon jobs (`SyncCategoriesJob`, `SyncProvidersJob`, `PublishScheduledBannersJob`, …) and return a `SyncJob` id that the client polls via `GET /sync-jobs/{syncJob}`.

**OpenAPI annotations** live directly in controllers as `@OA\...` docblocks. The root spec is in `app/OpenApi/OpenApiSpec.php`. Run `php artisan l5-swagger:generate` after any annotation change (or set `L5_SWAGGER_GENERATE_ALWAYS=true` locally). Swagger UI: `http://localhost:8080/api/documentation`.

**Form Requests** live under `app/Http/Requests/<Domain>/` (e.g., `Casino/SlotRequest.php`, `Banners/BannerRequest.php`, `Carousels/StoreCarouselRequest.php`). Keep validation there rather than in controllers.

**Horizon** runs in its own container (`betaki_horizon`). In `AppServiceProvider`, the Horizon dashboard is gated to `local`/`staging` environments only; adjust that guard instead of exposing the dashboard globally.

### Console Commands

Useful `php artisan` commands defined in `app/Console/Commands/`:

- `app:cleanup-slots` — orphan cleanup
- `app:import-categories`, `app:import-game-extras`, `app:import-earnings-log-history`
- `app:migrate-images-to-s3` — one-shot local → S3 migration
- `app:sync-providers-verticals`, `app:update-providers-from-games`
- `app:send-annual-earnings-reports` — parses XLSX and dispatches `SendPlayerEarningsEmailJob` per player (supports `--dry-run`, `--limit`, `--year`)
- `app:earnings-email-status` — status of report dispatches
- `app:fix-category-types`

### Testing Notes

Feature tests cover the primary API surfaces (`SlotApiTest`, `CategoryApiTest`, `LobbyLayoutControllerTest`, `FileUploadIntegrationTest`, `SendPlayerEarningsEmailJobTest`, `AnnualEarningsReportMailTest`, `ProductIncomeServiceTest`, `SendAnnualEarningsReportsCommandTest`). Use the `Tests\TestCase` base class; queue is `sync` so dispatched jobs run inline unless faked. When adding endpoints that hit S3, mock the `s3` disk with `Storage::fake('s3')`.

Cypress specs are organized by UI module under `cypress/e2e/<module>/` with page-objects in `cypress/support/page-objects/`. Base URL is `http://localhost:8080` — the Docker stack must be running.

**API base:** `http://localhost:8080/api/v1`  
**Swagger UI:** `http://localhost:8080/api/documentation`
