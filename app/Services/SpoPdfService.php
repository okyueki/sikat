<?php

namespace App\Services;

use App\Models\Spo;
use App\Services\Signing\SignableDocumentConfig;
use App\Services\Signing\StirlingSigningPdfService;
use Illuminate\Http\Response;

class SpoPdfService
{
    private static function config(): SignableDocumentConfig
    {
        return new SignableDocumentConfig(
            storageFolder: 'spo',
            verifyRouteName: 'spo.verify',
            auditModule: 'spo',
            tempPrefix: 'spo_',
            numberPrefix: "RS'ASF/SPO",
            documentLabel: 'SPO',
            routePrefix: 'spo',
        );
    }

    public static function sourcePdfRelativePath(int $id): string
    {
        return self::config()->sourcePdfRelativePath($id);
    }

    public static function generateSignedPdfContent(Spo $doc, bool $embedVerificationAssets = false): string
    {
        return app(StirlingSigningPdfService::class)->buildSignedPdfContent($doc, self::config(), $embedVerificationAssets);
    }

    public static function normalizeQrDimensionsMm(float $widthMm, float $heightMm): array
    {
        return StirlingSigningPdfService::normalizeQrDimensionsMm($widthMm, $heightMm);
    }

    public static function generateSignedPdf(Spo $doc): Response
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
