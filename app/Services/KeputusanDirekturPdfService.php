<?php

namespace App\Services;

use App\Models\KeputusanDirektur;
use App\Services\Signing\DocumentNumberService;
use App\Services\Signing\SignableDocumentConfig;
use App\Services\Signing\StirlingSigningPdfService;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;

class KeputusanDirekturPdfService
{
    private static function config(): SignableDocumentConfig
    {
        return new SignableDocumentConfig(
            storageFolder: 'keputusan_direktur',
            verifyRouteName: 'keputusan_direktur.verify',
            auditModule: 'keputusan_direktur',
            tempPrefix: 'keputusan_direktur_',
            numberPrefix: "RS'ASF/KEP",
            documentLabel: 'Keputusan Direktur',
            routePrefix: 'keputusan_direktur',
            hasMasaBerlaku: true,
        );
    }

    public static function sourcePdfRelativePath(int $id): string
    {
        return self::config()->sourcePdfRelativePath($id);
    }

    public static function generateSignedPdfContent(KeputusanDirektur $doc, bool $embedVerificationAssets = false): string
    {
        return app(StirlingSigningPdfService::class)->buildSignedPdfContent($doc, self::config(), $embedVerificationAssets);
    }

    public static function normalizeQrDimensionsMm(float $widthMm, float $heightMm): array
    {
        return StirlingSigningPdfService::normalizeQrDimensionsMm($widthMm, $heightMm);
    }

    public static function generateSignedPdf(KeputusanDirektur $doc): Response
    {
        return app(StirlingSigningPdfService::class)->generateSignedPdfResponse($doc, self::config());
    }

    public static function assertPdfParsableFromStoragePath(string $relativePath): void
    {
        app(StirlingSigningPdfService::class)->assertProcessableFromStoragePath($relativePath);
    }

    public static function signableConfig(): SignableDocumentConfig
    {
        return self::config();
    }
}
