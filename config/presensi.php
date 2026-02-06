<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Titik Presensi & Radius
    |--------------------------------------------------------------------------
    | Koordinat titik presensi (lokasi yang dianggap valid untuk absen) dan
    | submit presensi. Ubah di .env atau di sini.
    | Setelah ubah .env, wajib jalankan: php artisan config:clear
    */

    'target_latitude' => (float) env('PRESENSI_TARGET_LAT', -7.485628943494862),
    'target_longitude' => (float) env('PRESENSI_TARGET_LNG', 112.6527141877153),
    'allowed_radius_meter' => (int) env('PRESENSI_ALLOWED_RADIUS_METER', 30),

];
