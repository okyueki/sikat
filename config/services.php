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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'default_model' => env('OPENROUTER_DEFAULT_MODEL', 'google/gemma-3n-e2b-it:free'),
        'save_interactions' => env('OPENROUTER_SAVE_INTERACTIONS', false),
    ],
    // custom untuk bot telegram
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    ],

    'qontak' => [
        'client_id' => env('QONTAK_CLIENT_ID'),
        'client_secret' => env('QONTAK_CLIENT_SECRET'),
        'channel_id' => env('QONTAK_CHANNEL_ID'),
    ],

    'portalsifast' => [
        'url' => env('PORTALSIFAST_URL', 'https://portalsifast.rsaisyiyahsitifatimah.com'),
        'sso_secret' => env('PORTALSIFAST_SIKAT_SSO_SECRET'),
    ],

    'stirling_pdf' => [
        'url' => rtrim(env('STIRLING_PDF_URL', ''), '/'),
        'api_key' => env('STIRLING_PDF_API_KEY'),
        'timeout' => (int) env('STIRLING_PDF_TIMEOUT', 120),
        'embed_enabled' => filter_var(env('STIRLING_PDF_EMBED_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'document_signing' => [
        'nomor_font_path' => env('DOCUMENT_NUMBER_FONT_PATH'),
    ],

];
