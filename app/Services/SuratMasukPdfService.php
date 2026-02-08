<?php

namespace App\Services;

use App\Models\Surat;
use App\Models\VerifikasiSurat;
use App\Models\TandaTangan;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Layanan untuk mendapatkan path file PDF surat masuk.
 * Jika surat disimpan sebagai PDF → return path asli.
 * Jika DOCX (surat internal) → konversi ke PDF (isi template + LibreOffice) lalu return path PDF.
 */
class SuratMasukPdfService
{
    /** Path LibreOffice (Linux: /usr/bin/libreoffice, Windows: set LIBREOFFICE_PATH di .env) */
    public static function getLibreOfficePath(): string
    {
        return config('app.libreoffice_path', '/usr/bin/libreoffice');
    }

    /**
     * Mengembalikan path lengkap ke file PDF, atau null jika gagal.
     * Panggil ini lalu stream file dengan response()->file($path).
     */
    public static function getPdfPath(Surat $surat): ?string
    {
        $surat->load(['pegawai', 'sifat_surat', 'klasifikasi_surat']);
        if (empty($surat->file_surat)) {
            return null;
        }

        $basePath = realpath(storage_path('app/public'));
        if ($basePath === false) {
            return null;
        }
        $relativePath = ltrim(str_replace('\\', '/', $surat->file_surat), '/');
        $fullPath = realpath($basePath . DIRECTORY_SEPARATOR . $relativePath);
        if ($fullPath === false || !is_file($fullPath) || strpos($fullPath, $basePath) !== 0) {
            return null;
        }

        $ext = strtolower(pathinfo($surat->file_surat, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            return $fullPath;
        }

        if ($ext === 'docx') {
            return self::convertDocxToPdf($fullPath, $surat);
        }

        return null;
    }

    private static function convertDocxToPdf(string $docxPath, Surat $surat): ?string
    {
        $dir = storage_path('app/public/temp_surat');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        try {
            $templateProcessor = new TemplateProcessor($docxPath);
            $templateProcessor->setValue('nomor', htmlspecialchars($surat->nomor_surat ?? '', ENT_QUOTES, 'UTF-8'));
            $templateProcessor->setValue('perihal', htmlspecialchars($surat->perihal ?? '', ENT_QUOTES, 'UTF-8'));
            $templateProcessor->setValue('sifat', htmlspecialchars($surat->sifat_surat ? ($surat->sifat_surat->nama_sifat_surat ?? '') : '', ENT_QUOTES, 'UTF-8'));
            $templateProcessor->setValue('tanggal_surat', $surat->tanggal_surat ? htmlspecialchars(Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y'), ENT_QUOTES, 'UTF-8') : '');
            $templateProcessor->setValue('lampiran', htmlspecialchars($surat->lampiran ?? '', ENT_QUOTES, 'UTF-8'));
            $pegawai = $surat->pegawai;
            $templateProcessor->setValue('pengirim', htmlspecialchars($pegawai ? $pegawai->nama : '', ENT_QUOTES, 'UTF-8'));
            $templateProcessor->setValue('jabatan', htmlspecialchars($pegawai ? ($pegawai->jbtn ?? '') : '', ENT_QUOTES, 'UTF-8'));

            $tandaTangan = TandaTangan::where('id_surat', $surat->id_surat)->get();
            $placeholders = ['qrcode', 'qrcode_2', 'qrcode_3', 'qrcode_4'];
            $kotakAbuPath = public_path('assets/images/kotakabu.jpg');
            $logoPath = public_path('assets/images/web.png');
            $pdfUrl = url(route('surat.show', ['encryptedKodeSurat' => encrypt($surat->kode_surat)]));

            foreach ($placeholders as $placeholder) {
                $qrcodePath = storage_path('app/public/temp_surat/qrcode_' . $surat->id_surat . '_' . $placeholder . '.png');
                $ttd = $tandaTangan->where('status_ttd', $placeholder)->first();

                if ($ttd && $ttd->nik_penandatangan === $surat->nik_pengirim) {
                    if (file_exists($logoPath)) {
                        QrCode::format('png')->merge($logoPath, 0.2, true)->size(200)->margin(0)->generate($pdfUrl, $qrcodePath);
                        $templateProcessor->setImageValue($placeholder, ['path' => $qrcodePath, 'width' => 100, 'height' => 100, 'ratio' => true]);
                    }
                } else {
                    $verifikasiSurat = $ttd ? VerifikasiSurat::where('id_surat', $surat->id_surat)
                        ->where('nik_verifikator', $ttd->nik_penandatangan)
                        ->where('status_surat', 'Disetujui')
                        ->first() : null;

                    if ($verifikasiSurat && file_exists($logoPath)) {
                        QrCode::format('png')->merge($logoPath, 0.2, true)->size(200)->margin(0)->generate($pdfUrl, $qrcodePath);
                        $templateProcessor->setImageValue($placeholder, ['path' => $qrcodePath, 'width' => 100, 'height' => 100, 'ratio' => true]);
                    } elseif (file_exists($kotakAbuPath)) {
                        $templateProcessor->setImageValue($placeholder, ['path' => $kotakAbuPath, 'width' => 100, 'height' => 100, 'ratio' => true]);
                    }
                }
            }

            $filledDocxPath = $dir . DIRECTORY_SEPARATOR . 'surat-' . $surat->kode_surat . '-' . time() . '.docx';
            $templateProcessor->saveAs($filledDocxPath);

            $pdfPath = $dir . DIRECTORY_SEPARATOR . 'surat-' . $surat->kode_surat . '-' . time() . '.pdf';
            $libreOfficePath = self::getLibreOfficePath();
            $outDir = dirname($pdfPath);
            $command = '"' . $libreOfficePath . '" --headless --convert-to pdf --outdir "' . $outDir . '" "' . $filledDocxPath . '"';
            if (PHP_OS_FAMILY === 'Windows') {
                $command = '"' . $libreOfficePath . '" --headless --convert-to pdf --outdir "' . str_replace('/', '\\', $outDir) . '" "' . str_replace('/', '\\', $filledDocxPath) . '"';
            }
            shell_exec($command);

            return file_exists($pdfPath) ? $pdfPath : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
