<?php

namespace App\Support;

/**
 * Konversi koordinat UI (mm, origin kiri-atas) ke koordinat PDF/Stirling (points, origin kiri-bawah).
 */
final class PdfCoordinateConverter
{
    public const POINTS_PER_MM = 72 / 25.4;

    public static function mmToPoints(float $mm): float
    {
        return $mm * self::POINTS_PER_MM;
    }

    public static function pointsToMm(float $points): float
    {
        return $points / self::POINTS_PER_MM;
    }

    /**
     * @return array{x: float, y: float}
     */
    public static function topLeftMmToBottomLeftPoints(
        float $xMm,
        float $yMm,
        float $boxHeightMm,
        float $pageHeightPoints
    ): array {
        $heightPt = self::mmToPoints($boxHeightMm);

        return [
            'x' => self::mmToPoints($xMm),
            'y' => $pageHeightPoints - self::mmToPoints($yMm) - $heightPt,
        ];
    }
}
