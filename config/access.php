<?php

/**
 * Peta hak akses: ability (string) → daftar nilai users.level yang diizinkan.
 *
 * Konvensi penamaan: modul.aksi (contoh: surat.master, inventaris.access).
 * Middleware route: ->middleware(['auth', 'checkAccess:nama_ability'])
 * Blade / policy: @can('nama_ability') atau auth()->user()->can('nama_ability')
 *
 * Ability yang tidak ada di map ini ditolak oleh CheckAccess (403).
 *
 * Dokumentasi cepat dari CLI:
 *   php artisan access:matrix
 *   php artisan access:matrix --markdown
 *   php artisan access:matrix --markdown --output=docs/access-matrix.md
 *
 * Ke depan: jika izin perlu diubah tanpa deploy, pertimbangkan paket
 * spatie/laravel-permission (role/permission di DB) dan sinkronkan ke Gate.
 */
return [
    'map' => [
        'users.manage' => ['Direktur', 'Programmer', 'Kabag'],

        'rekap.view' => ['Direktur', 'Programmer', 'HRD', 'Kabag', 'Kasie'],

        'berkas_pegawai.manage' => ['Direktur', 'Programmer', 'HRD', 'Kabag', 'Kasie'],

        /** Sifat surat & klasifikasi (mengganti checkLevel:Kabag) */
        'surat.master' => ['Kabag'],

        /** Halaman backend daftar agenda (/backend-acara) — level dan/atau whitelist NIK */
        'agenda.backend' => [
            'levels' => ['Kabag', 'Kasie', 'Pelaksana', 'Koordinator', 'Programmer'],
            'niks' => ['434.10.03.2025'],
        ],

        /** Absensi agenda web: semua level akun (setara daftar level di inventaris), plus whitelist NIK. */
        'absensi_agenda.access' => [
            'levels' => [
                'Direktur', 'Programmer', 'HRD', 'Kabag', 'Kabid',
                'Kasie', 'Koordinator', 'Pelaksana', 'Komite',
            ],
            'niks' => [],
        ],

        /** Modul presensi sementara / verifikasi jam */
        'presensi.temporary' => ['Kabag', 'Kasie', 'Pelaksana', 'Koordinator'],

        /** Token QR absensi sholat (halaman /masjid-token) — level dan/atau whitelist NIK */
        'masjid_token.access' => [
            'levels' => ['Programmer'],
            'niks' => ['278.21.11.2018', '139.01.04.2015'],
        ],

        /**
         * Seluruh route inventaris & master referensi (setara semua level akun di form user).
         */
        'inventaris.access' => [
            'Direktur', 'Programmer', 'HRD', 'Kabag', 'Kabid',
            'Kasie', 'Koordinator', 'Pelaksana', 'Komite',
        ],
    ],
];
