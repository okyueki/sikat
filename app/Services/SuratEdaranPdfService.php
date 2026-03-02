<?php

namespace App\Services;

use App\Models\MasterStempel;
use App\Models\SuratEdaran;
use setasign\Fpdi\Tcpdf\Fpdi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

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
                        $pdf->Image($stempelPath, $x, $y, $w, $h, '', '', '', false, 300);
                    }
                    continue;
                }

                // Handle image signature
                if ($p->field_type === 'signature' && $signatureType === 'image' && $signatureImgFullPath) {
                    $pdf->Image($signatureImgFullPath, $x, $y, $w, $h, '', '', '', false, 300);
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
