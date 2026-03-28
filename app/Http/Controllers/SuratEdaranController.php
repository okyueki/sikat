<?php

namespace App\Http\Controllers;

use App\Models\MasterStempel;
use App\Models\MasterTandaTangan;
use App\Models\SuratEdaran;
use App\Models\SuratEdaranPlacement;
use App\Models\AuditTrail;
use App\Models\Pegawai;
use App\Services\SuratEdaranPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Surat Edaran – Judul, Nomor, Deskripsi, Tanggal, Yang menyetujui, File PDF.
 * Halaman tanda tangani: drag-drop kotak isian (tanda tangan teks/Latin, inisial, nama, tanggal, teks).
 */
class SuratEdaranController extends Controller
{
    public function index()
    {
        $title = 'Surat Edaran';
        $items = SuratEdaran::with('penandatangan')->orderBy('tanggal', 'desc')->paginate(15);
        return view('surat_edaran.index', compact('title', 'items'));
    }

    public function create()
    {
        $title = 'Tambah Surat Edaran';
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->orderBy('nama')->get();
        return view('surat_edaran.create', compact('title', 'pegawai'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul_surat' => 'required|string|max:255',
            'nomor_surat' => 'nullable|string|max:100',
            'deskripsi' => 'nullable|string',
            'tanggal' => 'required|date',
            'nik_penandatangan' => 'nullable|string|max:50',
            'file_pdf' => 'required|file|mimes:pdf|max:20480',
        ], [], [
            'judul_surat' => 'Judul Surat',
            'nomor_surat' => 'Nomor Surat',
            'deskripsi' => 'Deskripsi',
            'tanggal' => 'Tanggal',
            'nik_penandatangan' => 'Yang menyetujui',
            'file_pdf' => 'File PDF',
        ]);

        $path = $request->file('file_pdf')->store('surat_edaran', 'public');
        $validated['file_pdf'] = $path;
        $validated['created_by_username'] = (string) (auth()->user()->username ?? '');

        $created = SuratEdaran::create($validated);
        AuditTrail::logCreate(
            'surat_edaran',
            'surat_edaran',
            $created->id,
            $created->toArray(),
            'Membuat surat edaran baru'
        );

        return redirect()->route('surat_edaran.index')->with('success', 'Surat Edaran berhasil disimpan.');
    }

    public function show(SuratEdaran $surat_edaran)
    {
        $surat_edaran->load(['penandatangan', 'placements']);
        $title = 'Detail Surat Edaran';
        $verifyUrl = route('surat_edaran.verify', $surat_edaran);
        $verificationQrDataUri = null;
        if ($surat_edaran->tanggal_ditandatangani) {
            $qr = QrCode::format('png')->size(220)->margin(1)->generate($verifyUrl);
            $verificationQrDataUri = 'data:image/png;base64,' . base64_encode($qr);
        }

        return view('surat_edaran.show', compact('title', 'surat_edaran', 'verificationQrDataUri', 'verifyUrl'));
    }

    public function edit(SuratEdaran $surat_edaran)
    {
        $title = 'Edit Surat Edaran';
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->orderBy('nama')->get();
        return view('surat_edaran.edit', compact('title', 'surat_edaran', 'pegawai'));
    }

    public function update(Request $request, SuratEdaran $surat_edaran)
    {
        $validated = $request->validate([
            'judul_surat' => 'required|string|max:255',
            'nomor_surat' => 'nullable|string|max:100',
            'deskripsi' => 'nullable|string',
            'tanggal' => 'required|date',
            'nik_penandatangan' => 'nullable|string|max:50',
            'file_pdf' => 'nullable|file|mimes:pdf|max:20480',
        ], [], [
            'judul_surat' => 'Judul Surat',
            'nomor_surat' => 'Nomor Surat',
            'deskripsi' => 'Deskripsi',
            'tanggal' => 'Tanggal',
            'nik_penandatangan' => 'Yang menyetujui',
            'file_pdf' => 'File PDF',
        ]);

        if ($request->hasFile('file_pdf')) {
            if ($surat_edaran->file_pdf) {
                Storage::disk('public')->delete($surat_edaran->file_pdf);
            }
            $validated['file_pdf'] = $request->file('file_pdf')->store('surat_edaran', 'public');
        } else {
            unset($validated['file_pdf']);
        }

        $surat_edaran->update($validated);
        AuditTrail::logUpdate(
            'surat_edaran',
            'surat_edaran',
            $surat_edaran->id,
            [],
            $validated,
            'Mengubah surat edaran'
        );

        return redirect()->route('surat_edaran.index')->with('success', 'Surat Edaran berhasil diubah.');
    }

    public function destroy(SuratEdaran $surat_edaran)
    {
        $this->ensureCanDelete($surat_edaran);

        if ($surat_edaran->file_pdf) {
            Storage::disk('public')->delete($surat_edaran->file_pdf);
        }
        if ($surat_edaran->file_pdf_signed) {
            Storage::disk('public')->delete($surat_edaran->file_pdf_signed);
        }
        AuditTrail::logDelete(
            'surat_edaran',
            'surat_edaran',
            $surat_edaran->id,
            $surat_edaran->toArray(),
            'Menghapus surat edaran'
        );
        $surat_edaran->delete();
        return redirect()->route('surat_edaran.index')->with('success', 'Surat Edaran berhasil dihapus.');
    }

    /**
     * Stream PDF untuk ditampilkan di viewer (PDF.js).
     * Jika dokumen sudah sah (tanggal_ditandatangani) tetapi file yang tersimpan belum versi bertanda tangan,
     * generate dan simpan PDF bertanda tangan dulu, hapus file lama, lalu stream.
     */
    public function streamPdf(SuratEdaran $surat_edaran)
    {
        $surat_edaran->load('placements');

        // Dokumen sudah ditandatangani tapi file yang tersimpan belum versi bertanda tangan (data lama)
        $pathSudahSigned = $surat_edaran->file_pdf && str_ends_with($surat_edaran->file_pdf, '_signed.pdf');
        $perluSimpanSigned = $surat_edaran->tanggal_ditandatangani
            && $surat_edaran->placements->isNotEmpty()
            && ! $pathSudahSigned;

        if ($perluSimpanSigned && $surat_edaran->file_pdf && Storage::disk('public')->exists($surat_edaran->file_pdf)) {
            try {
                $oldPath = $surat_edaran->file_pdf;
                $signedContent = SuratEdaranPdfService::generateSignedPdfContent($surat_edaran);
                $newPath = 'surat_edaran/' . $surat_edaran->id . '_signed.pdf';
                Storage::disk('public')->put($newPath, $signedContent);
                Storage::disk('public')->delete($oldPath);
                $surat_edaran->update(['file_pdf' => $newPath, 'file_pdf_signed' => $newPath]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $path = Storage::disk('public')->path($surat_edaran->file_pdf);
        if (! $surat_edaran->file_pdf || ! file_exists($path)) {
            abort(404, 'File PDF tidak ditemukan.');
        }
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $surat_edaran->judul_surat) . '.pdf"',
        ]);
    }

    /**
     * Halaman tanda tangani: PDF viewer + sidebar (detail tanda tangan + kotak isian drag-drop).
     */
    public function tandaTangani(SuratEdaran $surat_edaran)
    {
        $this->ensureCanSign($surat_edaran);

        $surat_edaran->load(['penandatangan', 'placements']);
        $title = 'Tanda tangani PDF';
        $pdfUrl = route('surat_edaran.streamPdf', $surat_edaran);
        $pegawai = $surat_edaran->penandatangan;
        $signatureDetail = $surat_edaran->signature_detail ?? [];
        if ($pegawai && empty($signatureDetail['nama_lengkap'])) {
            $signatureDetail['nama_lengkap'] = $pegawai->nama ?? '';
            $signatureDetail['inisial'] = $this->inisialFromNama($pegawai->nama ?? '');
        }
        $placementsForJs = $surat_edaran->placements->map(function ($p) {
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

        return view('surat_edaran.tanda_tangani', compact('title', 'surat_edaran', 'pdfUrl', 'signatureDetail', 'placementsForJs', 'masterTandaTanganList', 'masterStempel'));
    }

    /**
     * Simpan detail tanda tangan (nama, inisial, font, warna) dan posisi kotak isian (placements).
     */
    public function saveSignatureAndPlacements(Request $request, SuratEdaran $surat_edaran)
    {
        $this->ensureCanSign($surat_edaran);

        $finalize = filter_var($request->input('finalize', false), FILTER_VALIDATE_BOOLEAN);

        $validated = $request->validate([
            'nama_lengkap' => 'nullable|string|max:255',
            'inisial' => 'nullable|string|max:20',
            'font_style' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'signature_type' => 'nullable|string|in:text,image',
            'signature_image_url' => 'nullable|string',
            'finalize' => 'nullable|boolean',
            'placements' => 'nullable|array',
            'placements.*.field_type' => 'required|string|in:signature,inisial,nama,tanggal,teks,stempel',
            'placements.*.page' => 'required|integer|min:1',
            'placements.*.x' => 'required|numeric',
            'placements.*.y' => 'required|numeric',
            'placements.*.width' => 'nullable|numeric',
            'placements.*.height' => 'nullable|numeric',
            'placements.*.value' => 'nullable|string',
            'placements.*.options' => 'nullable|array',
        ]);

        // Handle image URL - prioritize cropped image over regular image
        $imageUrl = $validated['signature_image_url'] ?? '';
        $imagePath = null;
        if (($validated['signature_type'] ?? 'text') === 'image') {
            // Check for cropped image first
            $croppedImage = $validated['cropped_signature_image'] ?? '';
            if ($croppedImage && str_starts_with($croppedImage, 'data:image')) {
                // Decode base64 cropped image and save
                $imageData = explode(',', $croppedImage);
                if (count($imageData) === 2) {
                    $decoded = base64_decode($imageData[1]);
                    $imagePath = 'tanda_tangan/surat_' . $surat_edaran->id . '_' . time() . '.png';
                    Storage::disk('public')->put($imagePath, $decoded);
                    $imageUrl = Storage::disk('public')->url($imagePath);
                }
            }
            // Fallback to regular image if no cropped image
            elseif ($imageUrl && str_starts_with($imageUrl, 'data:image')) {
                $imageData = explode(',', $imageUrl);
                if (count($imageData) === 2) {
                    $decoded = base64_decode($imageData[1]);
                    $imagePath = 'tanda_tangan/surat_' . $surat_edaran->id . '_' . time() . '.png';
                    Storage::disk('public')->put($imagePath, $decoded);
                    $imageUrl = Storage::disk('public')->url($imagePath);
                }
            }
        }

        $surat_edaran->update([
            'signature_detail' => [
                'nama_lengkap' => $validated['nama_lengkap'] ?? '',
                'inisial' => $validated['inisial'] ?? '',
                'font_style' => $validated['font_style'] ?? '1',
                'color' => $validated['color'] ?? '#000000',
                'type' => $validated['signature_type'] ?? 'text',
                'image_url' => $imageUrl,
                'image_path' => $imagePath,
            ],
        ]);

        $surat_edaran->placements()->delete();
        if (! empty($validated['placements'])) {
            foreach ($validated['placements'] as $i => $p) {
                $surat_edaran->placements()->create([
                    'field_type' => $p['field_type'],
                    'page' => (int) $p['page'],
                    'x' => (float) $p['x'],
                    'y' => (float) $p['y'],
                    'width' => isset($p['width']) ? (float) $p['width'] : null,
                    'height' => isset($p['height']) ? (float) $p['height'] : null,
                    'value' => $p['value'] ?? null,
                    'options' => $p['options'] ?? null,
                    'sort_order' => $i,
                ]);
            }
        }

        if (! $finalize) {
            AuditTrail::log(
                'update',
                'surat_edaran',
                'Menyimpan draft posisi tanda tangan surat edaran',
                $surat_edaran->id,
                null,
                ['placements_count' => count($validated['placements'] ?? [])],
                'surat_edaran'
            );
            return response()->json([
                'success' => true,
                'message' => 'Posisi tanda tangan berhasil disimpan sebagai draft.',
                'finalized' => false,
            ]);
        }

        // Finalisasi: sahkan dokumen dan simpan PDF bertanda tangan
        $surat_edaran->update(['tanggal_ditandatangani' => now()]);
        $surat_edaran->load('placements');
        $oldPath = $surat_edaran->file_pdf;
        try {
            $signedContent = SuratEdaranPdfService::generateSignedPdfContent($surat_edaran);
            $newPath = 'surat_edaran/' . $surat_edaran->id . '_signed.pdf';
            Storage::disk('public')->put($newPath, $signedContent);

            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            $surat_edaran->update([
                'file_pdf' => $newPath,
                'file_pdf_signed' => $newPath,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => true,
                'message' => 'Dokumen disahkan, tetapi gagal menyimpan file PDF bertanda tangan: ' . $e->getMessage(),
                'finalized' => true,
            ]);
        }

        AuditTrail::log(
            'update',
            'surat_edaran',
            'Mengesahkan dokumen surat edaran dan menyimpan PDF bertanda tangan',
            $surat_edaran->id,
            null,
            ['finalized' => true, 'placements_count' => count($validated['placements'] ?? [])],
            'surat_edaran'
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
    public function generateSignedPdf(SuratEdaran $surat_edaran)
    {
        $surat_edaran->load(['penandatangan', 'placements']);
        return SuratEdaranPdfService::generateSignedPdf($surat_edaran);
    }

    public function verifyAuthenticity(SuratEdaran $surat_edaran)
    {
        $surat_edaran->load('penandatangan');

        $baseAuditQuery = AuditTrail::query()
            ->where(function ($q) use ($surat_edaran) {
                $q->where('module', 'surat_edaran')
                    ->orWhere('table_name', 'surat_edaran');
            })
            ->where('record_id', $surat_edaran->id);

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

        $title = 'Verifikasi Keabsahan Surat Edaran';
        return view('surat_edaran.verifikasi', compact('title', 'surat_edaran', 'auditTrails', 'createdLog', 'uploadLog', 'userDisplayByUsername'));
    }

    private function ensureCanSign(SuratEdaran $surat_edaran): void
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
        $targetNik = (string) ($surat_edaran->nik_penandatangan ?? '');

        if (empty($targetNik)) {
            abort(403, 'Dokumen ini belum memiliki penandatangan yang ditentukan.');
        }

        if ($userNik !== $targetNik) {
            abort(403, 'Hanya pegawai yang dipilih sebagai penandatangan yang dapat menandatangani dokumen ini.');
        }
    }

    private function ensureCanDelete(SuratEdaran $surat_edaran): void
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

        $creatorUsername = (string) ($surat_edaran->created_by_username ?? '');
        if ($creatorUsername === '') {
            $creatorUsername = (string) (AuditTrail::query()
                ->where(function ($q) {
                    $q->where('module', 'surat_edaran')
                        ->orWhere('table_name', 'surat_edaran');
                })
                ->where('record_id', $surat_edaran->id)
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
}
