<?php

return [
    'enabled' => env('INSIGHTFACE_ENABLED', true),
    'base_url' => rtrim(env('INSIGHTFACE_BASE_URL', 'http://192.168.10.44:6700'), '/'),
    'timeout' => (int) env('INSIGHTFACE_TIMEOUT', 5),
    'min_score' => (float) env('INSIGHTFACE_MIN_SCORE', 70),
    'verify_mode' => env('FACE_VERIFY_MODE', 'soft'),
    'soft_mismatch_audit_notice' => env(
        'FACE_SOFT_MISMATCH_AUDIT_NOTICE',
        'Wajah tidak cocok dengan data profil Anda. Apakah Anda yakin ingin melanjutkan presensi? Seluruh aktivitas presensi dapat diaudit oleh SDI.'
    ),
];
