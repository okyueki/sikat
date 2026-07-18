<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AdaptsSignableDocumentViews;
use App\Models\MasterStempel;
use App\Models\MasterTandaTangan;
use App\Models\PeraturanDirektur;
use App\Models\PeraturanDirekturPlacement;
use App\Models\AuditTrail;
use App\Models\Pegawai;
use App\Services\PeraturanDirekturNumberService;
use App\Services\PeraturanDirekturPdfService;
use App\Support\DocumentVerificationQr;
use App\Support\DocumentVerificationUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Peraturan Direktur – Judul, Nomor, Deskripsi, Tanggal, Yang menyetujui, File PDF.
 * Halaman tanda tangani: drag-drop kotak isian (tanda tangan teks/Latin, inisial, nama, tanggal, teks).
 */
class PeraturanDirekturController extends Controller
{
    use AdaptsSignableDocumentViews;

    protected function signableRoutePrefix(): string
    {
        return 'peraturan_direktur';
    }

    protected function signableDocumentLabel(): string
    {
        return 'Peraturan Direktur';
    }

    protected function signableDocumentType(): string
    {
        return 'PER';
    }

    protected function hasMasaBerlakuFields(): bool
    {
        return true;
    }

    public function index()
    {
        $title = 'Peraturan Direktur';
        $items = PeraturanDirektur::with('penandatangan')->orderBy('tanggal', 'desc')->paginate(15);

        return view('surat_edaran.index', array_merge($this->signableViewAdapter(), compact('title', 'items')));
    }

    public function create()
    {
        $title = 'Tambah Peraturan Direktur';
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->orderBy('nama')->get();
        $previewNomorSurat = PeraturanDirekturNumberService::previewNext(
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

        $path = $request->file('file_pdf')->store('peraturan_direktur', 'public');
        $validated['file_pdf'] = $path;
        $validated['created_by_username'] = (string) (auth()->user()->username ?? '');
        try {
            PeraturanDirekturPdfService::assertPdfParsableFromStoragePath($path);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw ValidationException::withMessages([
                'file_pdf' => $e->getMessage(),
            ]);
        }

        $created = DB::transaction(function () use ($validated) {
            $payload = PeraturanDirekturNumberService::assignOnCreate($validated);

            return PeraturanDirektur::create($payload);
        });
        AuditTrail::logCreate(
            'peraturan_direktur',
            'peraturan_direktur',
            $created->id,
            $created->toArray(),
            'Membuat peraturan direktur baru'
        );

        return redirect()->route('peraturan_direktur.index')->with('success', 'Peraturan Direktur berhasil disimpan.');
    }

    public function show(PeraturanDirektur $peraturan_direktur)
    {
        $peraturan_direktur->load(['penandatangan', 'placements']);
        $title = 'Detail Peraturan Direktur';
        $surat_edaran = $peraturan_direktur;
        $verifyUrl = DocumentVerificationUrl::qrVerifyUrl('peraturan_direktur', $peraturan_direktur);
        $verificationQrUrl = $peraturan_direktur->tanggal_ditandatangani
            ? DocumentVerificationUrl::qrImageUrl('peraturan_direktur', $peraturan_direktur)
            : null;
        $verificationQrDataUri = $peraturan_direktur->tanggal_ditandatangani
            ? DocumentVerificationQr::dataUri($verifyUrl)
            : null;

        return view('surat_edaran.show', array_merge($this->signableViewAdapter(), compact('title', 'surat_edaran', 'verificationQrDataUri', 'verificationQrUrl', 'verifyUrl')));
    }

    /**
     * Gambar QR verifikasi (PNG) — dipakai halaman detail agar tidak bergantung data-URI panjang / CSP.
     * Konten sama dengan QR yang di-embed ke PDF (URL halaman verifikasi).
     */
    public function verificationQrPng(PeraturanDirektur $peraturan_direktur)
    {
        $verifyUrl = DocumentVerificationUrl::qrVerifyUrl('peraturan_direktur', $peraturan_direktur);
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

    public function edit(PeraturanDirektur $peraturan_direktur)
    {
        $title = 'Edit Peraturan Direktur';
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->orderBy('nama')->get();
        $surat_edaran = $peraturan_direktur;

        return view('surat_edaran.edit', array_merge($this->signableViewAdapter(), compact('title', 'surat_edaran', 'pegawai')));
    }

    public function update(Request $request, PeraturanDirektur $peraturan_direktur)
    {
        if ($peraturan_direktur->tanggal_ditandatangani) {
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
            $oldPath = $peraturan_direktur->file_pdf;
            $newPath = $request->file('file_pdf')->store('peraturan_direktur', 'public');

            try {
                PeraturanDirekturPdfService::assertPdfParsableFromStoragePath($newPath);
            } catch (\Throwable $e) {
                Storage::disk('public')->delete($newPath);
                throw ValidationException::withMessages([
                    'file_pdf' => $e->getMessage(),
                ]);
            }

            $validated['file_pdf'] = $newPath;
            if ($peraturan_direktur->file_pdf) {
                Storage::disk('public')->delete($oldPath);
            }
        } else {
            unset($validated['file_pdf']);
        }

        $peraturan_direktur->update($validated);
        AuditTrail::logUpdate(
            'peraturan_direktur',
            'peraturan_direktur',
            $peraturan_direktur->id,
            [],
            $validated,
            'Mengubah peraturan direktur'
        );

        return redirect()->route('peraturan_direktur.index')->with('success', 'Peraturan Direktur berhasil diubah.');
    }

    public function destroy(PeraturanDirektur $peraturan_direktur)
    {
        $this->ensureCanDelete($peraturan_direktur);

        $signatureDetail = is_array($peraturan_direktur->signature_detail) ? $peraturan_direktur->signature_detail : [];
        $signatureImagePath = (string) ($signatureDetail['image_path'] ?? '');

        if ($peraturan_direktur->file_pdf) {
            Storage::disk('public')->delete($peraturan_direktur->file_pdf);
        }
        if ($peraturan_direktur->file_pdf_signed) {
            Storage::disk('public')->delete($peraturan_direktur->file_pdf_signed);
        }
        if ($signatureImagePath !== '' && Storage::disk('public')->exists($signatureImagePath)) {
            Storage::disk('public')->delete($signatureImagePath);
        }
        AuditTrail::logDelete(
            'peraturan_direktur',
            'peraturan_direktur',
            $peraturan_direktur->id,
            $peraturan_direktur->toArray(),
            'Menghapus peraturan direktur'
        );
        $peraturan_direktur->delete();
        return redirect()->route('peraturan_direktur.index')->with('success', 'Peraturan Direktur berhasil dihapus.');
    }

    /**
     * Stream PDF untuk ditampilkan di viewer (PDF.js).
     * Jika dokumen sudah sah (tanggal_ditandatangani) tetapi file yang tersimpan belum versi bertanda tangan,
     * generate dan simpan PDF bertanda tangan dulu, hapus file lama, lalu stream.
     */
    public function streamPdf(PeraturanDirektur $peraturan_direktur)
    {
        $peraturan_direktur->load('placements');

        // Dokumen sudah ditandatangani tapi file yang tersimpan belum versi bertanda tangan (data lama)
        $pathSudahSigned = $peraturan_direktur->file_pdf && str_ends_with($peraturan_direktur->file_pdf, '_signed.pdf');
        $perluSimpanSigned = $peraturan_direktur->tanggal_ditandatangani
            && $peraturan_direktur->placements->isNotEmpty()
            && ! $pathSudahSigned;

        if ($perluSimpanSigned && $peraturan_direktur->file_pdf && Storage::disk('public')->exists($peraturan_direktur->file_pdf)) {
            try {
                $oldPath = $peraturan_direktur->file_pdf;
                $signedContent = PeraturanDirekturPdfService::generateSignedPdfContent($peraturan_direktur, true);
                $newPath = 'peraturan_direktur/' . $peraturan_direktur->id . '_signed.pdf';
                Storage::disk('public')->put($newPath, $signedContent);
                Storage::disk('public')->delete($oldPath);
                $peraturan_direktur->update(['file_pdf' => $newPath, 'file_pdf_signed' => $newPath]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (! $peraturan_direktur->file_pdf) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        $path = Storage::disk('public')->path($peraturan_direktur->file_pdf);
        if (! file_exists($path)) {
            abort(404, 'File PDF tidak ditemukan.');
        }
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $peraturan_direktur->judul_surat) . '.pdf"',
        ]);
    }

    /**
     * Halaman tanda tangani: PDF viewer + sidebar (detail tanda tangan + kotak isian drag-drop).
     */
    public function tandaTangani(PeraturanDirektur $peraturan_direktur)
    {
        $this->ensureCanSign($peraturan_direktur);
        if ($peraturan_direktur->tanggal_ditandatangani) {
            return redirect()
                ->route('peraturan_direktur.show', $peraturan_direktur)
                ->with('warning', 'Dokumen sudah sah dan tidak dapat dibuka lagi di mode tanda tangan.');
        }

        $peraturan_direktur->load(['penandatangan', 'placements']);
        if (! $peraturan_direktur->nomor_surat) {
            PeraturanDirekturNumberService::assignTo($peraturan_direktur);
            $peraturan_direktur->refresh();
        }
        $title = 'Tanda tangani PDF';
        $pdfUrl = route('peraturan_direktur.streamPdf', $peraturan_direktur);
        $pegawai = $peraturan_direktur->penandatangan;
        $signatureDetail = $peraturan_direktur->signature_detail ?? [];
        if ($pegawai && empty($signatureDetail['nama_lengkap'])) {
            $signatureDetail['nama_lengkap'] = $pegawai->nama ?? '';
            $signatureDetail['inisial'] = $this->inisialFromNama($pegawai->nama ?? '');
        }
        $placementsForJs = $peraturan_direktur->placements->map(function ($p) {
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

        $verifyUrl = DocumentVerificationUrl::qrVerifyUrl('peraturan_direktur', $peraturan_direktur);
        $surat_edaran = $peraturan_direktur;

        return view('surat_edaran.tanda_tangani', array_merge($this->signableViewAdapter(), compact('title', 'surat_edaran', 'pdfUrl', 'signatureDetail', 'placementsForJs', 'masterTandaTanganList', 'masterStempel', 'verifyUrl')));
    }

    /**
     * Simpan detail tanda tangan (nama, inisial, font, warna) dan posisi kotak isian (placements).
     */
    public function saveSignatureAndPlacements(Request $request, PeraturanDirektur $peraturan_direktur)
    {
        $this->ensureCanSign($peraturan_direktur);

        if ($peraturan_direktur->tanggal_ditandatangani) {
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
        $existingSignatureDetail = is_array($peraturan_direktur->signature_detail) ? $peraturan_direktur->signature_detail : [];
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
                $imagePath = 'tanda_tangan/per_' . $peraturan_direktur->id . '_' . time() . '.png';
                Storage::disk('public')->put($imagePath, $croppedImageBinary);
                $imageUrl = Storage::disk('public')->url($imagePath);
                if ($existingImagePath !== '' && $existingImagePath !== $imagePath) {
                    $imagePathToDelete = $existingImagePath;
                }
            }
            // Fallback to regular image if no cropped image
            elseif ($signatureImageBinary !== null) {
                $imagePath = 'tanda_tangan/per_' . $peraturan_direktur->id . '_' . time() . '.png';
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

        $peraturan_direktur->update([
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

        $peraturan_direktur->placements()->delete();
        if (! empty($validated['placements'])) {
            foreach ($validated['placements'] as $i => $p) {
                $width = isset($p['width']) ? (float) $p['width'] : null;
                $height = isset($p['height']) ? (float) $p['height'] : null;
                if (($p['field_type'] ?? '') === 'qr_verifikasi') {
                    [$width, $height] = PeraturanDirekturPdfService::normalizeQrDimensionsMm(
                        $width ?? 0,
                        $height ?? 0
                    );
                }

                $placementValue = $p['value'] ?? null;
                if (($p['field_type'] ?? '') === 'nomor_surat') {
                    $placementValue = $peraturan_direktur->nomor_surat;
                }

                $peraturan_direktur->placements()->create([
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
                'peraturan_direktur',
                'Menyimpan draft posisi tanda tangan peraturan direktur',
                $peraturan_direktur->id,
                null,
                ['placements_count' => count($validated['placements'] ?? [])],
                'peraturan_direktur'
            );
            return response()->json([
                'success' => true,
                'message' => 'Posisi tanda tangan berhasil disimpan sebagai draft.',
                'finalized' => false,
            ]);
        }

        // Finalisasi: sahkan dokumen dan simpan PDF bertanda tangan
        if (! $peraturan_direktur->nomor_surat) {
            PeraturanDirekturNumberService::assignTo($peraturan_direktur);
            $peraturan_direktur->refresh();
        }

        $oldPath = $peraturan_direktur->file_pdf;
        $sourceRelative = PeraturanDirekturPdfService::sourcePdfRelativePath((int) $peraturan_direktur->id);
        $newPath = 'peraturan_direktur/' . $peraturan_direktur->id . '_signed.pdf';

        if (! Storage::disk('public')->exists($sourceRelative)) {
            if ($oldPath && ! str_ends_with($oldPath, '_signed.pdf') && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->copy($oldPath, $sourceRelative);
            }
        }

        try {
            $peraturan_direktur->load('placements');
            $signedContent = PeraturanDirekturPdfService::generateSignedPdfContent($peraturan_direktur, true);
            Storage::disk('public')->put($newPath, $signedContent);

            DB::transaction(function () use ($peraturan_direktur, $newPath) {
                $peraturan_direktur->update([
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
            'peraturan_direktur',
            'Mengesahkan dokumen peraturan direktur dan menyimpan PDF bertanda tangan',
            $peraturan_direktur->id,
            null,
            ['finalized' => true, 'placements_count' => count($validated['placements'] ?? [])],
            'peraturan_direktur'
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
    public function generateSignedPdf(PeraturanDirektur $peraturan_direktur)
    {
        $peraturan_direktur->load(['penandatangan', 'placements']);
        $pdfContent = PeraturanDirekturPdfService::generateSignedPdfContent($peraturan_direktur, (bool) $peraturan_direktur->tanggal_ditandatangani);
        if ($peraturan_direktur->tanggal_ditandatangani && $peraturan_direktur->file_pdf) {
            Storage::disk('public')->put($peraturan_direktur->file_pdf, $pdfContent);
        }
        $filename = 'peraturan_direktur_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $peraturan_direktur->judul_surat) . '_signed.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function verifyAuthenticity(PeraturanDirektur $peraturan_direktur)
    {
        $peraturan_direktur->load('penandatangan');

        $baseAuditQuery = AuditTrail::query()
            ->where(function ($q) use ($peraturan_direktur) {
                $q->where('module', 'peraturan_direktur')
                    ->orWhere('table_name', 'peraturan_direktur');
            })
            ->where('record_id', $peraturan_direktur->id);

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

        $title = 'Verifikasi Keabsahan Peraturan Direktur';
        $surat_edaran = $peraturan_direktur;

        return view('surat_edaran.verifikasi', array_merge($this->signableViewAdapter(), compact('title', 'surat_edaran', 'auditTrails', 'createdLog', 'uploadLog', 'userDisplayByUsername')));
    }

    private function ensureCanSign(PeraturanDirektur $peraturan_direktur): void
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
        $targetNik = (string) ($peraturan_direktur->nik_penandatangan ?? '');

        if (empty($targetNik)) {
            abort(403, 'Dokumen ini belum memiliki penandatangan yang ditentukan.');
        }

        if ($userNik !== $targetNik) {
            abort(403, 'Hanya pegawai yang dipilih sebagai penandatangan yang dapat menandatangani dokumen ini.');
        }
    }

    private function ensureCanDelete(PeraturanDirektur $peraturan_direktur): void
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

        $creatorUsername = (string) ($peraturan_direktur->created_by_username ?? '');
        if ($creatorUsername === '') {
            $creatorUsername = (string) (AuditTrail::query()
                ->where(function ($q) {
                    $q->where('module', 'peraturan_direktur')
                        ->orWhere('table_name', 'peraturan_direktur');
                })
                ->where('record_id', $peraturan_direktur->id)
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
