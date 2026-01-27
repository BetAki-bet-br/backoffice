<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // CORS global
        $middleware->use([
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Grupo "api" minimalista (sem stateful/CSRF)
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\ParseJsonFormData::class,
        ]);

        $middleware->alias([
            'auth'      => \App\Http\Middleware\Authenticate::class,
            'auth.basic'=> \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'can'       => \Illuminate\Auth\Middleware\Authorize::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed'    => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle'  => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified'  => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // 422 de validação — formato padronizado
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code'     => 'VALIDATION_ERROR',
                        'message'  => 'Dados inválidos.',
                        'details'  => $e->errors(),
                        'trace_id' => (string) Str::uuid(),
                    ],
                ], 422);
            }
        });

        // Demais erros → sempre JSON na API
        $exceptions->render(function (Throwable $e, $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null; // deixa o Laravel tratar para rotas não-API
            }

            $status = 500;
            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
            }

            $code = match ($status) {
                401 => 'UNAUTHENTICATED',
                403 => 'FORBIDDEN',
                404 => 'NOT_FOUND',
                405 => 'METHOD_NOT_ALLOWED',
                429 => 'TOO_MANY_REQUESTS',
                default => 'SERVER_ERROR',
            };

            return response()->json([
                'error' => [
                    'code'     => $code,
                    'message'  => $e->getMessage() ?: 'Erro inesperado.',
                    'trace_id' => (string) Str::uuid(),
                ],
            ], $status);
        });
    })->create();