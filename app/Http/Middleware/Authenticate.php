<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Não redirecione para 'login' em contexto de API.
     * Retorne null para que a resposta seja 401 JSON.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}