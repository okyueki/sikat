<?php

namespace App\Support;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * QR verifikasi keabsahan dokumen (Surat Edaran, SPO, dll.) — URL sama dengan yang tertanam di PDF.
 */
final class DocumentVerificationQr
{
    private const QR_PIXEL_SIZE = 512;

    private const LOGO_RATIO = 0.14;

    public static function logoPath(): string
    {
        return public_path('assets/images/web.png');
    }

    public static function embedLogoEnabled(): bool
    {
        return filter_var(env('DOCUMENT_QR_EMBED_LOGO', false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function pngBinary(string $verifyUrl): string
    {
        $ecLevel = strlen($verifyUrl) > 55 ? 'Q' : 'M';

        $png = (string) QrCode::format('png')
            ->size(self::QR_PIXEL_SIZE)
            ->margin(4)
            ->errorCorrection($ecLevel)
            ->color(0, 0, 0)
            ->backgroundColor(255, 255, 255)
            ->generate($verifyUrl);

        if (! self::embedLogoEnabled()) {
            return $png;
        }

        $logoPath = self::logoPath();
        if (! is_file($logoPath) || ! extension_loaded('gd')) {
            return $png;
        }

        return self::embedCenterLogo($png, $logoPath, self::LOGO_RATIO);
    }

    public static function svgMarkup(string $verifyUrl): string
    {
        $ecLevel = strlen($verifyUrl) > 55 ? 'Q' : 'M';

        return (string) QrCode::format('svg')
            ->size(self::QR_PIXEL_SIZE)
            ->margin(4)
            ->errorCorrection($ecLevel)
            ->color(0, 0, 0)
            ->backgroundColor(255, 255, 255)
            ->generate($verifyUrl);
    }

    public static function dataUri(string $verifyUrl): ?string
    {
        try {
            $png = self::pngBinary($verifyUrl);
            if ($png === '') {
                return null;
            }

            return 'data:image/png;base64,' . base64_encode($png);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Logo di tengah QR dengan latar putih agar tetap terbaca scanner.
     */
    private static function embedCenterLogo(string $qrPng, string $logoPath, float $ratio): string
    {
        $qr = @imagecreatefromstring($qrPng);
        if ($qr === false) {
            return $qrPng;
        }

        $qrW = imagesx($qr);
        $qrH = imagesy($qr);
        $box = max(1, (int) round(min($qrW, $qrH) * $ratio));
        $cx = (int) floor(($qrW - $box) / 2);
        $cy = (int) floor(($qrH - $box) / 2);
        $centerX = (int) ($qrW / 2);
        $centerY = (int) ($qrH / 2);

        $white = imagecolorallocate($qr, 255, 255, 255);
        imagefilledellipse($qr, $centerX, $centerY, (int) ($box * 1.06), (int) ($box * 1.06), $white);

        $logo = @imagecreatefrompng($logoPath);
        if ($logo === false) {
            ob_start();
            imagepng($qr);
            $out = ob_get_clean() ?: $qrPng;
            imagedestroy($qr);

            return $out;
        }

        $logoCanvas = imagecreatetruecolor($box, $box);
        $bg = imagecolorallocate($logoCanvas, 255, 255, 255);
        imagefill($logoCanvas, 0, 0, $bg);

        $srcW = imagesx($logo);
        $srcH = imagesy($logo);
        $scale = min($box / $srcW, $box / $srcH);
        $drawW = max(1, (int) round($srcW * $scale));
        $drawH = max(1, (int) round($srcH * $scale));
        $dstX = (int) floor(($box - $drawW) / 2);
        $dstY = (int) floor(($box - $drawH) / 2);
        imagecopyresampled($logoCanvas, $logo, $dstX, $dstY, 0, 0, $drawW, $drawH, $srcW, $srcH);
        imagecopy($qr, $logoCanvas, $cx, $cy, 0, 0, $box, $box);

        imagedestroy($logo);
        imagedestroy($logoCanvas);

        ob_start();
        imagepng($qr);
        $out = ob_get_clean() ?: $qrPng;
        imagedestroy($qr);

        return $out;
    }
}
