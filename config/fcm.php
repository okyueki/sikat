<?php

return [

    /*
    |--------------------------------------------------------------------------
    | FCM (Firebase Cloud Messaging) — HTTP v1 API
    |--------------------------------------------------------------------------
    |
    | Pakai Service Account JSON dari Firebase Console:
    | Project Settings → Service accounts → Generate new private key.
    | Simpan file JSON di storage/app/ (jangan commit ke git), lalu isi path-nya di .env.
    |
    */

    /*
    | Path ke file Service Account JSON (absolut atau relatif ke base_path).
    | Contoh: storage/app/sikatmobile-36deb-7cce3d43a59f.json
    */
    'credentials_path' => env('FCM_CREDENTIALS_PATH', storage_path('app/sikatmobile-36deb-7cce3d43a59f.json')),

    /*
    | FCM HTTP v1 endpoint. Project ID dibaca dari file JSON.
    */
    'v1_endpoint' => 'https://fcm.googleapis.com/v1/projects/%s/messages:send',

];
