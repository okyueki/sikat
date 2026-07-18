<?php

namespace App\Support;

use RuntimeException;

/**
 * Render nomor surat ke PNG (Times New Roman) untuk embed PDF.
 * Ukuran font mengikuti tinggi kotak penempatan (mm) ≈ pt.
 */
final class DocumentNumberTextImage
{
    public const DEFAULT_FONT_SIZE_PT = 11.0;

    private const MIN_FONT_SIZE_PT = 6.0;

    private const MAX_FONT_SIZE_PT = 24.0;

    private const RENDER_DPI = 300;

    public static function fontSizePtFromHeightMm(float $heightMm): float
    {
        if ($heightMm <= 0) {
            return self::DEFAULT_FONT_SIZE_PT;
        }

        $pt = $heightMm * 72.0 / 25.4;

        return max(self::MIN_FONT_SIZE_PT, min(self::MAX_FONT_SIZE_PT, $pt));
    }

    public static function defaultHeightMm(): float
    {
        return self::DEFAULT_FONT_SIZE_PT * 25.4 / 72.0;
    }

    public static function pngBinary(string $text, string $colorHex = '#000000', ?float $fontSizePt = null): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (! extension_loaded('gd') || ! function_exists('imagettftext')) {
            throw new RuntimeException('GD dengan FreeType diperlukan untuk render penomoran.');
        }

        $fontPath = self::resolveFontPath();
        $fontSizePt = $fontSizePt ?? self::DEFAULT_FONT_SIZE_PT;
        $fontSizePt = max(self::MIN_FONT_SIZE_PT, min(self::MAX_FONT_SIZE_PT, $fontSizePt));
        $fontPx = $fontSizePt * self::RENDER_DPI / 72.0;
        $color = self::parseColor($colorHex);

        $bbox = imagettfbbox($fontPx, 0, $fontPath, $text);
        if ($bbox === false) {
            throw new RuntimeException('Gagal mengukur teks penomoran.');
        }

        $textW = abs($bbox[2] - $bbox[0]);
        $textH = abs($bbox[7] - $bbox[1]);
        $pad = (int) max(4, round($fontPx * 0.08));
        $width = max(1, (int) ceil($textW) + $pad * 2);
        $height = max(1, (int) ceil($textH) + $pad * 2);

        $img = imagecreatetruecolor($width, $height);
        if ($img === false) {
            throw new RuntimeException('Gagal membuat kanvas penomoran.');
        }

        imagesavealpha($img, true);
        imagealphablending($img, false);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);
        imagealphablending($img, true);

        $textColor = imagecolorallocate($img, $color['r'], $color['g'], $color['b']);
        $x = (int) round(($width - $textW) / 2 - $bbox[0]);
        $y = (int) round(($height - $textH) / 2 - $bbox[7]);
        imagettftext($img, $fontPx, 0, $x, $y, $textColor, $fontPath, $text);

        ob_start();
        imagepng($img);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        return $png;
    }

    public static function resolveFontPath(): string
    {
        $configured = (string) config('services.document_signing.nomor_font_path', '');
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = [
            resource_path('fonts/Times-New-Roman.ttf'),
            resource_path('fonts/times.ttf'),
            '/usr/share/fonts/truetype/msttcorefonts/times.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Times_New_Roman.ttf',
            resource_path('fonts/Tinos-Regular.ttf'),
            '/usr/share/fonts/truetype/liberation/LiberationSerif-Regular.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException(
            'Font Times New Roman tidak ditemukan. Letakkan Times-New-Roman.ttf di resources/fonts/ atau set DOCUMENT_NUMBER_FONT_PATH.'
        );
    }

    /** @return array{r: int, g: int, b: int} */
    private static function parseColor(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return ['r' => 0, 'g' => 0, 'b' => 0];
        }

        return [
            'r' => (int) hexdec(substr($hex, 0, 2)),
            'g' => (int) hexdec(substr($hex, 2, 2)),
            'b' => (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
