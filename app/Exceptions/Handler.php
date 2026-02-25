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

        // Erros de banco
        if ($e instanceof QueryException) {
            $sqlState = $e->errorInfo[0] ?? null;
            $driverCode = (int) ($e->errorInfo[1] ?? 0);

            // Unique constraint violation (MySQL 1062, PG 23505, SQLite 19/2067)
            if ($sqlState === '23505' || $driverCode === 1062 || $driverCode === 2067 || $driverCode === 19) {
                $field = $this->extractConstraintField($e->getMessage());
                $msg = $field
                    ? "O valor informado para '{$field}' já está em uso."
                    : 'Registro duplicado. Verifique os dados e tente novamente.';

                return response()->json([
                    'error' => [
                        'code'     => 'DUPLICATE_ENTRY',
                        'message'  => $msg,
                        'trace_id' => $traceId,
                    ],
                ], 409);
            }

            // Foreign key constraint violation (MySQL 1451/1452, PG 23503)
            if ($sqlState === '23503' || $driverCode === 1451 || $driverCode === 1452) {
                return response()->json([
                    'error' => [
                        'code'     => 'FOREIGN_KEY_VIOLATION',
                        'message'  => 'Não é possível completar a operação pois existem registros relacionados.',
                        'trace_id' => $traceId,
                    ],
                ], 409);
            }

            return response()->json([
                'error' => [
                    'code'     => 'DATABASE_ERROR',
                    'message'  => $debug ? $e->getMessage() : 'Ocorreu um erro no banco de dados.',
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
                'message'  => $debug ? $e->getMessage() : 'Erro interno do servidor.',
                'trace_id' => $traceId,
            ],
        ], 500);
    }

    /**
     * Tenta extrair o nome do campo de uma mensagem de constraint violation.
     */
    private function extractConstraintField(string $message): ?string
    {
        // MySQL: "Duplicate entry '...' for key 'table.column_unique'"
        if (preg_match("/for key '(?:[^.]+\.)?([^']+)'/i", $message, $m)) {
            return str_replace(['_unique', '_UNIQUE'], '', $m[1]);
        }

        // PostgreSQL: "duplicate key value violates unique constraint ... Key (column)="
        if (preg_match('/Key \(([^)]+)\)/i', $message, $m)) {
            return $m[1];
        }

        // SQLite: "UNIQUE constraint failed: table.column"
        if (preg_match('/UNIQUE constraint failed: \w+\.(\w+)/i', $message, $m)) {
            return $m[1];
        }

        return null;
    }
}