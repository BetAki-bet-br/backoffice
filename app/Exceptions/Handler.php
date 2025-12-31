<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Illuminate\Support\Str;

class Handler extends ExceptionHandler
{
    /**
     * Report or log an exception.
     */
    public function register(): void
    {
        // Você pode adicionar reportables aqui se quiser enviar para Sentry/NewRelic/etc.
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render exceptions into JSON padronizado.
     */
    public function render($request, Throwable $e)
    {
        // API-only: sempre JSON
        $traceId = (string) Str::uuid();
        $debug   = (bool) config('app.debug');

        // 422 - validação
        if ($e instanceof ValidationException) {
            return response()->json([
                'error' => [
                    'code'     => 'VALIDATION_ERROR',
                    'message'  => 'There were validation errors.',
                    'details'  => $e->errors(),
                    'trace_id' => $traceId,
                ],
            ], 422);
        }

        // 401 - não autenticado
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'error' => [
                    'code'     => 'UNAUTHENTICATED',
                    'message'  => 'Authentication required.',
                    'trace_id' => $traceId,
                ],
            ], 401);
        }

        // 403 - sem permissão/política
        if ($e instanceof AuthorizationException) {
            return response()->json([
                'error' => [
                    'code'     => 'FORBIDDEN',
                    'message'  => $e->getMessage() ?: 'You are not allowed to perform this action.',
                    'trace_id' => $traceId,
                ],
            ], 403);
        }

        // 404 - rota ou modelo não encontrado
        if ($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException) {
            return response()->json([
                'error' => [
                    'code'     => 'NOT_FOUND',
                    'message'  => 'Resource not found.',
                    'trace_id' => $traceId,
                ],
            ], 404);
        }

        // 405 - método não permitido
        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'error' => [
                    'code'     => 'METHOD_NOT_ALLOWED',
                    'message'  => 'HTTP method not allowed for this route.',
                    'trace_id' => $traceId,
                ],
            ], 405);
        }

        // 429 - rate limit
        if ($e instanceof ThrottleRequestsException) {
            return response()->json([
                'error' => [
                    'code'     => 'TOO_MANY_REQUESTS',
                    'message'  => 'Too many requests. Please try again later.',
                    'trace_id' => $traceId,
                ],
            ], 429);
        }

        // Erros de banco (genérico)
        if ($e instanceof QueryException) {
            return response()->json([
                'error' => [
                    'code'     => 'DATABASE_ERROR',
                    'message'  => $debug ? $e->getMessage() : 'A database error occurred.',
                    'trace_id' => $traceId,
                ],
            ], 500);
        }

        // HttpExceptions com status específico
        if ($e instanceof HttpExceptionInterface) {
            $status  = $e->getStatusCode();
            $message = $e->getMessage() ?: 'HTTP error.';

            return response()->json([
                'error' => [
                    'code'     => 'HTTP_ERROR',
                    'message'  => $message,
                    'trace_id' => $traceId,
                ],
            ], $status);
        }

        // Fallback 500 - erro não tratado
        return response()->json([
            'error' => [
                'code'     => 'SERVER_ERROR',
                'message'  => $debug ? $e->getMessage() : 'Internal server error.',
                'trace_id' => $traceId,
            ],
        ], 500);
    }
}