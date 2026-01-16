<?php

if (!function_exists('formatRupiah')) {
    /**
     * Format angka menjadi format Rupiah Indonesia
     * 
     * @param float|int|string $angka Angka yang akan diformat
     * @param bool $withDecimals Apakah menampilkan desimal (default: false)
     * @return string Format Rupiah
     */
    function formatRupiah($angka, $withDecimals = false)
    {
        if (empty($angka) && $angka !== 0 && $angka !== '0') {
            return 'Rp 0';
        }
        
        // Convert to float
        $angka = (float) $angka;
        
        if ($withDecimals) {
            return 'Rp ' . number_format($angka, 2, ',', '.');
        } else {
            return 'Rp ' . number_format($angka, 0, ',', '.');
        }
    }
}
