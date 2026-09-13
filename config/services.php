<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mcp' => [
        // A dónde llaman ChatController::send() y el debug route de
        // routes/web.php para hablar con /mcp/banorte -- por defecto,
        // app.url (el mismo proceso que ya sirve la request web). En local
        // con `php artisan serve` en Windows eso es un auto-deadlock (ver
        // CLAUDE.md): la request externa nunca libera el proceso -- único,
        // sin fork() -- para atender la interna. Overridear con
        // MCP_LOOPBACK_URL apuntando a un segundo `php artisan serve` corre
        // el mismo código en OTRO proceso, así uno no bloquea al otro.
        'loopback_url' => env('MCP_LOOPBACK_URL', env('APP_URL', 'http://localhost')),
    ],

    'twelvedata' => [
        'key' => env('TWELVE_DATA_API_KEY'),
        'base_url' => env('TWELVE_DATA_BASE_URL', 'https://api.twelvedata.com'),

        // El plan gratuito de TwelveData corta en 8 req/min para TODA la cuenta.
        // Este límite es por usuario, así que no puede garantizar el techo de
        // arriba por sí solo -- lo que evita es que un solo cliente lo agote.
        'rate_limit_per_minute' => env('TWELVE_DATA_RATE_LIMIT_PER_MINUTE', 8),
    ],

];
