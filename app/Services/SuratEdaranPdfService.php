<?php

namespace App\Services;

use App\Models\MasterStempel;
use App\Models\SuratEdaran;
use setasign\Fpdi\Tcpdf\Fpdi;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SuratEdaranPdfService
{
    /**
     * Generate konten PDF yang sudah di-overlay tanda tangan (untuk disimpan ke storage).
     */
    public static function generateSignedPdfContent(SuratEdaran $surat): string
    {
        if (! $surat->file_pdf) {
            throw new \RuntimeException('File PDF belum diisi pada surat edaran ID: ' . $surat->id);
        }
        
        $path = Storage::disk('public')->path($surat->file_pdf);
        if (! file_exists($path)) {
            throw new \RuntimeException('File PDF tidak ditemukan: ' . $surat->file_pdf . ' (Path: ' . $path . ')');
        }

        $pdf = new Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        try {
            $pageCount = $pdf->setSourceFile($path);
        } catch (CrossReferenceException $e) {
            $reason = self::detectIncompatiblePdfReason($path);
            throw new \RuntimeException(
                'File PDF tidak kompatibel untuk proses tanda tangan digital internal. ' .
                $reason . ' ' .
                'Silakan simpan ulang dokumen sebagai PDF standar (misalnya via Print to PDF), lalu upload ulang.',
                0,
                $e
            );
        }

        $signatureDetail = $surat->signature_detail ?? [];
        $namaLengkap = $signatureDetail['nama_lengkap'] ?? $surat->penandatangan?->nama ?? '';
        $inisial = $signatureDetail['inisial'] ?? '';
        $fontStyle = $signatureDetail['font_style'] ?? 'times';
        $colorHex = $signatureDetail['color'] ?? '#000000';
        $signatureType = $signatureDetail['type'] ?? 'text';
        $signatureImagePath = $signatureDetail['image_path'] ?? null;
        $rgb = self::hexToRgb($colorHex);

        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetFont('dejavusans', '', 9);

        $masterStempel = MasterStempel::getPerusahaan();
        $stempelPath = $masterStempel && $masterStempel->file_path
            ? Storage::disk('public')->path($masterStempel->file_path)
            : null;
        
        // Path gambar tanda tangan (jika type = image)
        $signatureImgFullPath = null;
        if ($signatureType === 'image' && $signatureImagePath) {
            $signatureImgFullPath = Storage::disk('public')->path($signatureImagePath);
            if (!file_exists($signatureImgFullPath)) {
                $signatureImgFullPath = null;
            }
        }

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tpl = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tpl);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

            $placements = $surat->placements->where('page', $pageNo)->sortBy('sort_order');

            foreach ($placements as $p) {
                $x = (float) $p->x;
                $y = (float) $p->y;
                $w = $p->width ? (float) $p->width : 40;
                $h = $p->height ? (float) $p->height : 8;

                if ($p->field_type === 'stempel') {
                    if ($stempelPath && file_exists($stempelPath)) {
                        // Samakan dengan tampilan draft: beri dasar putih agar konten PDF di belakang tidak "tembus".
                        $pdf->SetFillColor(255, 255, 255);
                        $pdf->Rect($x, $y, $w, $h, 'F');
                        $pdf->Image($stempelPath, $x, $y, $w, $h, 'PNG', '', '', false, 300);
                    }
                    continue;
                }

                // Handle image signature
                if ($p->field_type === 'signature' && $signatureType === 'image' && $signatureImgFullPath) {
                    // Samakan dengan tampilan draft: beri dasar putih agar konten PDF di belakang tidak "tembus".
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->Rect($x, $y, $w, $h, 'F');
                    $pdf->Image($signatureImgFullPath, $x, $y, $w, $h, 'PNG', '', '', false, 300);
                    continue;
                }

                $value = $p->value;
                if ($value === null || $value === '') {
                    switch ($p->field_type) {
                        case 'signature':
                            $value = $namaLengkap;
                            break;
                        case 'inisial':
                            $value = $inisial;
                            break;
                        case 'nama':
                            $value = $namaLengkap;
                            break;
                        case 'tanggal':
                            $value = $surat->tanggal ? $surat->tanggal->format('d-m-Y') : '';
                            break;
                        default:
                            $value = (string) $value;
                    }
                }
                if ($value === '') {
                    continue;
                }

                $options = $p->options ?? [];
                $font = $options['font_style'] ?? $fontStyle;
                $col = $options['color'] ?? $colorHex;
                $rgb = self::hexToRgb($col);

                $pdf->SetXY($x, $y);
                $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
                $pdf->SetFont('dejavusans', '', 9);
                if ($p->field_type === 'signature' || $p->field_type === 'inisial') {
                    $pdf->SetFont('times', 'I', 10);
                }
                $pdf->Cell($w, $h, $value, 0, 0, 'L', false, '', 0, false, 'T', 'T');
            }

            // Tambahkan QR verifikasi pada halaman terakhir untuk dokumen yang sudah sah
            if ($pageNo === $pageCount && $surat->tanggal_ditandatangani) {
                try {
                    $verifyUrl = route('surat_edaran.verify', $surat);
                    $tempDir = 'temp_surat';
                    if (!Storage::disk('public')->exists($tempDir)) {
                        Storage::disk('public')->makeDirectory($tempDir);
                    }

                    $qrRelativePath = $tempDir . '/qr_surat_edaran_' . $surat->id . '.png';
                    $qrFullPath = Storage::disk('public')->path($qrRelativePath);
                    QrCode::format('png')->size(220)->margin(1)->generate($verifyUrl, $qrFullPath);

                    if (file_exists($qrFullPath)) {
                        // Selalu pojok kanan bawah (konsisten, mudah dijelaskan ke user), dengan latar putih
                        // agar QR tetap terbaca walau menimpa tanda tangan/stempel — pola mirip layanan tanda tangan umum.
                        $qrSizeMm = 22.0;
                        $marginMm = 8.0;
                        $padMm = 1.2;
                        $xQr = max(2.0, (float) $size['width'] - $qrSizeMm - $marginMm);
                        $yQr = max(2.0, (float) $size['height'] - $qrSizeMm - $marginMm);

                        $pdf->SetFillColor(255, 255, 255);
                        $pdf->Rect($xQr - $padMm, $yQr - $padMm, $qrSizeMm + (2 * $padMm), $qrSizeMm + (2 * $padMm), 'F');
                        $pdf->Image($qrFullPath, $xQr, $yQr, $qrSizeMm, $qrSizeMm, 'PNG', '', '', false, 300);

                        Storage::disk('public')->delete($qrRelativePath);
                    }
                } catch (\Throwable $e) {
                    // QR verifikasi bersifat tambahan, jangan gagalkan proses PDF jika terjadi error.
                    report($e);
                }
            }
        }

        return $pdf->Output('', 'S');
    }

    /**
     * Generate dan response download PDF yang sudah di-overlay tanda tangan.
     */
    public static function generateSignedPdf(SuratEdaran $surat): Response
    {
        $pdfContent = self::generateSignedPdfContent($surat);
        $filename = 'surat_edaran_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $surat->judul_surat) . '_signed.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Validasi awal kompatibilitas parser FPDI terhadap file PDF yang di-upload.
     * Dipakai agar kegagalan diketahui lebih awal saat upload/edit, bukan saat finalisasi.
     */
    public static function assertPdfParsableFromStoragePath(string $relativePath): void
    {
        if ($relativePath === '') {
            throw new \RuntimeException('Path file PDF kosong.');
        }

        $path = Storage::disk('public')->path($relativePath);
        if (! file_exists($path)) {
            throw new \RuntimeException('File PDF tidak ditemukan: ' . $relativePath);
        }

        $pdf = new Fpdi();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        try {
            $pdf->setSourceFile($path);
        } catch (CrossReferenceException $e) {
            $reason = self::detectIncompatiblePdfReason($path);
            throw new \RuntimeException(
                'File PDF menggunakan kompresi/struktur yang belum didukung parser sistem. ' .
                $reason . ' ' .
                'Silakan simpan ulang sebagai PDF standar (Print to PDF), lalu upload kembali.',
                0,
                $e
            );
        }
    }

    private static function detectIncompatiblePdfReason(string $absolutePath): string
    {
        $chunk = @file_get_contents($absolutePath, false, null, 0, 1024 * 1024);
        if (! is_string($chunk) || $chunk === '') {
            return 'Penyebab detail tidak dapat dibaca dari struktur file PDF.';
        }

        $reasons = [];
        if (preg_match('/%PDF-(\d\.\d)/', $chunk, $m)) {
            $version = $m[1] ?? '';
            if ($version !== '' && version_compare($version, '1.5', '>=')) {
                $reasons[] = 'Versi PDF ' . $version . ' (sering memakai struktur object stream modern)';
            }
        }

        if (str_contains($chunk, '/ObjStm')) {
            $reasons[] = 'Terdeteksi Object Stream (/ObjStm)';
        }
        if (str_contains($chunk, '/Type/XRef') || str_contains($chunk, '/XRef')) {
            $reasons[] = 'Terdeteksi Cross-Reference Stream (XRef stream)';
        }
        if (str_contains($chunk, '/Encrypt')) {
            $reasons[] = 'File PDF terenkripsi/protected';
        }

        if (empty($reasons)) {
            return 'Kemungkinan memakai struktur kompresi PDF tingkat lanjut yang belum didukung parser FPDI free.';
        }

        return 'Indikasi teknis: ' . implode('; ', $reasons) . '.';
    }

    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return [0, 0, 0];
        }
        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
