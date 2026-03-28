<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Titik Masjid & Radius (Absensi Sholat)
    |--------------------------------------------------------------------------
    | Koordinat lokasi masjid RS dan radius (meter) untuk validasi absensi
    | sholat. Pakai env MASJID_* agar tidak bentrok dengan presensi kerja
    | (PRESENSI_TARGET_LAT, PRESENSI_LNG, PRESENSI_ALLOWED_RADIUS_METER).
    | Setelah ubah .env, jalankan: php artisan config:clear
    */

    'target_latitude' => (float) env('MASJID_TARGET_LAT', -7.485628943494862),
    'target_longitude' => (float) env('MASJID_TARGET_LNG', 112.6527141877153),
    'allowed_radius_meter' => (int) env('MASJID_ALLOWED_RADIUS_METER', 50),

];
