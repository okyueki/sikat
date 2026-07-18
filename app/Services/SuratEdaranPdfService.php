<?php

namespace App\Services;

use App\Models\SuratEdaran;
use App\Services\Signing\DocumentNumberService;
use App\Services\Signing\SignableDocumentConfig;
use App\Services\Signing\StirlingSigningPdfService;
use Illuminate\Http\Response;

/**
 * Thin wrapper — logika di StirlingSigningPdfService.
 */
class SuratEdaranPdfService
{
    private static function config(): SignableDocumentConfig
    {
        return new SignableDocumentConfig(
            storageFolder: 'surat_edaran',
            verifyRouteName: 'surat_edaran.verify',
            auditModule: 'surat_edaran',
            tempPrefix: 'surat_edaran_',
            numberPrefix: "RS'ASF/EDR",
            documentLabel: 'Surat Edaran',
            routePrefix: 'surat_edaran',
        );
    }

    public static function sourcePdfRelativePath(int $suratEdaranId): string
    {
        return self::config()->sourcePdfRelativePath($suratEdaranId);
    }

    public static function generateSignedPdfContent(SuratEdaran $surat, bool $embedVerificationAssets = false): string
    {
        return app(StirlingSigningPdfService::class)->buildSignedPdfContent(
            $surat,
            self::config(),
            $embedVerificationAssets
        );
    }

    /**
     * @return array{0: float, 1: float}
     */
    public static function normalizeQrDimensionsMm(float $widthMm, float $heightMm): array
    {
        return StirlingSigningPdfService::normalizeQrDimensionsMm($widthMm, $heightMm);
    }

    public static function generateSignedPdf(SuratEdaran $surat): Response
    {
        return app(StirlingSigningPdfService::class)->generateSignedPdfResponse($surat, self::config());
    }

    public static function assertPdfParsableFromStoragePath(string $relativePath): void
    {
        app(StirlingSigningPdfService::class)->assertProcessableFromStoragePath($relativePath);
    }
}
