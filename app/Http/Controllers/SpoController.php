<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AdaptsSignableDocumentViews;
use App\Models\AuditTrail;
use App\Models\Departemen;
use App\Models\MasterStempel;
use App\Models\Pegawai;
use App\Models\Spo;
use App\Services\SpoNumberService;
use App\Services\SpoPdfService;
use App\Support\DocumentVerificationQr;
use App\Support\DocumentVerificationUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SpoController extends Controller
{
    use AdaptsSignableDocumentViews;

    protected function signableRoutePrefix(): string
    {
        return 'spo';
    }

    protected function signableDocumentLabel(): string
    {
        return 'SPO';
    }

    protected function signableDocumentType(): string
    {
        return 'SPO';
    }

    protected function hasMasaBerlakuFields(): bool
    {
        return false;
    }

    public function index()
    {
        $title = 'SPO Standart Prosedur Operasional';
        $items = Spo::with(['penandatangan', 'uploaderPegawai'])->orderBy('tanggal', 'desc')->paginate(15);
        $departemenMap = $this->loadDepartemenMapForItems($items);

        return view('surat_edaran.index', array_merge($this->signableViewAdapter(), [
            'title' => $title,
            'items' => $items,
            'isSpo' => true,
            'departemenMap' => $departemenMap,
        ]));
    }

    public function create()
    {
        $title = 'Tambah SPO Standart Prosedur Operasional';
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->orderBy('nama')->get();
        $departemenList = Departemen::orderBy('nama')->get();
        $previewNomorSurat = SpoNumberService::previewNext(
            \Carbon\Carbon::parse(old('tanggal', now()->toDateString()))
        );

        return view('surat_edaran.create', array_merge($this->signableViewAdapter(), [
            'title' => $title,
            'pegawai' => $pegawai,
            'isSpo' => true,
            'departemenList' => $departemenList,
            'selectedDepartemenTerkait' => old('dep_terkait', []),
            'previewNomorSurat' => $previewNomorSurat,
        ]));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul_surat' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal' => 'required|date',
            'nik_penandatangan' => 'nullable|string|max:50',
            'dep_terkait' => 'nullable|array',
            'dep_terkait.*' => 'nullable|string|max:50',
            'file_pdf' => 'required|file|mimes:pdf|max:20480',
        ], [], [
            'judul_surat' => 'Judul SPO',
            'deskripsi' => 'Deskripsi Singkat',
            'tanggal' => 'Tanggal',
            'nik_penandatangan' => 'Yang menyetujui',
            'dep_terkait' => 'Departemen Terkait',
            'file_pdf' => 'File PDF',
        ]);

        $path = $request->file('file_pdf')->store('spo', 'public');
        try {
            SpoPdfService::assertPdfParsableFromStoragePath($path);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw ValidationException::withMessages([
                'file_pdf' => $e->getMessage(),
            ]);
        }

        $uploaderUsername = (string) (auth()->user()->username ?? '');
        $uploaderPegawai = $uploaderUsername !== ''
            ? Pegawai::query()->where('nik', $uploaderUsername)->first()
            : null;

        $created = DB::transaction(function () use ($validated, $path, $uploaderUsername, $uploaderPegawai) {
            $payload = SpoNumberService::assignOnCreate([
                'judul_spo' => $validated['judul_surat'],
                'deskripsi_singkat' => $validated['deskripsi'] ?? null,
                'tanggal' => $validated['tanggal'],
                'nik_penandatangan' => $validated['nik_penandatangan'] ?? null,
                'dep_terkait_ids' => $this->normalizeDepartemenIds($validated['dep_terkait'] ?? []),
                'petugas_upload_nik' => $uploaderPegawai?->nik,
                'departemen_upload_id' => $uploaderPegawai?->departemen,
                'file_pdf' => $path,
                'created_by_username' => $uploaderUsername,
            ]);

            return Spo::create($payload);
        });

        AuditTrail::logCreate(
            'spo',
            'spo',
            $created->id,
            $created->toArray(),
            'Membuat SPO baru'
        );

        return redirect()->route('spo.index')->with('success', 'SPO berhasil disimpan.');
    }

    public function show(Spo $spo)
    {
        $spo->load(['penandatangan', 'placements', 'uploaderPegawai']);
        $title = 'Detail SPO Standart Prosedur Operasional';
        $verifyUrl = DocumentVerificationUrl::qrVerifyUrl('spo', $spo);
        $verificationQrUrl = $spo->tanggal_ditandatangani
            ? DocumentVerificationUrl::qrImageUrl('spo', $spo)
            : null;
        $verificationQrDataUri = $spo->tanggal_ditandatangani
            ? DocumentVerificationQr::dataUri($verifyUrl)
            : null;

        return view('surat_edaran.show', array_merge($this->signableViewAdapter(), [
            'title' => $title,
            'surat_edaran' => $spo,
            'verificationQrDataUri' => $verificationQrDataUri,
            'verificationQrUrl' => $verificationQrUrl,
            'verifyUrl' => $verifyUrl,
            'isSpo' => true,
            'departemenMap' => $this->loadDepartemenMap([$spo->departemen_upload_id, ...($spo->dep_terkait_ids ?? [])]),
        ]));
    }

    public function verificationQrPng(Spo $spo)
    {
        if (! $spo->tanggal_ditandatangani) {
            abort(404);
        }
        $verifyUrl = DocumentVerificationUrl::qrVerifyUrl('spo', $spo);
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

    public function edit(Spo $spo)
    {
        $title = 'Edit SPO Standart Prosedur Operasional';
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->orderBy('nama')->get();
        $departemenList = Departemen::orderBy('nama')->get();

        return view('surat_edaran.edit', array_merge($this->signableViewAdapter(), [
            'title' => $title,
            'surat_edaran' => $spo,
            'pegawai' => $pegawai,
            'isSpo' => true,
            'departemenList' => $departemenList,
            'selectedDepartemenTerkait' => old('dep_terkait', $spo->dep_terkait_ids ?? []),
        ]));
    }

    public function update(Request $request, Spo $spo)
    {
        if ($spo->tanggal_ditandatangani) {
            abort(403, 'Dokumen yang sudah sah tidak dapat diubah. Silakan buat dokumen baru.');
        }

        $validated = $request->validate([
            'judul_surat' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal' => 'required|date',
            'nik_penandatangan' => 'nullable|string|max:50',
            'dep_terkait' => 'nullable|array',
            'dep_terkait.*' => 'nullable|string|max:50',
            'file_pdf' => 'nullable|file|mimes:pdf|max:20480',
        ], [], [
            'judul_surat' => 'Judul SPO',
            'deskripsi' => 'Deskripsi Singkat',
            'tanggal' => 'Tanggal',
            'nik_penandatangan' => 'Yang menyetujui',
            'dep_terkait' => 'Departemen Terkait',
            'file_pdf' => 'File PDF',
        ]);

        $updatePayload = [
            'judul_spo' => $validated['judul_surat'],
            'deskripsi_singkat' => $validated['deskripsi'] ?? null,
            'tanggal' => $validated['tanggal'],
            'nik_penandatangan' => $validated['nik_penandatangan'] ?? null,
            'dep_terkait_ids' => $this->normalizeDepartemenIds($validated['dep_terkait'] ?? []),
        ];

        if ($request->hasFile('file_pdf')) {
            $oldPath = $spo->file_pdf;
            $newPath = $request->file('file_pdf')->store('spo', 'public');

            try {
                SpoPdfService::assertPdfParsableFromStoragePath($newPath);
            } catch (\Throwable $e) {
                Storage::disk('public')->delete($newPath);
                throw ValidationException::withMessages([
                    'file_pdf' => $e->getMessage(),
                ]);
            }

            $updatePayload['file_pdf'] = $newPath;
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $spo->update($updatePayload);
        AuditTrail::logUpdate(
            'spo',
            'spo',
            $spo->id,
            [],
            $updatePayload,
            'Mengubah SPO'
        );

        return redirect()->route('spo.index')->with('success', 'SPO berhasil diubah.');
    }

    public function destroy(Spo $spo)
    {
        $this->ensureCanDelete($spo);

        $signatureDetail = is_array($spo->signature_detail) ? $spo->signature_detail : [];
        $signatureImagePath = (string) ($signatureDetail['image_path'] ?? '');

        if ($spo->file_pdf) {
            Storage::disk('public')->delete($spo->file_pdf);
        }
        if ($spo->file_pdf_signed) {
            Storage::disk('public')->delete($spo->file_pdf_signed);
        }
        if ($signatureImagePath !== '' && Storage::disk('public')->exists($signatureImagePath)) {
            Storage::disk('public')->delete($signatureImagePath);
        }

        AuditTrail::logDelete(
            'spo',
            'spo',
            $spo->id,
            $spo->toArray(),
            'Menghapus SPO'
        );

        $spo->delete();
        return redirect()->route('spo.index')->with('success', 'SPO berhasil dihapus.');
    }

    public function streamPdf(Spo $spo)
    {
        $spo->load('placements');

        $pathSudahSigned = $spo->file_pdf && str_ends_with($spo->file_pdf, '_signed.pdf');
        $perluSimpanSigned = $spo->tanggal_ditandatangani
            && $spo->placements->isNotEmpty()
            && ! $pathSudahSigned;

        if ($perluSimpanSigned && $spo->file_pdf && Storage::disk('public')->exists($spo->file_pdf)) {
            try {
                $oldPath = $spo->file_pdf;
                $signedContent = SpoPdfService::generateSignedPdfContent($spo, true);
                $newPath = 'spo/' . $spo->id . '_signed.pdf';
                Storage::disk('public')->put($newPath, $signedContent);
                Storage::disk('public')->delete($oldPath);
                $spo->update(['file_pdf' => $newPath, 'file_pdf_signed' => $newPath]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (! $spo->file_pdf) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        $path = Storage::disk('public')->path($spo->file_pdf);
        if (! file_exists($path)) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $spo->judul_spo) . '.pdf"',
        ]);
    }

    public function tandaTangani(Spo $spo)
    {
        $this->ensureCanSign($spo);
        if ($spo->tanggal_ditandatangani) {
            return redirect()
                ->route('spo.show', $spo)
                ->with('warning', 'Dokumen sudah sah dan tidak dapat dibuka lagi di mode tanda tangan.');
        }

        $spo->load(['penandatangan', 'placements']);
        if (! $spo->nomor_surat) {
            SpoNumberService::assignTo($spo);
            $spo->refresh();
        }
        $title = 'Tanda tangani PDF SPO';
        $pdfUrl = route('spo.streamPdf', $spo);
        $pegawai = $spo->penandatangan;
        $signatureDetail = $spo->signature_detail ?? [];
        if ($pegawai && empty($signatureDetail['nama_lengkap'])) {
            $signatureDetail['nama_lengkap'] = $pegawai->nama ?? '';
            $signatureDetail['inisial'] = $this->inisialFromNama($pegawai->nama ?? '');
        }

        $placementsForJs = $spo->placements->map(function ($p) {
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
        $verifyUrl = DocumentVerificationUrl::qrVerifyUrl('spo', $spo);

        return view('surat_edaran.tanda_tangani', array_merge($this->signableViewAdapter(), [
            'title' => $title,
            'surat_edaran' => $spo,
            'pdfUrl' => $pdfUrl,
            'signatureDetail' => $signatureDetail,
            'placementsForJs' => $placementsForJs,
            'masterTandaTanganList' => $masterTandaTanganList,
            'masterStempel' => $masterStempel,
            'verifyUrl' => $verifyUrl,
            'isSpo' => true,
        ]));
    }

    public function saveSignatureAndPlacements(Request $request, Spo $spo)
    {
        $this->ensureCanSign($spo);
        if ($spo->tanggal_ditandatangani) {
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

        $existingSignatureDetail = is_array($spo->signature_detail) ? $spo->signature_detail : [];
        $existingImagePath = (string) ($existingSignatureDetail['image_path'] ?? '');
        $existingImageUrl = (string) ($existingSignatureDetail['image_url'] ?? '');
        $signatureType = $validated['signature_type'] ?? 'text';
        $croppedImageBinary = $this->decodeSignatureImageDataUrl($validated['cropped_signature_image'] ?? null, 'cropped_signature_image');
        $signatureImageBinary = $this->decodeSignatureImageDataUrl($validated['signature_image_url'] ?? null, 'signature_image_url');

        $imageUrl = $validated['signature_image_url'] ?? $existingImageUrl;
        $imagePath = $existingImagePath !== '' ? $existingImagePath : null;
        $imagePathToDelete = null;

        if ($signatureType === 'image') {
            if ($croppedImageBinary !== null) {
                $imagePath = 'tanda_tangan/spo_' . $spo->id . '_' . time() . '.png';
                Storage::disk('public')->put($imagePath, $croppedImageBinary);
                $imageUrl = Storage::disk('public')->url($imagePath);
                if ($existingImagePath !== '' && $existingImagePath !== $imagePath) {
                    $imagePathToDelete = $existingImagePath;
                }
            } elseif ($signatureImageBinary !== null) {
                $imagePath = 'tanda_tangan/spo_' . $spo->id . '_' . time() . '.png';
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

        $spo->update([
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

        $spo->placements()->delete();
        if (! empty($validated['placements'])) {
            foreach ($validated['placements'] as $i => $p) {
                $width = isset($p['width']) ? (float) $p['width'] : null;
                $height = isset($p['height']) ? (float) $p['height'] : null;
                if (($p['field_type'] ?? '') === 'qr_verifikasi') {
                    [$width, $height] = SpoPdfService::normalizeQrDimensionsMm(
                        $width ?? 0,
                        $height ?? 0
                    );
                }

                $placementValue = $p['value'] ?? null;
                if (($p['field_type'] ?? '') === 'nomor_surat') {
                    $placementValue = $spo->nomor_surat;
                }

                $spo->placements()->create([
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
                'spo',
                'Menyimpan draft posisi tanda tangan SPO',
                $spo->id,
                null,
                ['placements_count' => count($validated['placements'] ?? [])],
                'spo'
            );

            return response()->json([
                'success' => true,
                'message' => 'Posisi tanda tangan berhasil disimpan sebagai draft.',
                'finalized' => false,
            ]);
        }

        if (! $spo->nomor_surat) {
            SpoNumberService::assignTo($spo);
            $spo->refresh();
        }

        $oldPath = $spo->file_pdf;
        $sourceRelative = SpoPdfService::sourcePdfRelativePath((int) $spo->id);
        $newPath = 'spo/' . $spo->id . '_signed.pdf';

        if (! Storage::disk('public')->exists($sourceRelative)) {
            if ($oldPath && ! str_ends_with($oldPath, '_signed.pdf') && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->copy($oldPath, $sourceRelative);
            }
        }

        try {
            $spo->load('placements');
            $signedContent = SpoPdfService::generateSignedPdfContent($spo, true);
            Storage::disk('public')->put($newPath, $signedContent);

            DB::transaction(function () use ($spo, $newPath) {
                $spo->update([
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
            'spo',
            'Mengesahkan dokumen SPO dan menyimpan PDF bertanda tangan',
            $spo->id,
            null,
            ['finalized' => true, 'placements_count' => count($validated['placements'] ?? [])],
            'spo'
        );

        return response()->json([
            'success' => true,
            'message' => 'Tanda tangan dan posisi disimpan. Dokumen SPO sah dan PDF bertanda tangan tersimpan di sistem.',
            'finalized' => true,
        ]);
    }

    public function generateSignedPdf(Spo $spo)
    {
        $spo->load(['penandatangan', 'placements']);
        $pdfContent = SpoPdfService::generateSignedPdfContent($spo, (bool) $spo->tanggal_ditandatangani);
        if ($spo->tanggal_ditandatangani && $spo->file_pdf) {
            Storage::disk('public')->put($spo->file_pdf, $pdfContent);
        }
        $filename = 'spo_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $spo->judul_spo) . '_signed.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function verifyAuthenticity(Spo $spo)
    {
        $spo->load('penandatangan');

        $baseAuditQuery = AuditTrail::query()
            ->where(function ($q) {
                $q->where('module', 'spo')
                    ->orWhere('table_name', 'spo');
            })
            ->where('record_id', $spo->id);

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

        $title = 'Verifikasi Keabsahan SPO';
        return view('surat_edaran.verifikasi', array_merge($this->signableViewAdapter(), [
            'title' => $title,
            'surat_edaran' => $spo,
            'auditTrails' => $auditTrails,
            'createdLog' => $createdLog,
            'uploadLog' => $uploadLog,
            'userDisplayByUsername' => $userDisplayByUsername,
            'isSpo' => true,
        ]));
    }

    private function ensureCanSign(Spo $spo): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Anda harus login untuk menandatangani dokumen.');
        }

        $level = strtolower((string) ($user->level ?? ''));
        if (in_array($level, ['admin', 'super admin', 'superadmin'], true)) {
            return;
        }

        $userNik = (string) ($user->username ?? '');
        $targetNik = (string) ($spo->nik_penandatangan ?? '');

        if (empty($targetNik)) {
            abort(403, 'Dokumen ini belum memiliki penandatangan yang ditentukan.');
        }

        if ($userNik !== $targetNik) {
            abort(403, 'Hanya pegawai yang dipilih sebagai penandatangan yang dapat menandatangani dokumen ini.');
        }
    }

    private function ensureCanDelete(Spo $spo): void
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

        $creatorUsername = (string) ($spo->created_by_username ?? '');
        if ($creatorUsername === '') {
            $creatorUsername = (string) (AuditTrail::query()
                ->where(function ($q) {
                    $q->where('module', 'spo')
                        ->orWhere('table_name', 'spo');
                })
                ->where('record_id', $spo->id)
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

        $inisial = '';
        foreach (array_slice($words, 0, 3) as $word) {
            $inisial .= mb_substr($word, 0, 1, 'UTF-8');
        }

        return mb_strtoupper($inisial, 'UTF-8');
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

    private function normalizeDepartemenIds(array $departemenIds): array
    {
        return collect($departemenIds)
            ->filter(fn ($id) => is_string($id) && trim($id) !== '')
            ->map(fn ($id) => trim($id))
            ->unique()
            ->values()
            ->all();
    }

    private function loadDepartemenMapForItems($items): array
    {
        $ids = [];
        foreach ($items as $item) {
            $ids[] = $item->departemen_upload_id;
            $ids = array_merge($ids, $item->dep_terkait_ids ?? []);
        }

        return $this->loadDepartemenMap($ids);
    }

    private function loadDepartemenMap(array $ids): array
    {
        $normalizedIds = collect($ids)
            ->filter(fn ($id) => is_string($id) && trim($id) !== '')
            ->map(fn ($id) => trim($id))
            ->unique()
            ->values();

        if ($normalizedIds->isEmpty()) {
            return [];
        }

        return Departemen::query()
            ->whereIn('dep_id', $normalizedIds->all())
            ->get(['dep_id', 'nama'])
            ->mapWithKeys(fn ($dep) => [(string) $dep->dep_id => (string) $dep->nama])
            ->toArray();
    }
}
