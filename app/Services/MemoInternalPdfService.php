<?php

namespace App\Services;

use App\Models\MemoInternal;
use App\Services\Signing\SignableDocumentConfig;
use App\Services\Signing\StirlingSigningPdfService;
use Illuminate\Http\Response;

final class MemoInternalPdfService
{
    private static function config(): SignableDocumentConfig
    {
        return new SignableDocumentConfig(
            storageFolder: 'memo_internal',
            verifyRouteName: 'memo_internal.verify',
            auditModule: 'memo_internal',
            tempPrefix: 'memo_internal_',
            numberPrefix: "RS'ASF",
            documentLabel: 'Memo Internal',
            routePrefix: 'memo_internal',
        );
    }

    public static function sourcePdfRelativePath(int $id): string
    {
        return self::config()->sourcePdfRelativePath($id);
    }

    public static function generateSignedPdfContent(MemoInternal $doc, bool $embedVerificationAssets = false): string
    {
        return app(StirlingSigningPdfService::class)->buildSignedPdfContent($doc, self::config(), $embedVerificationAssets);
    }

    public static function normalizeQrDimensionsMm(float $widthMm, float $heightMm): array
    {
        return StirlingSigningPdfService::normalizeQrDimensionsMm($widthMm, $heightMm);
    }

    public static function generateSignedPdf(MemoInternal $doc): Response
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
