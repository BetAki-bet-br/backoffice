# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

Laravel 12 / PHP 8.4 REST API for the Betaki admin backoffice. PostgreSQL (production), Redis (cache/queues), Laravel Sanctum (auth), Spatie Laravel Permission (RBAC), L5-Swagger for OpenAPI docs, Laravel Horizon (queue worker), AWS S3 (file storage).

## Commands

```bash
# Start all containers (app, nginx, db, redis, horizon)
docker compose up -d

# Run all tests
composer test
# or
php artisan test

# Run a single test file
php artisan test tests/Feature/SlotApiTest.php

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
```

Tests run against SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), not PostgreSQL.

## Architecture

### Route Structure

All routes live under `/api/v1/` in `routes/api.php`. The file is split into two groups:

- **Public** — unauthenticated endpoints (reads for banners, slots, categories, showcases, menus, etc.)
- **Authenticated** (`auth:sanctum`) — write operations plus admin-only reads

The pattern for most resources: public `index`/`show` declared first as read-only, then authenticated `except(['index','show'])` for CRUDs. Some write actions require specific Spatie permissions via `->middleware('permission:resource.action')`.

### Domain Model Organization

Models are organized under `app/Models/Domain/` by domain:

- `Domain/Banners/` — `Banner`, `BannerTranslation`
- `Domain/Carousels/` — carousel-related models
- `Domain/Casino/` — `Slot`, `Category`, `Showcase`, `Provider`, `GameExtra`, `PortalGame`, `TopList`, `TopWinner`, `TopWinnerBatch`, `AwardedGame`, `AwardedGameBatch`
- `Domain/Navigation/` — `Footer`, `FooterLink`, `FooterTranslation`, `Menu`, `MenuItem`
- Root `Models/` — `User`, `Setting`, `SyncJob`

All domain models use `SoftDeletes`. N:M pivot tables (`category_slot`, `showcase_slot`, `top_list_slot`) carry a `position` column for ordering.

### Key Patterns

**Slot index uses cursor-based pagination** (`cursorPaginate(20)`), not standard page pagination.

**Slot responses are enriched** by joining `GameExtra` (RTP, volatility, min_bet) and `PortalGame` (gameTypeName) keyed by `provider_game_id`. This pattern repeats in `index`, `show`, `byIds`, and `getByExternalId`.

**`ParseJsonFormData` middleware** (`app/Http/Middleware/`) decodes JSON strings embedded in multipart FormData requests (e.g., when uploading images alongside JSON fields). Applied on POST/PUT/PATCH.

**`FileUploadService`** (`app/Services/`) handles S3 uploads for banners, slots, and categories. Pass the `UploadedFile` directly; it returns the public S3 URL. Always call `deleteImageByUrl()` before replacing an existing image.

**External Portal sync** — `BasePortalApiClient` (`app/Services/BaseApi/`) wraps HTTP calls to an external portal API (configured in `services.base_api`). Sync services (`PortalGamesSyncService`, `CategorySyncService`, `ProviderSyncService`) use it. Background jobs (`SyncCategoriesJob`, `SyncProvidersJob`, `PublishScheduledBannersJob`) run via Horizon.

**OpenAPI annotations** live directly in controllers (`@OA\...` docblocks). The root spec definition is in `app/OpenApi/OpenApiSpec.php`. Run `php artisan l5-swagger:generate` after any annotation change.

**Swagger UI:** `http://localhost:8080/api/documentation`
**API base:** `http://localhost:8080/api/v1`
