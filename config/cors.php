<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Caminhos que aceitam CORS
    |--------------------------------------------------------------------------
    | Aqui definimos os endpoints onde as requisições de outros domínios
    | podem ser aceitas. Para API, geralmente limitamos a "api/*".
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
    |--------------------------------------------------------------------------
    | Métodos permitidos
    |--------------------------------------------------------------------------
    */
    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Origens permitidas
    |--------------------------------------------------------------------------
    | Em desenvolvimento, o Angular roda em localhost:4200.
    | Você pode adicionar outras origens (staging, prod) depois.
    */
    'allowed_origins' => [
        'http://localhost:4200',
        'http://localhost:8080',
    ],

    /*
    |--------------------------------------------------------------------------
    | Headers permitidos
    |--------------------------------------------------------------------------
    */
    'allowed_headers' => [
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'Accept',
        'Origin',
        'Idempotency-Key',
        'If-None-Match',
        'If-Match',
    ],

    /*
    |--------------------------------------------------------------------------
    | Headers expostos (visíveis no front)
    |--------------------------------------------------------------------------
    */
    'exposed_headers' => [
        'ETag',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'Retry-After',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tempo máximo de cache do preflight (em segundos)
    |--------------------------------------------------------------------------
    */
    'max_age' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Suporte a credenciais (cookies, authorization)
    |--------------------------------------------------------------------------
    | Para APIs com token Bearer (sem cookies), mantenha false.
    | Se um dia for usar requests stateful (ex: SPA com Sanctum), use true.
    */
    'supports_credentials' => false,

];