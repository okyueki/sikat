<?php

namespace App\Services\Signing;

use App\Contracts\SignableDocument;
use App\Models\MasterStempel;
use App\Services\StirlingPdfClient;
use App\Support\DocumentNumberTextImage;
use App\Support\DocumentVerificationQr;
use App\Support\DocumentVerificationUrl;
use App\Support\PdfCoordinateConverter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class StirlingSigningPdfService
{
    private const QR_MIN_MM = 30.0;

    private const QR_DEFAULT_MM = 35.0;

    private const QR_MAX_MM = 42.0;

    public function __construct(
        private readonly StirlingPdfClient $stirling
    ) {
    }

    public function buildSignedPdfContent(
        SignableDocument $document,
        SignableDocumentConfig $config,
        bool $embedVerificationAssets = false
    ): string {
        if (! $document->getFilePdf()) {
            throw new RuntimeException('File PDF belum diisi pada dokumen ID: ' . $document->getKey());
        }

        $path = $this->resolveSourcePdfPath($document, $config);
        if (! file_exists($path)) {
            throw new RuntimeException('File PDF tidak ditemukan: ' . $document->getFilePdf());
        }

        $content = $this->buildWithStirling($document, $config, $path, $embedVerificationAssets);

        Log::debug('Dokumen ditandatangani via Stirling PDF', [
            'document_id' => $document->getKey(),
            'module' => $config->auditModule,
        ]);

        return $content;
    }

    public function generateSignedPdfResponse(
        SignableDocument $document,
        SignableDocumentConfig $config
    ): Response {
        $embedQr = (bool) $document->getTanggalDitandatangani();
        $pdfContent = $this->buildSignedPdfContent($document, $config, $embedQr);
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $document->getJudulForFilename());
        $filename = $config->storageFolder . '_' . $slug . '_signed.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function assertProcessableFromStoragePath(string $relativePath): void
    {
        if ($relativePath === '') {
            throw new RuntimeException('Path file PDF kosong.');
        }

        $path = Storage::disk('public')->path($relativePath);
        if (! file_exists($path)) {
            throw new RuntimeException('File PDF tidak ditemukan: ' . $relativePath);
        }

        if (! $this->stirling->isConfigured()) {
            throw new RuntimeException(
                'Layanan Stirling PDF belum dikonfigurasi. Set STIRLING_PDF_URL dan STIRLING_PDF_API_KEY di .env.'
            );
        }

        $this->stirling->assertProcessable($path);
    }

    /**
     * @return array{0: float, 1: float}
     */
    public static function normalizeQrDimensionsMm(float $widthMm, float $heightMm): array
    {
        $base = max($widthMm, $heightMm);
        if ($base < 1) {
            $base = self::QR_DEFAULT_MM;
        }
        $size = max(self::QR_MIN_MM, min(self::QR_MAX_MM, $base));

        return [$size, $size];
    }

    private function resolveSourcePdfPath(SignableDocument $document, SignableDocumentConfig $config): string
    {
        $sourceRelative = $config->sourcePdfRelativePath((int) $document->getKey());
        $sourcePath = Storage::disk('public')->path($sourceRelative);
        if (file_exists($sourcePath)) {
            return $sourcePath;
        }

        return Storage::disk('public')->path((string) $document->getFilePdf());
    }

    private function buildWithStirling(
        SignableDocument $document,
        SignableDocumentConfig $config,
        string $path,
        bool $embedVerificationAssets
    ): string {
        if (! $this->stirling->isConfigured()) {
            throw new RuntimeException('Layanan Stirling PDF belum dikonfigurasi.');
        }

        $shouldEmbedQr = $embedVerificationAssets || $document->getTanggalDitandatangani();
        $verifyUrl = DocumentVerificationUrl::shortFor($config->routePrefix, (int) $document->getKey());
        $pageDimensions = $this->stirling->getPageDimensions($path);
        $workingPdf = $this->copyToTempPdf($path, $config->tempPrefix . 'base_');
        $tempImages = [];

        try {
            $signatureDetail = $document->getSignatureDetail() ?? [];
            $namaLengkap = $signatureDetail['nama_lengkap'] ?? $document->penandatangan?->nama ?? '';
            $inisial = $signatureDetail['inisial'] ?? '';
            $colorHex = $signatureDetail['color'] ?? '#000000';
            $signatureType = $signatureDetail['type'] ?? 'text';
            $signatureImagePath = $signatureDetail['image_path'] ?? null;

            $masterStempel = MasterStempel::getPerusahaan();
            $stempelPath = $masterStempel && $masterStempel->file_path
                ? Storage::disk('public')->path($masterStempel->file_path)
                : null;

            $signatureImgFullPath = null;
            if ($signatureType === 'image' && $signatureImagePath) {
                $candidate = Storage::disk('public')->path($signatureImagePath);
                if (file_exists($candidate)) {
                    $signatureImgFullPath = $candidate;
                }
            }

            $placements = $document->placements()->orderBy('page')->orderBy('sort_order')->get();
            $hasQrPlacement = $placements->contains(fn ($p) => $p->field_type === 'qr_verifikasi');

            foreach ($placements as $placement) {
                $pageIndex = (int) $placement->page - 1;
                if ($pageIndex < 0 || $pageIndex >= count($pageDimensions)) {
                    continue;
                }

                $pageHeight = (float) $pageDimensions[$pageIndex]['height'];
                $xMm = (float) $placement->x;
                $yMm = (float) $placement->y;
                $wMm = $placement->width ? (float) $placement->width : 40.0;
                $hMm = $placement->height ? (float) $placement->height : 8.0;

                if ($placement->field_type === 'qr_verifikasi') {
                    if (! $shouldEmbedQr) {
                        continue;
                    }
                    [$wMm, $hMm] = self::normalizeQrDimensionsMm($wMm, $hMm);
                }

                $coords = PdfCoordinateConverter::topLeftMmToBottomLeftPoints($xMm, $yMm, $hMm, $pageHeight);

                if ($placement->field_type === 'qr_verifikasi') {
                    $qrPath = $this->writeTempPng(
                        DocumentVerificationQr::pngBinary($verifyUrl),
                        $config->tempPrefix . 'qr_'
                    );
                    $tempImages[] = $qrPath;
                    $prepared = $this->prepareOverlayImage($qrPath, $wMm, $hMm, true, $config->tempPrefix);
                    $tempImages[] = $prepared['path'];
                    $workingPdf = $this->replaceWorkingPdf(
                        $workingPdf,
                        $this->applyImageOverlay($workingPdf, $prepared['path'], (int) $placement->page, $coords, $prepared['height_pt'], $config->tempPrefix)
                    );
                    continue;
                }

                if ($placement->field_type === 'stempel' && $stempelPath && file_exists($stempelPath)) {
                    $prepared = $this->prepareOverlayImage($stempelPath, $wMm, $hMm, false, $config->tempPrefix);
                    $tempImages[] = $prepared['path'];
                    $workingPdf = $this->replaceWorkingPdf(
                        $workingPdf,
                        $this->applyImageOverlay($workingPdf, $prepared['path'], (int) $placement->page, $coords, $prepared['height_pt'], $config->tempPrefix)
                    );
                    continue;
                }

                if ($placement->field_type === 'signature' && $signatureType === 'image' && $signatureImgFullPath) {
                    $prepared = $this->prepareOverlayImage($signatureImgFullPath, $wMm, $hMm, false, $config->tempPrefix);
                    $tempImages[] = $prepared['path'];
                    $workingPdf = $this->replaceWorkingPdf(
                        $workingPdf,
                        $this->applyImageOverlay($workingPdf, $prepared['path'], (int) $placement->page, $coords, $prepared['height_pt'], $config->tempPrefix)
                    );
                    continue;
                }

                if ($placement->field_type === 'nomor_surat') {
                    $value = $this->resolvePlacementText($placement, $document, $namaLengkap, $inisial);
                    if ($value === '') {
                        continue;
                    }

                    $options = is_array($placement->options) ? $placement->options : [];
                    $col = (string) ($options['color'] ?? $colorHex);

                    try {
                        $fontSizePt = DocumentNumberTextImage::fontSizePtFromHeightMm($hMm);
                        $nomorPath = $this->writeTempPng(
                            DocumentNumberTextImage::pngBinary($value, $col, $fontSizePt),
                            $config->tempPrefix . 'nomor_'
                        );
                        $tempImages[] = $nomorPath;
                        $prepared = $this->prepareOverlayImage(
                            $nomorPath,
                            $wMm,
                            $hMm > 0 ? $hMm : DocumentNumberTextImage::defaultHeightMm(),
                            false,
                            $config->tempPrefix
                        );
                        $tempImages[] = $prepared['path'];
                        $workingPdf = $this->replaceWorkingPdf(
                            $workingPdf,
                            $this->applyImageOverlay(
                                $workingPdf,
                                $prepared['path'],
                                (int) $placement->page,
                                $coords,
                                $prepared['height_pt'],
                                $config->tempPrefix
                            )
                        );
                    } catch (\Throwable $e) {
                        report($e);
                    }

                    continue;
                }

                $value = $this->resolvePlacementText($placement, $document, $namaLengkap, $inisial);
                if ($value === '') {
                    continue;
                }

                $options = is_array($placement->options) ? $placement->options : [];
                $col = (string) ($options['color'] ?? $colorHex);
                $fontSizePt = $this->resolveTextFontSizePt((string) $placement->field_type, $hMm);

                $workingPdf = $this->replaceWorkingPdf(
                    $workingPdf,
                    $this->applyTextStamp($workingPdf, $value, (int) $placement->page, $coords, $fontSizePt, $col, $config->tempPrefix)
                );
            }

            if ($shouldEmbedQr && ! $hasQrPlacement) {
                $lastPage = count($pageDimensions);
                $lastPageHeight = (float) $pageDimensions[$lastPage - 1]['height'];
                $lastPageWidth = (float) $pageDimensions[$lastPage - 1]['width'];
                $qrSizeMm = self::QR_DEFAULT_MM;

                try {
                    $qrPath = $this->writeTempPng(
                        DocumentVerificationQr::pngBinary($verifyUrl),
                        $config->tempPrefix . 'qr_'
                    );
                    $tempImages[] = $qrPath;

                    $pageWidthMm = PdfCoordinateConverter::pointsToMm($lastPageWidth);
                    $pageHeightMm = PdfCoordinateConverter::pointsToMm($lastPageHeight);
                    $xQrMm = max(2.0, $pageWidthMm - $qrSizeMm - 8.0);
                    $yQrMm = max(2.0, $pageHeightMm - $qrSizeMm - 8.0);
                    $qrCoords = PdfCoordinateConverter::topLeftMmToBottomLeftPoints(
                        $xQrMm,
                        $yQrMm,
                        $qrSizeMm,
                        $lastPageHeight
                    );
                    $prepared = $this->prepareOverlayImage($qrPath, $qrSizeMm, $qrSizeMm, true, $config->tempPrefix);
                    $tempImages[] = $prepared['path'];

                    $workingPdf = $this->replaceWorkingPdf(
                        $workingPdf,
                        $this->applyImageOverlay($workingPdf, $prepared['path'], $lastPage, $qrCoords, $prepared['height_pt'], $config->tempPrefix)
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return (string) file_get_contents($workingPdf);
        } finally {
            $this->cleanupTempFile($workingPdf);
            foreach ($tempImages as $imagePath) {
                $this->cleanupTempFile($imagePath);
            }
        }
    }

    private function resolvePlacementText($placement, SignableDocument $document, string $namaLengkap, string $inisial): string
    {
        if ($placement->field_type === 'nomor_surat') {
            return (string) ($document->getNomorSurat() ?? '');
        }

        $value = $placement->value;
        if ($value !== null && $value !== '') {
            return (string) $value;
        }

        return match ($placement->field_type) {
            'signature', 'nama' => $namaLengkap,
            'inisial' => $inisial,
            'tanggal' => $document->getTanggal() ? $document->getTanggal()->format('d-m-Y') : '',
            default => '',
        };
    }

    private function resolveTextFontSizePt(string $fieldType, float $heightMm): float
    {
        return match ($fieldType) {
            'nomor_surat' => DocumentNumberTextImage::fontSizePtFromHeightMm($heightMm),
            'inisial' => max(8.0, min(11.0, PdfCoordinateConverter::mmToPoints($heightMm))),
            default => max(8.0, PdfCoordinateConverter::mmToPoints($heightMm)),
        };
    }

    private function applyImageOverlay(
        string $inputPdfPath,
        string $imagePath,
        int $pageNumber,
        array $coords,
        int $heightPt,
        string $tempPrefix
    ): string {
        $output = $this->createTempPdfPath($tempPrefix . 'img_');

        $binary = $this->stirling->addImageStamp($inputPdfPath, $imagePath, [
            'stampType' => 'image',
            'pageNumbers' => (string) $pageNumber,
            'fontSize' => (float) max(1, $heightPt),
            'rotation' => 0,
            'opacity' => 1,
            'position' => 5,
            'overrideX' => $coords['x'],
            'overrideY' => $coords['y'],
            'customMargin' => 'medium',
        ]);

        file_put_contents($output, $binary);

        return $output;
    }

    private function applyTextStamp(
        string $inputPdfPath,
        string $text,
        int $pageNumber,
        array $coords,
        float $fontSizePt,
        string $colorHex,
        string $tempPrefix
    ): string {
        $output = $this->createTempPdfPath($tempPrefix . 'text_');

        $binary = $this->stirling->addTextStamp($inputPdfPath, [
            'stampType' => 'text',
            'stampText' => $text,
            'pageNumbers' => (string) $pageNumber,
            'fontSize' => $fontSizePt,
            'rotation' => 0,
            'opacity' => 1,
            'position' => 5,
            'overrideX' => $coords['x'],
            'overrideY' => $coords['y'],
            'customMargin' => 'medium',
            'customColor' => $this->normalizeHexColor($colorHex),
            'alphabet' => 'roman',
        ]);

        file_put_contents($output, $binary);

        return $output;
    }

    private function replaceWorkingPdf(string $current, string $next): string
    {
        if ($current !== $next) {
            $this->cleanupTempFile($current);
        }

        return $next;
    }

    /**
     * @return array{path: string, width_pt: int, height_pt: int}
     */
    private function prepareOverlayImage(
        string $sourcePath,
        float $widthMm,
        float $heightMm,
        bool $flattenWhite,
        string $tempPrefix
    ): array {
        $widthPt = max(1, (int) round(PdfCoordinateConverter::mmToPoints($widthMm)));
        $heightPt = max(1, (int) round(PdfCoordinateConverter::mmToPoints($heightMm)));

        // QR di PDF: 1 point ≠ 1 pixel — render raster ~300 DPI agar scanner bisa baca.
        $canvasW = $flattenWhite
            ? max($widthPt * 4, (int) round($widthMm * 300 / 25.4))
            : $widthPt;
        $canvasH = $flattenWhite
            ? max($heightPt * 4, (int) round($heightMm * 300 / 25.4))
            : $heightPt;

        if (! extension_loaded('gd')) {
            return ['path' => $sourcePath, 'width_pt' => $widthPt, 'height_pt' => $heightPt];
        }

        $source = $this->loadImage($sourcePath);
        if ($source === null) {
            return ['path' => $sourcePath, 'width_pt' => $widthPt, 'height_pt' => $heightPt];
        }

        $canvas = imagecreatetruecolor($canvasW, $canvasH);
        if ($canvas === false) {
            imagedestroy($source);

            return ['path' => $sourcePath, 'width_pt' => $widthPt, 'height_pt' => $heightPt];
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        $scale = min($canvasW / $srcW, $canvasH / $srcH);
        $drawW = max(1, (int) round($srcW * $scale));
        $drawH = max(1, (int) round($srcH * $scale));
        $dstX = (int) floor(($canvasW - $drawW) / 2);
        $dstY = (int) floor(($canvasH - $drawH) / 2);

        if ($flattenWhite) {
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
            imagealphablending($canvas, true);
            imagecopyresampled($canvas, $source, $dstX, $dstY, 0, 0, $drawW, $drawH, $srcW, $srcH);
        } else {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
            imagefill($canvas, 0, 0, $transparent);
            imagealphablending($canvas, true);
            imagesavealpha($canvas, true);
            imagecopyresampled($canvas, $source, $dstX, $dstY, 0, 0, $drawW, $drawH, $srcW, $srcH);
        }

        imagedestroy($source);

        $target = $this->writeTempPngFromGd($canvas, $tempPrefix . 'overlay_');
        imagedestroy($canvas);

        return ['path' => $target, 'width_pt' => $widthPt, 'height_pt' => $heightPt];
    }

    private function loadImage(string $path): ?\GdImage
    {
        $info = @getimagesize($path);
        if (! is_array($info)) {
            return null;
        }

        return match ($info[2]) {
            IMAGETYPE_PNG => @imagecreatefrompng($path) ?: null,
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path) ?: null,
            default => null,
        };
    }

    private function copyToTempPdf(string $sourcePath, string $prefix): string
    {
        $pdfPath = $this->createTempPdfPath($prefix);
        if (! copy($sourcePath, $pdfPath)) {
            throw new RuntimeException('Gagal menyalin PDF sementara.');
        }

        return $pdfPath;
    }

    private function createTempPdfPath(string $prefix): string
    {
        $target = tempnam(sys_get_temp_dir(), $prefix);
        if ($target === false) {
            throw new RuntimeException('Gagal membuat file sementara PDF.');
        }

        $pdfPath = $target . '.pdf';
        rename($target, $pdfPath);

        return $pdfPath;
    }

    private function writeTempPng(string $binary, string $prefix): string
    {
        $target = tempnam(sys_get_temp_dir(), $prefix);
        if ($target === false) {
            throw new RuntimeException('Gagal membuat file sementara gambar.');
        }

        $pngPath = $target . '.png';
        rename($target, $pngPath);
        file_put_contents($pngPath, $binary);

        return $pngPath;
    }

    private function writeTempPngFromGd(\GdImage $image, string $prefix): string
    {
        $target = tempnam(sys_get_temp_dir(), $prefix);
        if ($target === false) {
            throw new RuntimeException('Gagal membuat file sementara gambar.');
        }

        $pngPath = $target . '.png';
        rename($target, $pngPath);
        imagepng($image, $pngPath);

        return $pngPath;
    }

    private function cleanupTempFile(?string $path): void
    {
        if (is_string($path) && $path !== '' && is_file($path)) {
            @unlink($path);
        }
    }

    private function normalizeHexColor(string $hex): string
    {
        $hex = trim($hex);
        if ($hex === '') {
            return '#000000';
        }
        if (! str_starts_with($hex, '#')) {
            $hex = '#' . $hex;
        }

        return $hex;
    }
}
