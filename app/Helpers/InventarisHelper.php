<?php

if (!function_exists('simrs_webapps_url')) {
    /**
     * Build URL ke webapps SIMRS (bisa beda server).
     *
     * .env yang direkomendasikan:
     * - SIMRS_WEBAPPS_BASE_URL=http://127.0.0.1/webapps
     *
     * @param string|null $path Path relatif di bawah webapps (contoh: inventaris/xxx.jpg, pages/berkaspegawai/berkas/xxx.pdf)
     * @return string|null
     */
    function simrs_webapps_url($path)
    {
        if (empty($path)) {
            return null;
        }

        $path = ltrim((string) $path, '/');

        // Jika sudah full URL, langsung return
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        // Base URL webapps (paling direkomendasikan)
        $base = (string) env('SIMRS_WEBAPPS_BASE_URL', '');
        if (trim($base) === '') {
            // Fallback legacy (kalau user belum migrasi)
            $base = (string) env('INVENTARIS_IMAGE_BASE_URL', '');
        }

        if (trim($base) === '') {
            return null;
        }

        return rtrim($base, '/') . '/' . $path;
    }
}

if (!function_exists('route_key_encode')) {
    /**
     * Encode identifier agar aman dipakai di URL segment (menghindari '/' di kode).
     * Format: k_<base64url>
     */
    function route_key_encode($raw)
    {
        if ($raw === null) return null;
        $raw = (string) $raw;
        $b64 = base64_encode($raw);
        $b64url = rtrim(strtr($b64, '+/', '-_'), '=');
        return 'k_' . $b64url;
    }
}

if (!function_exists('route_key_decode')) {
    /**
     * Decode identifier dari route_key_encode(). Jika bukan format k_, return apa adanya.
     */
    function route_key_decode($encoded)
    {
        if ($encoded === null) return null;
        $encoded = (string) $encoded;
        if (strpos($encoded, 'k_') !== 0) {
            return $encoded;
        }
        $b64url = substr($encoded, 2);
        $b64 = strtr($b64url, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        $decoded = base64_decode($b64, true);
        return $decoded === false ? $encoded : $decoded;
    }
}

if (!function_exists('simrs_asset')) {
    /**
     * Helper untuk link file yang tersimpan di webapps SIMRS.
     * Jika SIMRS_WEBAPPS_BASE_URL belum diset, fallback ke asset() lokal.
     */
    function simrs_asset($path)
    {
        return simrs_webapps_url($path) ?? asset($path);
    }
}

if (!function_exists('getSimrsImageBase64')) {
    /**
     * Ambil gambar dari webapps SIMRS lalu return base64 data URL.
     *
     * - Mendukung path relatif (di bawah webapps) atau full URL.
     * - Jika $path tidak mengandung '/', maka akan diprefix dengan $defaultBasePath (bila diisi).
     */
    function getSimrsImageBase64($path, $defaultBasePath = null)
    {
        if (empty($path)) {
            return null;
        }

        $path = ltrim((string) $path, '/');

        // Sudah full URL
        if (preg_match('#^https?://#i', $path)) {
            $fullUrl = $path;
        } else {
            // Jika hanya nama file (tanpa folder), prefix-kan default base path
            if (!empty($defaultBasePath) && strpos($path, '/') === false) {
                $base = trim((string) $defaultBasePath, '/');
                if ($base !== '') {
                    $path = $base . '/' . $path;
                }
            }

            $fullUrl = simrs_webapps_url($path);
            if (!$fullUrl) {
                return null;
            }
        }

        try {
            $imageContent = @file_get_contents($fullUrl);
            if ($imageContent === false || $imageContent === '') {
                return null;
            }

            $imageInfo = @getimagesizefromstring($imageContent);
            $mimeType = 'image/jpeg';
            if ($imageInfo && isset($imageInfo['mime'])) {
                $mimeType = $imageInfo['mime'];
            } else {
                $extension = strtolower(pathinfo(parse_url($fullUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                $mimeTypes = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                ];
                $mimeType = $mimeTypes[$extension] ?? 'image/jpeg';
            }

            return 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);
        } catch (\Exception $e) {
            \Log::error('Failed to load SIMRS image: ' . $e->getMessage() . ' | URL: ' . $fullUrl);
            return null;
        }
    }
}

if (!function_exists('getPegawaiPhotoBase64')) {
    /**
     * Foto pegawai SIMRS biasanya ada di:
     * webapps/penggajian/pages/pegawai/photo/<file>
     *
     * Bisa dioverride via .env:
     * - PEGAWAI_PHOTO_PATH=penggajian/pages/pegawai/photo
     */
    function getPegawaiPhotoBase64($photoPath)
    {
        if (empty($photoPath)) {
            return null;
        }

        $photoPath = ltrim((string) $photoPath, '/');
        $default = trim((string) env('PEGAWAI_PHOTO_PATH', 'penggajian/pages/pegawai/photo'), '/');

        // Jika DB menyimpan path tanpa prefix modul (contoh: pages/pegawai/photo/xxx.jpg),
        // tapi default path mengandung modul (contoh: penggajian/pages/pegawai/photo),
        // maka tambahkan prefix modul secara otomatis.
        if (
            $default !== ''
            && strpos($photoPath, '/') !== false
            && strpos($photoPath, $default . '/') !== 0
        ) {
            $defaultParts = explode('/', $default);
            $modulePrefix = $defaultParts[0] ?? '';

            // Heuristik: path dari SIMRS sering berbentuk pages/pegawai/photo/...
            if ($modulePrefix !== '' && strpos($photoPath, 'pages/pegawai/photo/') === 0) {
                $photoPath = $modulePrefix . '/' . $photoPath;
            }
        }

        return getSimrsImageBase64($photoPath, $default);
    }
}

if (!function_exists('getPegawaiPhotoUrl')) {
    /**
     * Resolve URL foto pegawai dari nilai kolom `pegawai.photo`.
     *
     * Mendukung:
     * - `cmm.jpg` (nama file saja) -> prefix PEGAWAI_PHOTO_PATH
     * - `pages/pegawai/photo/cmm.jpg` -> auto tambah prefix modul (mis. penggajian/)
     * - `penggajian/pages/pegawai/photo/cmm.jpg`
     * - full URL
     * - file lokal (jika tersimpan di public/...)
     */
    function getPegawaiPhotoUrl($photoPath)
    {
        if (empty($photoPath)) {
            return null;
        }

        $photoPath = ltrim((string) $photoPath, '/');

        // Sudah full URL
        if (preg_match('#^https?://#i', $photoPath)) {
            return $photoPath;
        }

        // Jika file ada di public Laravel, pakai asset lokal (untuk mode fallback upload lokal)
        try {
            if (file_exists(public_path($photoPath))) {
                return asset($photoPath);
            }
        } catch (\Exception $e) {
            // ignore
        }

        $default = trim((string) env('PEGAWAI_PHOTO_PATH', 'penggajian/pages/pegawai/photo'), '/');

        // Jika hanya nama file (tanpa folder), prefix default path
        if (strpos($photoPath, '/') === false) {
            // Normal case: ada default path dari env / fallback
            if ($default !== '') {
                $photoPath = $default . '/' . $photoPath;
            } else {
                // Heuristik: beberapa environment mengosongkan PEGAWAI_PHOTO_PATH.
                // Coba beberapa kandidat path yang umum dipakai di SIMRS.
                $candidates = [
                    'pages/pegawai/photo/' . $photoPath,
                    'penggajian/pages/pegawai/photo/' . $photoPath,
                ];

                foreach ($candidates as $candidate) {
                    try {
                        if (file_exists(public_path($candidate))) {
                            return asset($candidate);
                        }
                    } catch (\Exception $e) {
                        // ignore
                    }

                    $url = simrs_webapps_url($candidate);
                    if ($url) {
                        return $url;
                    }
                }
            }
        }

        // Jika path dari DB tanpa prefix modul: pages/pegawai/photo/...
        if ($default !== '' && strpos($photoPath, 'pages/pegawai/photo/') === 0) {
            $defaultParts = explode('/', $default);
            $modulePrefix = $defaultParts[0] ?? '';
            if ($modulePrefix !== '' && strpos($photoPath, $modulePrefix . '/') !== 0) {
                $photoPath = $modulePrefix . '/' . $photoPath;
            }
        }

        // Utamakan base URL webapps SIMRS, fallback ke asset() lokal
        return simrs_webapps_url($photoPath) ?? asset($photoPath);
    }
}

if (!function_exists('getInventarisPhotoUrl')) {
    /**
     * Resolve URL gambar inventaris dari nilai kolom `inventaris_gambar.photo`.
     *
     * Target URL di SIMRS biasanya:
     * {SIMRS_WEBAPPS_BASE_URL}/inventaris/pages/upload/<file>
     *
     * Mendukung:
     * - `pages/upload/xxx.jpg` (tanpa prefix modul) -> auto tambah `inventaris/`
     * - `inventaris/pages/upload/xxx.jpg`
     * - full URL
     * - file lokal (jika tersimpan di public/...)
     */
    function getInventarisPhotoUrl($photoPath)
    {
        if (empty($photoPath)) {
            return null;
        }

        $photoPath = ltrim((string) $photoPath, '/');

        // Sudah full URL
        if (preg_match('#^https?://#i', $photoPath)) {
            return $photoPath;
        }

        // Jika file ada di public Laravel, pakai asset lokal
        try {
            if (file_exists(public_path($photoPath))) {
                return asset($photoPath);
            }
        } catch (\Exception $e) {
            // ignore
        }

        $module = trim((string) env('INVENTARIS_WEBAPPS_PREFIX', 'inventaris'), '/');

        // Normalisasi path SIMRS yang umum: pages/upload/...
        if ($module !== '' && strpos($photoPath, 'pages/upload/') === 0) {
            $photoPath = $module . '/' . $photoPath; // inventaris/pages/upload/...
        }

        return simrs_webapps_url($photoPath) ?? asset($photoPath);
    }
}

if (!function_exists('getInventarisImageBase64')) {
    /**
     * Get inventaris image as base64 encoded string
     * Similar to radiologi implementation for security
     * 
     * @param string $photoPath Path to photo (relative path from inventaris storage)
     * @return string|null Base64 encoded image data URL or null if failed
     */
    function getInventarisImageBase64($photoPath)
    {
        // Pakai URL inventaris yang sudah dinormalisasi, lalu convert ke base64
        $url = getInventarisPhotoUrl($photoPath);
        if (!$url) {
            return null;
        }
        return getSimrsImageBase64($url);
    }
}
