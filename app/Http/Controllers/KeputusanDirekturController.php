<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AdaptsSignableDocumentViews;
use App\Models\MasterStempel;
use App\Models\MasterTandaTangan;
use App\Models\KeputusanDirektur;
use App\Models\KeputusanDirekturPlacement;
use App\Models\AuditTrail;
use App\Models\Pegawai;
use App\Services\KeputusanDirekturNumberService;
use App\Services\KeputusanDirekturPdfService;
use App\Support\DocumentVerificationQr;
use App\Support\DocumentVerificationUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Keputusan Direktur – Judul, Nomor, Deskripsi, Tanggal, Yang menyetujui, File PDF.
 * Halaman tanda tangani: drag-drop kotak isian (tanda tangan teks/Latin, inisial, nama, tanggal, teks).
 */
class KeputusanDirekturController extends Controller
{
    use AdaptsSignableDocumentViews;

    protected function signableRoutePrefix(): string
    {
        return 'keputusan_direktur';
    }

    protected function signableDocumentLabel(): string
    {
        return 'Keputusan Direktur';
    }

    protected function signableDocumentType(): string
    {
        return 'KEP';
    }

    protected function hasMasaBerlakuFields(): bool
    {
        return true;
    }

    public function index()
    {
        $title = 'Keputusan Direktur';
        $items = KeputusanDirektur::with('penandatangan')->orderBy('tanggal', 'desc')->paginate(15);
        return view('surat_edaran.index', array_merge($this->signableViewAdapter(), compact('title', 'items')));
    }

    public function create()
    {
        $title = 'Tambah Keputusan Direktur';
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->orderBy('nama')->get();
        $previewNomorSurat = KeputusanDirekturNumberService::previewNext(
            \Carbon\Carbon::parse(old('tanggal', now()->toDateString()))
        );

        return view('surat_edaran.create', array_merge($this->signableViewAdapter(), compact('title', 'pegawai', 'previewNomorSurat')));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(array_merge([
            'judul_surat' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal' => 'required|date',
            'nik_penandatangan' => 'nullable|string|max:50',
            'file_pdf' => 'required|file|mimes:pdf|max:20480',
        ], $this->masaBerlakuValidationRules()), [], array_merge([
            'judul_surat' => 'Judul Surat',
            'deskripsi' => 'Deskripsi',
            'tanggal' => 'Tanggal',
            'nik_penandatangan' => 'Yang menyetujui',
            'file_pdf' => 'File PDF',
        ], $this->masaBerlakuAttributeNames()));

        $path = $request->file('file_pdf')->store('keputusan_direktur', 'public');
        $validated['file_pdf'] = $path;
        $validated['created_by_username'] = (string) (auth()->user()->username ?? '');
        try {
            KeputusanDirekturPdfService::assertPdfParsableFromStoragePath($path);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw ValidationException::withMessages([
                'file_pdf' => $e->getMessage(),
            ]);
        }

        $created = DB::transaction(function () use ($validated) {
            $payload = KeputusanDirekturNumberService::assignOnCreate($validated);

            return KeputusanDirektur::create($payload);
        });
        AuditTrail::logCreate(
            'keputusan_direktur',
            'keputusan_direktur',
            $created->id,
            $created->toArray(),
            'Membuat keputusan direktur baru'
        );

        return redirect()->route('keputusan_direktur.index')->with('success', 'Keputusan Direktur berhasil disimpan.');
    }

    public function show(KeputusanDirektur $keputusan_direktur)
    {
        $keputusan_direktur->load(['penandatangan', 'placements']);
        $title = 'Detail Keputusan Direktur';
        $verifyUrl = DocumentVerificationUrl::qrVerifyUrl('keputusan_direktur', $keputusan_direktur);
        $verificationQrUrl = $keputusan_direktur->tanggal_ditandatangani
            ? DocumentVerificationUrl::qrImageUrl('keputusan_direktur', $keputusan_direktur)
            : null;
        $verificationQrDataUri = $keputusan_direktur->tanggal_ditandatangani
            ? DocumentVerificationQr::dataUri($verifyUrl)
            : null;

        $surat_edaran = $keputusan_direktur;

        return view('surat_edaran.show', array_merge($this->signableViewAdapter(), compact('title', 'surat_edaran', 'verificationQrDataUri', 'verificationQrUrl', 'verifyUrl')));
    }

    /**
     * Gambar QR verifikasi (PNG) — dipakai halaman detail agar tidak bergantung data-URI panjang / CSP.
     * Konten sama dengan QR yang di-embed ke PDF (URL halaman verifikasi).
     */
    public function verificationQrPng(KeputusanDirektur $keputusan_direktur)
    {
        $verifyUrl = DocumentVerificationUrl::qrVerifyUrl('keputusan_direktur', $keputusan_direktur);
        try {
            $png = DocumentVerificationQr::pngBinary($verifyUrl);
        } catch (\Throwable $e) {
            report($e);
            abort(503, 'QR verifikasi tidak dapat dibuat.');
        }
        if ($png === '') {
            abort(503, 'QR verifikasi kosong.');
        }

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function edit(KeputusanDirektur $keputusan_direktur)
    {
        $title = 'Edit Keputusan Direktur';
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->orderBy('nama')->get();
        $surat_edaran = $keputusan_direktur;

        return view('surat_edaran.edit', array_merge($this->signableViewAdapter(), compact('title', 'surat_edaran', 'pegawai')));
    }

    public function update(Request $request, KeputusanDirektur $keputusan_direktur)
    {
        if ($keputusan_direktur->tanggal_ditandatangani) {
            abort(403, 'Dokumen yang sudah sah tidak dapat diubah. Silakan buat dokumen baru.');
        }

        $validated = $request->validate(array_merge([
            'judul_surat' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal' => 'required|date',
            'nik_penandatangan' => 'nullable|string|max:50',
            'file_pdf' => 'nullable|file|mimes:pdf|max:20480',
        ], $this->masaBerlakuValidationRules()), [], array_merge([
            'judul_surat' => 'Judul Surat',
            'deskripsi' => 'Deskripsi',
            'tanggal' => 'Tanggal',
            'nik_penandatangan' => 'Yang menyetujui',
            'file_pdf' => 'File PDF',
        ], $this->masaBerlakuAttributeNames()));

        if ($request->hasFile('file_pdf')) {
            $oldPath = $keputusan_direktur->file_pdf;
            $newPath = $request->file('file_pdf')->store('keputusan_direktur', 'public');

            try {
                KeputusanDirekturPdfService::assertPdfParsableFromStoragePath($newPath);
            } catch (\Throwable $e) {
                Storage::disk('public')->delete($newPath);
                throw ValidationException::withMessages([
                    'file_pdf' => $e->getMessage(),
                ]);
            }

            $validated['file_pdf'] = $newPath;
            if ($keputusan_direktur->file_pdf) {
                Storage::disk('public')->delete($oldPath);
            }
        } else {
            unset($validated['file_pdf']);
        }

        $keputusan_direktur->update($validated);
        AuditTrail::logUpdate(
            'keputusan_direktur',
            'keputusan_direktur',
            $keputusan_direktur->id,
            [],
            $validated,
            'Mengubah keputusan direktur'
        );

        return redirect()->route('keputusan_direktur.index')->with('success', 'Keputusan Direktur berhasil diubah.');
    }

    public function destroy(KeputusanDirektur $keputusan_direktur)
    {
        $this->ensureCanDelete($keputusan_direktur);

        $signatureDetail = is_array($keputusan_direktur->signature_detail) ? $keputusan_direktur->signature_detail : [];
        $signatureImagePath = (string) ($signatureDetail['image_path'] ?? '');

        if ($keputusan_direktur->file_pdf) {
            Storage::disk('public')->delete($keputusan_direktur->file_pdf);
        }
        if ($keputusan_direktur->file_pdf_signed) {
            Storage::disk('public')->delete($keputusan_direktur->file_pdf_signed);
        }
        if ($signatureImagePath !== '' && Storage::disk('public')->exists($signatureImagePath)) {
            Storage::disk('public')->delete($signatureImagePath);
        }
        AuditTrail::logDelete(
            'keputusan_direktur',
            'keputusan_direktur',
            $keputusan_direktur->id,
            $keputusan_direktur->toArray(),
            'Menghapus keputusan direktur'
        );
        $keputusan_direktur->delete();
        return redirect()->route('keputusan_direktur.index')->with('success', 'Keputusan Direktur berhasil dihapus.');
    }

    /**
     * Stream PDF untuk ditampilkan di viewer (PDF.js).
     * Jika dokumen sudah sah (tanggal_ditandatangani) tetapi file yang tersimpan belum versi bertanda tangan,
     * generate dan simpan PDF bertanda tangan dulu, hapus file lama, lalu stream.
     */
    public function streamPdf(KeputusanDirektur $keputusan_direktur)
    {
        $keputusan_direktur->load('placements');

        // Dokumen sudah ditandatangani tapi file yang tersimpan belum versi bertanda tangan (data lama)
        $pathSudahSigned = $keputusan_direktur->file_pdf && str_ends_with($keputusan_direktur->file_pdf, '_signed.pdf');
        $perluSimpanSigned = $keputusan_direktur->tanggal_ditandatangani
            && $keputusan_direktur->placements->isNotEmpty()
            && ! $pathSudahSigned;

        if ($perluSimpanSigned && $keputusan_direktur->file_pdf && Storage::disk('public')->exists($keputusan_direktur->file_pdf)) {
            try {
                $oldPath = $keputusan_direktur->file_pdf;
                $signedContent = KeputusanDirekturPdfService::generateSignedPdfContent($keputusan_direktur, true);
                $newPath = 'keputusan_direktur/' . $keputusan_direktur->id . '_signed.pdf';
                Storage::disk('public')->put($newPath, $signedContent);
                Storage::disk('public')->delete($oldPath);
                $keputusan_direktur->update(['file_pdf' => $newPath, 'file_pdf_signed' => $newPath]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (! $keputusan_direktur->file_pdf) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        $path = Storage::disk('public')->path($keputusan_direktur->file_pdf);
        if (! file_exists($path)) {
            abort(404, 'File PDF tidak ditemukan.');
        }
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $keputusan_direktur->judul_surat) . '.pdf"',
        ]);
    }

    /**
     * Halaman tanda tangani: PDF viewer + sidebar (detail tanda tangan + kotak isian drag-drop).
     */
    public function tandaTangani(KeputusanDirektur $keputusan_direktur)
    {
        $this->ensureCanSign($keputusan_direktur);
        if ($keputusan_direktur->tanggal_ditandatangani) {
            return redirect()
                ->route('keputusan_direktur.show', $keputusan_direktur)
                ->with('warning', 'Dokumen sudah sah dan tidak dapat dibuka lagi di mode tanda tangan.');
        }

        $keputusan_direktur->load(['penandatangan', 'placements']);
        if (! $keputusan_direktur->nomor_surat) {
            KeputusanDirekturNumberService::assignTo($keputusan_direktur);
            $keputusan_direktur->refresh();
        }
        $title = 'Tanda tangani PDF';
        $pdfUrl = route('keputusan_direktur.streamPdf', $keputusan_direktur);
        $pegawai = $keputusan_direktur->penandatangan;
        $signatureDetail = $keputusan_direktur->signature_detail ?? [];
        if ($pegawai && empty($signatureDetail['nama_lengkap'])) {
            $signatureDetail['nama_lengkap'] = $pegawai->nama ?? '';
            $signatureDetail['inisial'] = $this->inisialFromNama($pegawai->nama ?? '');
        }
        $placementsForJs = $keputusan_direktur->placements->map(function ($p) {
            return [
                'field_type' => $p->field_type,
                'page' => (int) $p->page,
                'x' => (float) $p->x,
                'y' => (float) $p->y,
                'width' => (float) ($p->width ?? 40),
                'height' => (float) ($p->height ?? 8),
                'value' => $p->value,
            ];
        })->values()->all();

        $masterTandaTanganList = auth()->user()->masterTandaTangan()->orderByDesc('is_default')->orderBy('id')->get();
        $masterStempel = MasterStempel::getPerusahaan();

        $verifyUrl = DocumentVerificationUrl::qrVerifyUrl('keputusan_direktur', $keputusan_direktur);
        $surat_edaran = $keputusan_direktur;

        return view('surat_edaran.tanda_tangani', array_merge($this->signableViewAdapter(), compact('title', 'surat_edaran', 'pdfUrl', 'signatureDetail', 'placementsForJs', 'masterTandaTanganList', 'masterStempel', 'verifyUrl')));
    }

    /**
     * Simpan detail tanda tangan (nama, inisial, font, warna) dan posisi kotak isian (placements).
     */
    public function saveSignatureAndPlacements(Request $request, KeputusanDirektur $keputusan_direktur)
    {
        $this->ensureCanSign($keputusan_direktur);

        if ($keputusan_direktur->tanggal_ditandatangani) {
            return $this->jsonError('Dokumen sudah sah dan tidak dapat diubah.', 409, true);
        }

        $finalize = filter_var($request->input('finalize', false), FILTER_VALIDATE_BOOLEAN);

        $validated = $request->validate([
            'nama_lengkap' => 'nullable|string|max:255',
            'inisial' => 'nullable|string|max:20',
            'font_style' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'signature_type' => 'nullable|string|in:text,image',
            'signature_image_url' => 'nullable|string|max:4000000',
            'cropped_signature_image' => 'nullable|string|max:4000000',
            'finalize' => 'nullable|boolean',
            'placements' => 'nullable|array',
            'placements.*.field_type' => 'required|string|in:signature,inisial,nama,tanggal,teks,stempel,qr_verifikasi,nomor_surat',
            'placements.*.page' => 'required|integer|min:1',
            'placements.*.x' => 'required|numeric',
            'placements.*.y' => 'required|numeric',
            'placements.*.width' => 'nullable|numeric',
            'placements.*.height' => 'nullable|numeric',
            'placements.*.value' => 'nullable|string',
            'placements.*.options' => 'nullable|array',
        ]);

        // Handle image URL - prioritize cropped image over regular image
        $existingSignatureDetail = is_array($keputusan_direktur->signature_detail) ? $keputusan_direktur->signature_detail : [];
        $existingImagePath = (string) ($existingSignatureDetail['image_path'] ?? '');
        $existingImageUrl = (string) ($existingSignatureDetail['image_url'] ?? '');
        $signatureType = $validated['signature_type'] ?? 'text';
        $croppedImageBinary = $this->decodeSignatureImageDataUrl($validated['cropped_signature_image'] ?? null, 'cropped_signature_image');
        $signatureImageBinary = $this->decodeSignatureImageDataUrl($validated['signature_image_url'] ?? null, 'signature_image_url');

        $imageUrl = $validated['signature_image_url'] ?? $existingImageUrl;
        $imagePath = $existingImagePath !== '' ? $existingImagePath : null;
        $imagePathToDelete = null;

        if ($signatureType === 'image') {
            // Check for cropped image first
            if ($croppedImageBinary !== null) {
                $imagePath = 'tanda_tangan/kep_' . $keputusan_direktur->id . '_' . time() . '.png';
                Storage::disk('public')->put($imagePath, $croppedImageBinary);
                $imageUrl = Storage::disk('public')->url($imagePath);
                if ($existingImagePath !== '' && $existingImagePath !== $imagePath) {
                    $imagePathToDelete = $existingImagePath;
                }
            }
            // Fallback to regular image if no cropped image
            elseif ($signatureImageBinary !== null) {
                $imagePath = 'tanda_tangan/kep_' . $keputusan_direktur->id . '_' . time() . '.png';
                Storage::disk('public')->put($imagePath, $signatureImageBinary);
                $imageUrl = Storage::disk('public')->url($imagePath);
                if ($existingImagePath !== '' && $existingImagePath !== $imagePath) {
                    $imagePathToDelete = $existingImagePath;
                }
            }
        } else {
            $imageUrl = '';
            $imagePath = null;
            if ($existingImagePath !== '') {
                $imagePathToDelete = $existingImagePath;
            }
        }

        $keputusan_direktur->update([
            'signature_detail' => [
                'nama_lengkap' => $validated['nama_lengkap'] ?? '',
                'inisial' => $validated['inisial'] ?? '',
                'font_style' => $validated['font_style'] ?? '1',
                'color' => $validated['color'] ?? '#000000',
                'type' => $signatureType,
                'image_url' => $imageUrl,
                'image_path' => $imagePath,
            ],
        ]);

        if ($imagePathToDelete && Storage::disk('public')->exists($imagePathToDelete)) {
            Storage::disk('public')->delete($imagePathToDelete);
        }

        $keputusan_direktur->placements()->delete();
        if (! empty($validated['placements'])) {
            foreach ($validated['placements'] as $i => $p) {
                $width = isset($p['width']) ? (float) $p['width'] : null;
                $height = isset($p['height']) ? (float) $p['height'] : null;
                if (($p['field_type'] ?? '') === 'qr_verifikasi') {
                    [$width, $height] = KeputusanDirekturPdfService::normalizeQrDimensionsMm(
                        $width ?? 0,
                        $height ?? 0
                    );
                }

                $placementValue = $p['value'] ?? null;
                if (($p['field_type'] ?? '') === 'nomor_surat') {
                    $placementValue = $keputusan_direktur->nomor_surat;
                }

                $keputusan_direktur->placements()->create([
                    'field_type' => $p['field_type'],
                    'page' => (int) $p['page'],
                    'x' => (float) $p['x'],
                    'y' => (float) $p['y'],
                    'width' => $width,
                    'height' => $height,
                    'value' => $placementValue,
                    'options' => $p['options'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        if (! $finalize) {
            AuditTrail::log(
                'update',
                'keputusan_direktur',
                'Menyimpan draft posisi tanda tangan keputusan direktur',
                $keputusan_direktur->id,
                null,
                ['placements_count' => count($validated['placements'] ?? [])],
                'keputusan_direktur'
            );
            return response()->json([
                'success' => true,
                'message' => 'Posisi tanda tangan berhasil disimpan sebagai draft.',
                'finalized' => false,
            ]);
        }

        // Finalisasi: sahkan dokumen dan simpan PDF bertanda tangan
        if (! $keputusan_direktur->nomor_surat) {
            KeputusanDirekturNumberService::assignTo($keputusan_direktur);
            $keputusan_direktur->refresh();
        }

        $oldPath = $keputusan_direktur->file_pdf;
        $sourceRelative = KeputusanDirekturPdfService::sourcePdfRelativePath((int) $keputusan_direktur->id);
        $newPath = 'keputusan_direktur/' . $keputusan_direktur->id . '_signed.pdf';

        if (! Storage::disk('public')->exists($sourceRelative)) {
            if ($oldPath && ! str_ends_with($oldPath, '_signed.pdf') && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->copy($oldPath, $sourceRelative);
            }
        }

        try {
            $keputusan_direktur->load('placements');
            $signedContent = KeputusanDirekturPdfService::generateSignedPdfContent($keputusan_direktur, true);
            Storage::disk('public')->put($newPath, $signedContent);

            DB::transaction(function () use ($keputusan_direktur, $newPath) {
                $keputusan_direktur->update([
                    'tanggal_ditandatangani' => now(),
                    'file_pdf' => $newPath,
                    'file_pdf_signed' => $newPath,
                ]);
            });

            if ($oldPath && $oldPath !== $newPath && $oldPath !== $sourceRelative && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        } catch (\Throwable $e) {
            if (Storage::disk('public')->exists($newPath)) {
                Storage::disk('public')->delete($newPath);
            }
            report($e);
            $errorMessage = 'Gagal memfinalisasi dokumen. Draft posisi tanda tangan tetap tersimpan.';
            $errorStatus = 500;
            if (str_contains(strtolower($e->getMessage()), 'stirling pdf') ||
                str_contains(strtolower($e->getMessage()), 'pdf tidak dapat diproses') ||
                str_contains(strtolower($e->getMessage()), 'pdf tidak kompatibel')) {
                $errorMessage = 'Finalisasi gagal: layanan Stirling PDF tidak dapat memproses dokumen ini. ' .
                    'Pastikan Stirling PDF berjalan dan coba upload ulang PDF asli.';
                $errorStatus = 422;
            }
            return $this->jsonError($errorMessage, $errorStatus, false);
        }

        AuditTrail::log(
            'update',
            'keputusan_direktur',
            'Mengesahkan dokumen keputusan direktur dan menyimpan PDF bertanda tangan',
            $keputusan_direktur->id,
            null,
            ['finalized' => true, 'placements_count' => count($validated['placements'] ?? [])],
            'keputusan_direktur'
        );

        return response()->json([
            'success' => true,
            'message' => 'Tanda tangan dan posisi disimpan. Dokumen sah dan PDF bertanda tangan tersimpan di sistem.',
            'finalized' => true,
        ]);
    }

    /**
     * Generate dan download PDF yang sudah di-overlay tanda tangan.
     */
    public function generateSignedPdf(KeputusanDirektur $keputusan_direktur)
    {
        $keputusan_direktur->load(['penandatangan', 'placements']);
        $pdfContent = KeputusanDirekturPdfService::generateSignedPdfContent($keputusan_direktur, (bool) $keputusan_direktur->tanggal_ditandatangani);
        if ($keputusan_direktur->tanggal_ditandatangani && $keputusan_direktur->file_pdf) {
            Storage::disk('public')->put($keputusan_direktur->file_pdf, $pdfContent);
        }
        $filename = 'keputusan_direktur_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $keputusan_direktur->judul_surat) . '_signed.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function verifyAuthenticity(KeputusanDirektur $keputusan_direktur)
    {
        $keputusan_direktur->load('penandatangan');

        $baseAuditQuery = AuditTrail::query()
            ->where(function ($q) use ($keputusan_direktur) {
                $q->where('module', 'keputusan_direktur')
                    ->orWhere('table_name', 'keputusan_direktur');
            })
            ->where('record_id', $keputusan_direktur->id);

        $auditTrails = (clone $baseAuditQuery)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $createdLog = (clone $baseAuditQuery)
            ->where('action', 'create')
            ->orderBy('created_at', 'asc')
            ->first();

        $uploadLog = (clone $baseAuditQuery)
            ->whereIn('action', ['create', 'update'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->first(function ($log) {
                $newValues = is_array($log->new_values) ? $log->new_values : [];
                return array_key_exists('file_pdf', $newValues);
            });

        $usernames = collect([$createdLog?->username, $uploadLog?->username])
            ->merge($auditTrails->pluck('username'))
            ->filter(fn ($v) => filled($v))
            ->map(fn ($v) => trim((string) $v))
            ->unique()
            ->values();

        $pegawaiByNik = Pegawai::query()
            ->whereIn('nik', $usernames->all())
            ->get(['nik', 'nama'])
            ->mapWithKeys(function ($pegawai) {
                return [(string) $pegawai->nik => (string) $pegawai->nama];
            });

        $userDisplayByUsername = $usernames->mapWithKeys(function (string $username) use ($pegawaiByNik) {
            $nama = $pegawaiByNik->get($username);
            $label = $nama ? ($nama . ' (' . $username . ')') : $username;
            return [$username => $label];
        });

        $title = 'Verifikasi Keabsahan Keputusan Direktur';
        $surat_edaran = $keputusan_direktur;

        return view('surat_edaran.verifikasi', array_merge($this->signableViewAdapter(), compact('title', 'surat_edaran', 'auditTrails', 'createdLog', 'uploadLog', 'userDisplayByUsername')));
    }

    private function ensureCanSign(KeputusanDirektur $keputusan_direktur): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Anda harus login untuk menandatangani dokumen.');
        }

        $level = strtolower((string) ($user->level ?? ''));
        if (in_array($level, ['admin', 'super admin', 'superadmin'], true)) {
            return;
        }

        $userNik = (string) ($user->username ?? '');
        $targetNik = (string) ($keputusan_direktur->nik_penandatangan ?? '');

        if (empty($targetNik)) {
            abort(403, 'Dokumen ini belum memiliki penandatangan yang ditentukan.');
        }

        if ($userNik !== $targetNik) {
            abort(403, 'Hanya pegawai yang dipilih sebagai penandatangan yang dapat menandatangani dokumen ini.');
        }
    }

    private function ensureCanDelete(KeputusanDirektur $keputusan_direktur): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Anda harus login untuk menghapus dokumen.');
        }

        $level = strtolower((string) ($user->level ?? ''));
        if (in_array($level, ['super admin', 'superadmin'], true)) {
            return;
        }

        $username = (string) ($user->username ?? '');
        if ($username === '') {
            abort(403, 'Anda tidak memiliki izin menghapus dokumen ini.');
        }

        $creatorUsername = (string) ($keputusan_direktur->created_by_username ?? '');
        if ($creatorUsername === '') {
            $creatorUsername = (string) (AuditTrail::query()
                ->where(function ($q) {
                    $q->where('module', 'keputusan_direktur')
                        ->orWhere('table_name', 'keputusan_direktur');
                })
                ->where('record_id', $keputusan_direktur->id)
                ->where('action', 'create')
                ->orderBy('created_at')
                ->value('username') ?? '');
        }

        if ($creatorUsername === '' || $creatorUsername !== $username) {
            abort(403, 'Hanya pembuat dokumen atau super admin yang dapat menghapus.');
        }
    }

    private function inisialFromNama(string $nama): string
    {
        $words = preg_split('/\s+/', trim($nama), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return '';
        }
        $i = '';
        foreach (array_slice($words, 0, 3) as $w) {
            $i .= mb_substr($w, 0, 1, 'UTF-8');
        }
        return mb_strtoupper($i, 'UTF-8');
    }

    private function decodeSignatureImageDataUrl(?string $value, string $field): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        if (! str_starts_with($value, 'data:image')) {
            return null;
        }

        if (! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=\r\n]+)$/', $value, $matches)) {
            throw ValidationException::withMessages([
                $field => 'Format gambar tanda tangan tidak valid. Gunakan PNG base64.',
            ]);
        }

        $decoded = base64_decode($matches[1], true);
        if ($decoded === false) {
            throw ValidationException::withMessages([
                $field => 'Data gambar tanda tangan tidak valid.',
            ]);
        }

        if (strlen($decoded) > 2 * 1024 * 1024) {
            throw ValidationException::withMessages([
                $field => 'Ukuran gambar tanda tangan maksimal 2 MB.',
            ]);
        }

        return $decoded;
    }

    private function jsonError(string $message, int $status = 400, bool $finalized = false)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'finalized' => $finalized,
        ], $status);
    }
}
