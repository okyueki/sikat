<?php

namespace App\Services;

use App\Models\MasterStempel;
use App\Models\SuratEdaran;
use setasign\Fpdi\Tcpdf\Fpdi;
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
        $pageCount = $pdf->setSourceFile($path);

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
                        $qrSizeMm = 22.0;
                        $marginMm = 8.0;
                        $qrPosition = $masterStempel?->qr_position ?? 'bottom_right';
                        $safePadding = 0.8;
                        $qrBoxW = $qrSizeMm + ($safePadding * 2);
                        $qrBoxH = $qrSizeMm + ($safePadding * 2);

                        $cornerToCoord = function (string $corner) use ($size, $qrSizeMm, $marginMm): array {
                            $x = max(2.0, (float) $size['width'] - $qrSizeMm - $marginMm);
                            $y = max(2.0, (float) $size['height'] - $qrSizeMm - $marginMm);
                            if ($corner === 'bottom_left') {
                                $x = $marginMm;
                            }

                            return [$x, $y];
                        };

                        $overlap = function (array $a, array $b): bool {
                            return !(
                                $a['x'] + $a['w'] <= $b['x'] ||
                                $b['x'] + $b['w'] <= $a['x'] ||
                                $a['y'] + $a['h'] <= $b['y'] ||
                                $b['y'] + $b['h'] <= $a['y']
                            );
                        };

                        $placementBoxes = [];
                        foreach ($placements as $placement) {
                            $placementBoxes[] = [
                                'x' => (float) $placement->x,
                                'y' => (float) $placement->y,
                                'w' => $placement->width ? (float) $placement->width : 40.0,
                                'h' => $placement->height ? (float) $placement->height : 8.0,
                            ];
                        }

                        // Mode aman: hanya area bawah agar tidak mengganggu konten utama dokumen.
                        $allowedBottomCorners = ['bottom_right', 'bottom_left'];
                        $preferred = in_array($qrPosition, $allowedBottomCorners, true) ? $qrPosition : 'bottom_right';
                        $prioritizedCorners = array_values(array_unique(array_merge([$preferred], $allowedBottomCorners)));

                        $chosenQr = null;
                        foreach ($prioritizedCorners as $corner) {
                            [$candidateX, $candidateY] = $cornerToCoord($corner);
                            $candidateQrBox = [
                                'x' => max(1.0, $candidateX - $safePadding),
                                'y' => max(1.0, $candidateY - $safePadding),
                                'w' => $qrBoxW,
                                'h' => $qrBoxH,
                            ];

                            $isCollision = false;
                            foreach ($placementBoxes as $box) {
                                if ($overlap($candidateQrBox, $box)) {
                                    $isCollision = true;
                                    break;
                                }
                            }

                            if (! $isCollision) {
                                $chosenQr = [$candidateX, $candidateY];
                                break;
                            }
                        }

                        if ($chosenQr) {
                            [$xQr, $yQr] = $chosenQr;
                            $pdf->Image($qrFullPath, $xQr, $yQr, $qrSizeMm, $qrSizeMm, 'PNG', '', '', false, 300);
                        }

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
