<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\AuthController;

// Settings
use App\Http\Controllers\Api\V1\SettingController;

// RBAC
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\PermissionController;

// Banners
use App\Http\Controllers\Api\V1\BannerController;
use App\Http\Controllers\Api\V1\CarouselController;
use App\Http\Controllers\Api\V1\LobbyLayoutController;

// Casino
use App\Http\Controllers\Api\V1\SlotController;

// Categories
use App\Http\Controllers\Api\V1\CategoryController;

// Showcases
use App\Http\Controllers\Api\V1\ShowcaseController;

// Menus
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\MenuItemController;

// Top Lists
use App\Http\Controllers\Api\V1\TopListController;

// Awarded
use App\Http\Controllers\Api\V1\AwardedController;

// Top Winners
use App\Http\Controllers\Api\V1\TopWinnersController;

// Footers
use App\Http\Controllers\Api\V1\FooterController;

// Telegram Bots
use App\Http\Controllers\Api\V1\TelegramBotController;
use App\Http\Controllers\Api\V1\BotFlowController;
use App\Http\Controllers\Api\V1\BotStatisticController;

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

    Route::get('carousels/casino', [CarouselController::class, 'casino'])
        ->name('carousels.casino');

    Route::get('carousels/live', [CarouselController::class, 'live'])
        ->name('carousels.live');

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
        Route::get('batches',         [AwardedController::class, 'index']);
        Route::get('batches/{batch}', [AwardedController::class, 'show']);
    });

    // Top Winners
    Route::prefix('winners')->group(function () {
        Route::get('batches',         [TopWinnersController::class, 'index']);
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

        // Providers
        Route::post('providers/sync', [\App\Http\Controllers\Api\V1\ProviderController::class, 'sync'])
            ->name('providers.sync');

        Route::put('providers/reorder', [\App\Http\Controllers\Api\V1\ProviderController::class, 'reorder'])
            ->name('providers.reorder');

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
            Route::post('batches',          [AwardedController::class, 'store']);
            Route::put('batches/{batch}',   [AwardedController::class, 'update']);
            Route::delete('batches/{batch}',[AwardedController::class, 'destroy']);

            Route::put('batches/{batch}/results',  [AwardedController::class, 'syncResults']);
            Route::post('batches/{batch}/publish', [AwardedController::class, 'publish']);
            Route::post('batches/{batch}/archive', [AwardedController::class, 'archive']);
        });

        // Top Winners
        Route::prefix('winners')->group(function () {
            Route::post('batches',           [TopWinnersController::class, 'store']);
            Route::put('batches/{batch}',    [TopWinnersController::class, 'update']);
            Route::delete('batches/{batch}', [TopWinnersController::class, 'destroy']);

            Route::put('batches/{batch}/results',  [TopWinnersController::class, 'syncResults']);
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

        Route::post('portal-games/sync', [\App\Http\Controllers\Api\V1\PortalGamesSyncController::class, 'sync'])
            ->name('portalgames.sync');

        // Portal Games
        Route::get('portal-games', [\App\Http\Controllers\Api\V1\PortalGamesController::class, 'index'])
            ->name('portalgames.index');

        Route::get('portal-games/overview', [\App\Http\Controllers\Api\V1\PortalGamesOverviewController::class, 'index'])
            ->name('portalgames.overview');

        // Pré-cadastro de Slots a partir de portal_games
        Route::post('slots/sync-from-portal', [\App\Http\Controllers\Api\V1\PortalSlotsSyncController::class, 'sync'])
            ->name('slots.syncFromPortal');

        // Telegram Bots
        Route::apiResource('telegram-bots', TelegramBotController::class);

        Route::post('telegram-bots/{bot}/setup-webhook', [TelegramBotController::class, 'setupWebhook'])
            ->name('telegram-bots.setup-webhook');

        Route::post('telegram-bots/{bot}/test-webhook', [TelegramBotController::class, 'testWebhook'])
            ->name('telegram-bots.test-webhook');

        Route::post('telegram-bots/{bot}/reset-webhook', [TelegramBotController::class, 'resetWebhook'])
            ->name('telegram-bots.reset-webhook');

        // Bot Flows
        Route::post('telegram-bots/{bot}/flows', [BotFlowController::class, 'store'])
            ->name('bot-flows.store');

        Route::get('telegram-bots/{bot}/flows', [BotFlowController::class, 'index'])
            ->name('bot-flows.index');

        Route::get('telegram-bots/{bot}/flows/{flow}', [BotFlowController::class, 'show'])
            ->name('bot-flows.show');

        Route::put('telegram-bots/{bot}/flows/{flow}', [BotFlowController::class, 'update'])
            ->name('bot-flows.update');

        Route::delete('telegram-bots/{bot}/flows/{flow}', [BotFlowController::class, 'destroy'])
            ->name('bot-flows.destroy');

        Route::post('telegram-bots/{bot}/flows/{flow}/duplicate', [BotFlowController::class, 'duplicate'])
            ->name('bot-flows.duplicate');

        Route::post('telegram-bots/{bot}/flows/{flow}/publish', [BotFlowController::class, 'publish'])
            ->name('bot-flows.publish');

        // Bot Statistics
        Route::get('telegram-bots/{bot}/statistics', [BotStatisticController::class, 'summary'])
            ->name('bot-statistics.summary');

        Route::get('telegram-bots/{bot}/statistics/chart', [BotStatisticController::class, 'chartData'])
            ->name('bot-statistics.chart');

        Route::get('telegram-bots/{bot}/statistics/validated-users', [BotStatisticController::class, 'validatedUsers'])
            ->name('bot-statistics.validated-users');

        Route::get('telegram-bots/{bot}/statistics/failed-users', [BotStatisticController::class, 'failedUsers'])
            ->name('bot-statistics.failed-users');

        Route::get('telegram-bots/{bot}/statistics/messages', [BotStatisticController::class, 'messageLogs'])
            ->name('bot-statistics.messages');

        Route::get('telegram-bots/{bot}/statistics/export', [BotStatisticController::class, 'export'])
            ->name('bot-statistics.export');
    });
});

// Telegram Webhooks (Public)
Route::post('/webhooks/telegram/{botId}', [\App\Http\Controllers\Webhooks\TelegramWebhookController::class, 'handle'])
    ->name('webhooks.telegram');
