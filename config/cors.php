<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | allowed_origins = * : semua origin bisa akses API (aman karena auth pakai
    |   Bearer token, bukan cookie; endpoint sensitif tetap butuh token).
    | allowed_origins = whitelist : hanya origin yang tercantum (set CORS_ALLOW_ANY=false
    |   dan isi CORS_ALLOWED_ORIGINS).
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('CORS_ALLOW_ANY', true)
        ? ['*']
        : (env('CORS_ALLOWED_ORIGINS')
            ? array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS')))
            : [
                'https://srikandi.rsaisyiyahsitifatimah.com',
                'https://sikat.rsaisyiyahsitifatimah.com',
                'http://192.168.15.250:6300',
                'http://localhost:6300',
                'http://127.0.0.1:6300',
            ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Harus false jika allowed_origins = * (aturan browser). API pakai Bearer token, bukan cookie.
    'supports_credentials' => env('CORS_ALLOW_ANY', true) ? false : true,

];
