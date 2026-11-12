<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Ingreso único desde el Portal Corporativo (ver App\Services\PortalSsoService).
    'portal' => [
        'url' => env('PORTAL_URL'),
        'audiencia' => env('PORTAL_SSO_AUDIENCIA', 'Turnero'),
        'clave_publica' => env('PORTAL_SSO_CLAVE_PUBLICA', storage_path('app/sso/publica.pem')),
        // Segundos de diferencia de reloj que se toleran entre el portal y este servidor.
        'margen_reloj' => (int) env('PORTAL_SSO_MARGEN_RELOJ', 60),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
