<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register Spatie Permission middleware alias
        $this->app['router']->aliasMiddleware('permission', \Spatie\Permission\Middleware\PermissionMiddleware::class);
        $this->app['router']->aliasMiddleware('role', \Spatie\Permission\Middleware\RoleMiddleware::class);

        // Proteção do Horizon em ambientes não locais
        if (class_exists(Horizon::class)) {
            Horizon::auth(fn ($request) => app()->environment('local', 'staging'));
        }
        // Limiter padrão da API (120 req/min por IP)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->ip());
        });

        // Limiter de login (5 tentativas/min por IP + email)
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by($email.$request->ip()),
            ];
        });
    }
}
