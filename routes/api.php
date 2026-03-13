<?php

use App\Http\Controllers\Api\V1\AwardedController;
// Auth
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\CarouselController;
// Settings
use App\Http\Controllers\Api\V1\CategoryController;
// RBAC
use App\Http\Controllers\Api\V1\FooterController;
use App\Http\Controllers\Api\V1\GameExtraSyncController;
// Banners
use App\Http\Controllers\Api\V1\LobbyLayoutController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\MenuItemController;
// Casino
use App\Http\Controllers\Api\V1\PermissionController;
// Categories
use App\Http\Controllers\Api\V1\RoleController;
// Showcases
use App\Http\Controllers\Api\V1\SettingController;
// Menus
use App\Http\Controllers\Api\V1\ShowcaseController;
use App\Http\Controllers\Api\V1\SlotController;
// Top Lists
use App\Http\Controllers\Api\V1\TopListController;
// Awarded
use App\Http\Controllers\Api\V1\TopWinnersController;
// Top Winners
use App\Http\Controllers\AuthController;
// Footers
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Public
    |--------------------------------------------------------------------------
    */
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('auth.login');

    Route::get('settings/public', [SettingController::class, 'publicIndex'])
        ->name('settings.public');

    Route::get('carousels/{slug}', [CarouselController::class, 'show'])
        ->name('carousels.show');

    Route::get('lobbies/casino', [LobbyLayoutController::class, 'casino'])
        ->name('lobbies.casino');

    Route::get('lobbies/live', [LobbyLayoutController::class, 'live'])
        ->name('lobbies.live');

    // Banners
    Route::apiResource('banners', BannerController::class)
        ->only(['index', 'show'])
        ->parameters(['banners' => 'banner'])
        ->names('banners');

    // Slots
    Route::post('slots/by-ids', [SlotController::class, 'byIds'])->name('slots.byIds');
    Route::get('slots/by-external-id/{external_id}', [SlotController::class, 'getByExternalId'])->name('slots.getByExternalId');
    Route::apiResource('slots', SlotController::class)
        ->only(['index', 'show'])
        ->parameters(['slots' => 'slot'])
        ->names('slots');

    // Categories
    Route::get('categories/slots', [CategoryController::class, 'slots'])->name('categories.slots.public');
    Route::get('categories/live', [CategoryController::class, 'live'])->name('categories.live.public');
    Route::apiResource('categories', CategoryController::class)
        ->only(['index', 'show'])
        ->parameters(['categories' => 'category'])
        ->names('categories');

    // Providers
    Route::apiResource('providers', \App\Http\Controllers\Api\V1\ProviderController::class)
        ->only(['index', 'show'])
        ->parameters(['providers' => 'provider'])
        ->names('providers.public');

    // Showcases
    Route::apiResource('showcases', ShowcaseController::class)
        ->only(['index', 'show'])
        ->parameters(['showcases' => 'showcase'])
        ->names('showcases');

    // Menus
    Route::apiResource('menus', MenuController::class)
        ->only(['index', 'show'])
        ->parameters(['menus' => 'menu'])
        ->names('menus');

    Route::get('menus/{menu}/items', [MenuItemController::class, 'index'])->name('menus.items.index');
    Route::get('menus/{menu}/items/{item}', [MenuItemController::class, 'show'])->name('menus.items.show');

    // Top Lists
    Route::apiResource('top-lists', TopListController::class)
        ->only(['index', 'show'])
        ->parameters(['top-lists' => 'top_list'])
        ->names('toplists');

    // Awarded
    Route::prefix('awards')->group(function () {
        Route::get('batches', [AwardedController::class, 'index']);
        Route::get('batches/{batch}', [AwardedController::class, 'show']);
    });

    // Top Winners
    Route::prefix('winners')->group(function () {
        Route::get('batches', [TopWinnersController::class, 'index']);
        Route::get('batches/{batch}', [TopWinnersController::class, 'show']);
    });

    // Footers
    Route::apiResource('footers', FooterController::class)
        ->only(['index', 'show'])
        ->parameters(['footers' => 'footer'])
        ->names('footers');

    // Portal Games Public
    Route::get('public/portal-games/providers', [\App\Http\Controllers\Api\V1\PortalGamesPublicController::class, 'getProviders'])
        ->name('public.portalgames.providers');

    Route::get('public/portal-games/by-provider', [\App\Http\Controllers\Api\V1\PortalGamesPublicController::class, 'getGamesByProvider'])
        ->name('public.portalgames.byProvider');

    /*
    |--------------------------------------------------------------------------
    | Authenticated
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        // Auth helpers
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Settings (admin)
        Route::apiResource('settings', SettingController::class)
            ->parameters(['settings' => 'setting'])
            ->names('settings');

        // Permissions (somente listagem)
        Route::get('permissions', [PermissionController::class, 'index'])
            ->name('permissions.index');

        // Banners
        Route::post('banners/{banner}/publish', [BannerController::class, 'publish'])
            ->middleware('permission:banners.publish')
            ->name('banners.publish');

        Route::apiResource('banners', BannerController::class)
            ->except(['index', 'show'])
            ->parameters(['banners' => 'banner'])
            ->names('banners');

        // Carousels
        Route::apiResource('carousels', CarouselController::class)
            ->except('show')
            ->parameters(['carousels' => 'carousel']);

        // Roles
        Route::apiResource('roles', RoleController::class)
            ->parameters(['roles' => 'role'])
            ->names('roles');

        // Users
        Route::apiResource('users', \App\Http\Controllers\Api\V1\UserController::class)
            ->parameters(['users' => 'user'])
            ->names('users');

        // Slots
        Route::apiResource('slots', SlotController::class)
            ->except(['index', 'show'])
            ->parameters(['slots' => 'slot'])
            ->names('slots');

        // Lobbies (Configuração)
        Route::get('lobbies/{vertical}/config', [\App\Http\Controllers\Api\V1\LobbyController::class, 'show'])
            ->whereIn('vertical', ['slots', 'live'])
            ->name('lobbies.config.show');

        Route::put('lobbies/{vertical}/config', [\App\Http\Controllers\Api\V1\LobbyController::class, 'update'])
            ->whereIn('vertical', ['slots', 'live'])
            ->name('lobbies.config.update');

        // Categories
        Route::post('categories/sync', [CategoryController::class, 'sync'])
            ->name('categories.sync');

        Route::apiResource('categories', CategoryController::class)
            ->except(['index', 'show'])
            ->parameters(['categories' => 'category'])
            ->names('categories');

        Route::put('categories/{category}/slots', [CategoryController::class, 'syncSlots'])
            ->name('categories.slots.sync');

        // Sync Jobs (polling)
        Route::get('sync-jobs/{syncJob}', [\App\Http\Controllers\Api\V1\SyncJobController::class, 'show'])
            ->name('sync-jobs.show');

        // Providers
        Route::post('providers/sync', [\App\Http\Controllers\Api\V1\ProviderController::class, 'sync'])
            ->name('providers.sync');

        Route::put('providers/reorder', [\App\Http\Controllers\Api\V1\ProviderController::class, 'reorder'])
            ->name('providers.reorder');

        Route::post('providers/{provider}/deactivate-slots', [\App\Http\Controllers\Api\V1\ProviderController::class, 'deactivateSlots'])
            ->name('providers.deactivateSlots');

        Route::post('providers/{provider}/activate-slots', [\App\Http\Controllers\Api\V1\ProviderController::class, 'activateSlots'])
            ->name('providers.activateSlots');

        Route::apiResource('providers', \App\Http\Controllers\Api\V1\ProviderController::class)
            ->only(['update'])
            ->parameters(['providers' => 'provider'])
            ->names('providers.admin');

        // Showcases
        Route::apiResource('showcases', ShowcaseController::class)
            ->except(['index', 'show'])
            ->parameters(['showcases' => 'showcase'])
            ->names('showcases');

        Route::put('showcases/{showcase}/slots', [ShowcaseController::class, 'syncSlots'])
            ->name('showcases.slots.sync');

        // Menus and Menu Items
        Route::put('menus/reorder', [MenuController::class, 'reorder'])->name('menus.reorder');

        Route::apiResource('menus', MenuController::class)
            ->except(['index', 'show'])
            ->parameters(['menus' => 'menu'])
            ->names('menus');

        Route::post('menus/{menu}/items', [MenuItemController::class, 'store'])->name('menus.items.store');
        Route::put('menus/{menu}/items/{item}', [MenuItemController::class, 'update'])->name('menus.items.update');
        Route::delete('menus/{menu}/items/{item}', [MenuItemController::class, 'destroy'])->name('menus.items.destroy');

        Route::put('menus/{menu}/items/tree', [MenuItemController::class, 'syncTree'])->name('menus.items.syncTree');

        // Top Lists
        Route::apiResource('top-lists', TopListController::class)
            ->except(['index', 'show'])
            ->parameters(['top-lists' => 'top_list'])
            ->names('toplists');

        Route::put('top-lists/{top_list}/slots', [TopListController::class, 'syncSlots'])
            ->name('toplists.slots.sync');

        Route::post('top-lists/{top_list}/publish', [TopListController::class, 'publish'])
            ->name('toplists.publish');

        // Awarded
        Route::prefix('awards')->group(function () {
            Route::post('batches', [AwardedController::class, 'store']);
            Route::put('batches/{batch}', [AwardedController::class, 'update']);
            Route::delete('batches/{batch}', [AwardedController::class, 'destroy']);

            Route::put('batches/{batch}/results', [AwardedController::class, 'syncResults']);
            Route::post('batches/{batch}/publish', [AwardedController::class, 'publish']);
            Route::post('batches/{batch}/archive', [AwardedController::class, 'archive']);
        });

        // Top Winners
        Route::prefix('winners')->group(function () {
            Route::post('batches', [TopWinnersController::class, 'store']);
            Route::put('batches/{batch}', [TopWinnersController::class, 'update']);
            Route::delete('batches/{batch}', [TopWinnersController::class, 'destroy']);

            Route::put('batches/{batch}/results', [TopWinnersController::class, 'syncResults']);
            Route::post('batches/{batch}/publish', [TopWinnersController::class, 'publish']);
            Route::post('batches/{batch}/archive', [TopWinnersController::class, 'archive']);
        });

        // Footers
        Route::post('footers/{footer}/publish', [FooterController::class, 'publish'])
            ->name('footers.publish');

        Route::put('footers/{footer}/links', [FooterController::class, 'syncLinks'])
            ->name('footers.links.sync');

        Route::apiResource('footers', FooterController::class)
            ->except(['index', 'show'])
            ->parameters(['footers' => 'footer'])
            ->names('footers');

        // Dados da Comtrade
        Route::get('game-extras/external/{externalId}', [\App\Http\Controllers\Api\V1\GameExtraController::class, 'byExternalId'])
            ->name('gameextras.byExternalId');

        Route::get('game-extras/overview', [\App\Http\Controllers\Api\V1\GameExtraOverviewController::class, 'index'])
            ->name('gameextras.overview');

        Route::apiResource('game-extras', \App\Http\Controllers\Api\V1\GameExtraController::class)
            ->parameters(['game-extras' => 'game_extra'])
            ->names('gameextras');

        Route::post('game-extras/sync', [GameExtraSyncController::class, 'sync'])
            ->name('gameextras.sync');

        Route::post('portal-games/sync', [\App\Http\Controllers\Api\V1\PortalGamesSyncController::class, 'sync'])
            ->name('portalgames.sync');

        // SoftSwiss
        Route::post('softswiss/sync', [\App\Http\Controllers\Api\V1\SoftSwissSyncController::class, 'sync'])
            ->name('softswiss.sync');

        // Portal Games
        Route::get('portal-games', [\App\Http\Controllers\Api\V1\PortalGamesController::class, 'index'])
            ->name('portalgames.index');

        Route::get('portal-games/overview', [\App\Http\Controllers\Api\V1\PortalGamesOverviewController::class, 'index'])
            ->name('portalgames.overview');

        // Pré-cadastro de Slots a partir de portal_games
        Route::post('slots/sync-from-portal', [\App\Http\Controllers\Api\V1\PortalSlotsSyncController::class, 'sync'])
            ->name('slots.syncFromPortal');

        // Earnings Reports
        Route::get('earnings-reports/status', [\App\Http\Controllers\Api\V1\EarningsReportController::class, 'status'])
            ->name('earnings.status');
        Route::post('earnings-reports/send', [\App\Http\Controllers\Api\V1\EarningsReportController::class, 'send'])
            ->name('earnings.send');
    });
});
