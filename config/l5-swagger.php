<?php

return [
    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => ['title' => 'BetAki Admin API'],
            'routes' => ['api' => 'api/documentation'],
            'paths' => [
                'use_absolute_path' => true,
                'docs' => public_path('api-docs'),
                'docs_json' => 'api-docs.json',
                'annotations' => [
                    base_path('app/OpenApi'),
                    base_path('app/Http/Controllers'),
                    base_path('app/Http/Requests'),
                ],
            ],
        ],
    ],

    'paths' => [
        'base' => env('L5_SWAGGER_BASE_PATH', null),
        'docs' => public_path('api-docs'),
        'docs_json' => 'api-docs.json',
        'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
        'assets' => public_path('vendor/l5-swagger'),
        'assets_public' => '/vendor/l5-swagger',
        'excludes' => [],
    ],

    'routes' => [
        'api' => 'api/documentation',
        'middleware' => ['api' => []],
        'controller' => \L5Swagger\Http\Controllers\SwaggerController::class,
    ],

    'generate_always' => filter_var(env('L5_SWAGGER_GENERATE_ALWAYS', true), FILTER_VALIDATE_BOOLEAN),

    'constants' => [
        'L5_SWAGGER_CONST_HOST' => env('APP_URL', 'http://localhost:8080'),
    ],

    'ui' => [
        'display_request_duration' => true,
        'filter' => true,
        'persist_authorization' => true,
    ],
];