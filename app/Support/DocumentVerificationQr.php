<?php

namespace App\Support;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * QR verifikasi keabsahan dokumen (Surat Edaran, SPO, dll.) — URL sama dengan yang tertanam di PDF.
 */
final class DocumentVerificationQr
{
    public static function pngBinary(string $verifyUrl): string
    {
        return (string) QrCode::format('png')->size(220)->margin(1)->generate($verifyUrl);
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
}
