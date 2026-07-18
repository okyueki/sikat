<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AdaptsSignableDocumentViews;
use App\Http\Requests\Surat\StoreMemoInternalRequest;
use App\Models\AuditTrail;
use App\Models\DisposisiSurat;
use App\Models\KlasifikasiSurat;
use App\Models\MasterStempel;
use App\Models\MemoInternal;
use App\Models\Pegawai;
use App\Models\SifatSurat;
use App\Models\Surat;
use App\Models\TandaTangan;
use App\Models\VerifikasiSurat;
use App\Services\MemoInternalPdfService;
use App\Services\Surat\MemoInternalActionResolver;
use App\Services\Surat\MemoInternalIndexService;
use App\Services\Surat\SuratPegawaiNameFilter;
use App\Services\SuratKeluarNumberService;
use App\Support\DocumentVerificationQr;
use App\Support\DocumentVerificationUrl;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class MemoInternalController extends Controller
{
    use AdaptsSignableDocumentViews {
        signableViewAdapter as protected defaultSignableViewAdapter;
    }

    public function __construct(
        private MemoInternalIndexService $indexService,
        private MemoInternalActionResolver $actionResolver,
    ) {
    }

    protected function signableRoutePrefix(): string
    {
        return 'memo_internal';
    }

    protected function signableDocumentLabel(): string
    {
        return 'Memo Internal';
    }

    protected function signableDocumentType(): string
    {
        return 'MEMO';
    }

    protected function hasMasaBerlakuFields(): bool
    {
        return false;
    }

    protected function signableViewAdapter(): array
    {
        return array_merge($this->defaultSignableViewAdapter(), [
            'numberFormatHint' => "RS'ASF/urut/kode_klasifikasi/departemen/tahun",
            'isMemoInternal' => true,
        ]);
    }

    public function index(Request $request)
    {
        $title = 'Memo Internal';
        $nik = Auth::user()->username;

        if ($request->ajax()) {
            $query = $this->indexService->indexQuery($nik);

            return DataTables::of($query)
                ->addColumn('nama_pegawai', fn ($row) => $row->pegawai ? $row->pegawai->nama : '-')
                ->addColumn('action', fn ($row) => $this->actionResolver->render($row))
                ->filterColumn('nama_pegawai', function ($query, $keyword) {
                    SuratPegawaiNameFilter::apply($query, $keyword);
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('memo_internal.index', compact('title'));
    }

    public function create()
    {
        $title = 'Create Memo Internal';
        $klasifikasiSurat = KlasifikasiSurat::all();
        $sifatSurat = SifatSurat::all();
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->get();
        $nik = Auth::user()->username;
        $previewNomorSurat = SuratKeluarNumberService::previewNext(
            $nik,
            (int) old('id_klasifikasi_surat', KlasifikasiSurat::value('id_klasifikasi_surat') ?? 1),
            old('tanggal_surat', now()->toDateString())
        );

        return view('memo_internal.create', compact(
            'title',
            'klasifikasiSurat',
            'sifatSurat',
            'pegawai',
            'previewNomorSurat'
        ));
    }

    public function previewNomor(Request $request)
    {
        $validated = $request->validate([
            'id_klasifikasi_surat' => 'required|integer|exists:klasifikasi_surat,id_klasifikasi_surat',
            'tanggal_surat' => 'required|date',
        ]);

        return response()->json([
            'nomor' => SuratKeluarNumberService::previewNext(
                Auth::user()->username,
                (int) $validated['id_klasifikasi_surat'],
                $validated['tanggal_surat']
            ),
        ]);
    }

    public function store(StoreMemoInternalRequest $request)
    {
        $nik = Auth::user()->username;
        $validated = $request->validated();
        $kodeSurat = 'SRT-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $path = $request->file('file_surat')->store('uploads/surat', 'public');
        try {
            MemoInternalPdfService::assertPdfParsableFromStoragePath($path);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw ValidationException::withMessages(['file_surat' => $e->getMessage()]);
        }

        $lampiranFile = $request->file('file_lampiran');

        $memo = DB::transaction(function () use ($validated, $nik, $kodeSurat, $path, $lampiranFile) {
            $surat = Surat::create([
                'id_klasifikasi_surat' => $validated['id_klasifikasi_surat'],
                'id_sifat_surat' => $validated['id_sifat_surat'],
                'nik_pengirim' => $nik,
                'perihal' => $validated['perihal'],
                'tanggal_surat' => $validated['tanggal_surat'],
                'lampiran' => $validated['lampiran'],
                'kode_surat' => $kodeSurat,
            ]);

            SuratKeluarNumberService::assignToSurat($surat, $nik);
            $surat->file_surat = $path;
            $surat->save();

            if ($lampiranFile) {
                $surat->file_lampiran = $lampiranFile->store('uploads/lampiran', 'public');
                $surat->save();
            }

            foreach ([
                ['nik' => $validated['ttd_utama'], 'status' => 'qrcode'],
                ['nik' => $validated['ttd_2'] ?? null, 'status' => 'qrcode_2'],
                ['nik' => $validated['ttd_3'] ?? null, 'status' => 'qrcode_3'],
                ['nik' => $validated['ttd_4'] ?? null, 'status' => 'qrcode_4'],
            ] as $ttd) {
                if (! empty($ttd['nik'])) {
                    TandaTangan::create([
                        'id_surat' => $surat->id_surat,
                        'nik_penandatangan' => $ttd['nik'],
                        'status_ttd' => $ttd['status'],
                    ]);
                }
            }

            return MemoInternal::create([
                'id_surat' => $surat->id_surat,
                'nik_penandatangan' => $validated['ttd_utama'],
                'created_by_username' => $nik,
            ]);
        });

        AuditTrail::logCreate('memo_internal', 'memo_internal', $memo->id, $memo->toArray(), 'Membuat memo internal');

        return redirect()
            ->route('memo_internal.tandaTangani', $memo)
            ->with('success', 'Memo Internal berhasil dibuat. Lanjutkan tanda tangan PDF.');
    }

    public function show(MemoInternal $memo_internal)
    {
        $memo_internal->load(['surat.pegawai', 'penandatangan', 'placements']);
        $title = 'Detail Memo Internal';
        $verifyUrl = DocumentVerificationUrl::qrVerifyUrl('memo_internal', $memo_internal);

        return view('surat_edaran.show', array_merge($this->signableViewAdapter(), [
            'title' => $title,
            'surat_edaran' => $memo_internal,
            'verificationQrDataUri' => null,
            'verificationQrUrl' => null,
            'verifyUrl' => $verifyUrl,
            'isMemoInternal' => true,
        ]));
    }

    public function streamPdf(MemoInternal $memo_internal)
    {
        $memo_internal->load(['surat', 'placements']);
        $relative = $memo_internal->file_pdf_signed ?: $memo_internal->surat?->file_surat;

        if (! $relative || ! Storage::disk('public')->exists($relative)) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        return response()->file(Storage::disk('public')->path($relative), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="memo_internal.pdf"',
        ]);
    }

    public function tandaTangani(MemoInternal $memo_internal)
    {
        $this->ensureCanSign($memo_internal);
        if ($memo_internal->tanggal_ditandatangani) {
            return redirect()->route('memo_internal.kirimsurat', encrypt($memo_internal->surat->kode_surat))
                ->with('warning', 'Dokumen sudah ditandatangani.');
        }

        $memo_internal->load(['surat', 'penandatangan', 'placements']);
        $memo_internal->surat->refresh();

        $title = 'Tanda Tangani Memo Internal';
        $pdfUrl = route('memo_internal.streamPdf', $memo_internal);
        $pegawai = $memo_internal->penandatangan;
        $signatureDetail = $memo_internal->signature_detail ?? [];
        if ($pegawai && empty($signatureDetail['nama_lengkap'])) {
            $signatureDetail['nama_lengkap'] = $pegawai->nama ?? '';
            $signatureDetail['inisial'] = $this->inisialFromNama($pegawai->nama ?? '');
        }

        $placementsForJs = $memo_internal->placements->map(fn ($p) => [
            'field_type' => $p->field_type,
            'page' => (int) $p->page,
            'x' => (float) $p->x,
            'y' => (float) $p->y,
            'width' => (float) ($p->width ?? 40),
            'height' => (float) ($p->height ?? 8),
            'value' => $p->value,
        ])->values()->all();

        return view('surat_edaran.tanda_tangani', array_merge($this->signableViewAdapter(), [
            'title' => $title,
            'surat_edaran' => $memo_internal,
            'pdfUrl' => $pdfUrl,
            'signatureDetail' => $signatureDetail,
            'placementsForJs' => $placementsForJs,
            'masterTandaTanganList' => auth()->user()->masterTandaTangan()->orderByDesc('is_default')->orderBy('id')->get(),
            'masterStempel' => MasterStempel::getPerusahaan(),
            'verifyUrl' => DocumentVerificationUrl::qrVerifyUrl('memo_internal', $memo_internal),
            'isMemoInternal' => true,
        ]));
    }

    public function saveSignatureAndPlacements(Request $request, MemoInternal $memo_internal)
    {
        $this->ensureCanSign($memo_internal);
        if ($memo_internal->tanggal_ditandatangani) {
            return $this->jsonError('Dokumen sudah sah.', 409, true);
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

        $existing = is_array($memo_internal->signature_detail) ? $memo_internal->signature_detail : [];
        $signatureType = $validated['signature_type'] ?? 'text';
        $imagePath = $this->persistSignatureImage($memo_internal, $validated, $existing, $signatureType);

        $memo_internal->update([
            'signature_detail' => [
                'nama_lengkap' => $validated['nama_lengkap'] ?? '',
                'inisial' => $validated['inisial'] ?? '',
                'font_style' => $validated['font_style'] ?? '1',
                'color' => $validated['color'] ?? '#000000',
                'type' => $signatureType,
                'image_url' => $imagePath['url'],
                'image_path' => $imagePath['path'],
            ],
        ]);

        $memo_internal->placements()->delete();
        foreach ($validated['placements'] ?? [] as $i => $p) {
            $width = isset($p['width']) ? (float) $p['width'] : null;
            $height = isset($p['height']) ? (float) $p['height'] : null;
            if (($p['field_type'] ?? '') === 'qr_verifikasi') {
                [$width, $height] = MemoInternalPdfService::normalizeQrDimensionsMm($width ?? 0, $height ?? 0);
            }
            $value = ($p['field_type'] ?? '') === 'nomor_surat'
                ? $memo_internal->surat->nomor_surat
                : ($p['value'] ?? null);

            $memo_internal->placements()->create([
                'field_type' => $p['field_type'],
                'page' => (int) $p['page'],
                'x' => (float) $p['x'],
                'y' => (float) $p['y'],
                'width' => $width,
                'height' => $height,
                'value' => $value,
                'options' => $p['options'] ?? null,
                'sort_order' => $i,
            ]);
        }

        if (! $finalize) {
            return response()->json([
                'success' => true,
                'message' => 'Draft posisi tanda tangan disimpan.',
                'finalized' => false,
            ]);
        }

        $memo_internal->load(['surat', 'placements']);
        $oldSuratPath = $memo_internal->surat->file_surat;
        $sourceRelative = MemoInternalPdfService::sourcePdfRelativePath((int) $memo_internal->id);
        $newPath = 'memo_internal/' . $memo_internal->id . '_signed.pdf';

        if (! Storage::disk('public')->exists($sourceRelative) && $oldSuratPath && Storage::disk('public')->exists($oldSuratPath)) {
            Storage::disk('public')->copy($oldSuratPath, $sourceRelative);
        }

        try {
            $signedContent = MemoInternalPdfService::generateSignedPdfContent($memo_internal, true);
            Storage::disk('public')->put($newPath, $signedContent);

            DB::transaction(function () use ($memo_internal, $newPath) {
                $memo_internal->update([
                    'tanggal_ditandatangani' => now(),
                    'file_pdf_signed' => $newPath,
                ]);
                $memo_internal->surat->update(['file_surat' => $newPath]);
            });
        } catch (\Throwable $e) {
            report($e);

            return $this->jsonError(
                'Gagal memfinalisasi PDF. Pastikan Stirling PDF berjalan.',
                str_contains(strtolower($e->getMessage()), 'stirling') ? 422 : 500,
                false
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Memo Internal berhasil ditandatangani.',
            'finalized' => true,
            'redirect' => route('memo_internal.kirimsurat', encrypt($memo_internal->surat->kode_surat)),
        ]);
    }

    public function generateSignedPdf(MemoInternal $memo_internal)
    {
        $memo_internal->load(['surat', 'penandatangan', 'placements']);
        $pdfContent = MemoInternalPdfService::generateSignedPdfContent(
            $memo_internal,
            (bool) $memo_internal->tanggal_ditandatangani
        );

        if ($memo_internal->tanggal_ditandatangani && $memo_internal->file_pdf_signed) {
            Storage::disk('public')->put($memo_internal->file_pdf_signed, $pdfContent);
        }

        $filename = 'memo_internal_' . $memo_internal->getJudulForFilename() . '_signed.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function verificationQrPng(MemoInternal $memo_internal)
    {
        $verifyUrl = DocumentVerificationUrl::qrVerifyUrl('memo_internal', $memo_internal);

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

    public function verifyAuthenticity(MemoInternal $memo_internal)
    {
        return redirect()->route('surat.show', encrypt($memo_internal->surat->kode_surat));
    }

    public function kirimsurat(string $encryptedKodeSurat)
    {
        Carbon::setLocale('id');
        $title = 'Kirim Memo Internal';
        $kodeSurat = decrypt($encryptedKodeSurat);
        $surat = Surat::with(['pegawai', 'memoInternal'])
            ->where('kode_surat', $kodeSurat)
            ->whereHas('memoInternal')
            ->firstOrFail();

        $this->authorize('viewKeluar', $surat);

        if (! $surat->memoInternal->tanggal_ditandatangani) {
            return redirect()
                ->route('memo_internal.tandaTangani', $surat->memoInternal)
                ->with('warning', 'Selesaikan tanda tangan PDF terlebih dahulu.');
        }

        $pdfUrl = route('memo_internal.streamPdf', $surat->memoInternal);
        $tanggalSurat = Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y');
        $verifikasiSurat = VerifikasiSurat::with('pegawai')->where('id_surat', $surat->id_surat)->get();
        $disposisiAll = DisposisiSurat::with('pegawai')->where('id_surat', $surat->id_surat)->get();
        $verifikasi = VerifikasiSurat::where('id_surat', $surat->id_surat)->first();
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->get();

        return view('surat_keluar.kirimsurat', array_merge(compact(
            'title',
            'surat',
            'verifikasi',
            'pegawai',
            'pdfUrl',
            'tanggalSurat',
            'verifikasiSurat',
            'disposisiAll'
        ), [
            'kirimProsesRoute' => route('memo_internal.kirimSuratProses'),
            'isMemoInternal' => true,
        ]));
    }

    public function kirimSuratProses(Request $request)
    {
        $request->validate([
            'id_surat' => 'required|exists:surat,id_surat',
            'nik_atasan_langsung' => 'required',
        ]);

        $surat = Surat::whereHas('memoInternal')->findOrFail($request->id_surat);
        $this->authorize('viewKeluar', $surat);

        VerifikasiSurat::updateOrCreate(
            ['id_surat' => $surat->id_surat],
            [
                'nik_verifikator' => $request->nik_atasan_langsung,
                'status_surat' => 'Dikirim',
                'tanggal_verifikasi' => now(),
            ]
        );

        return redirect()->route('memo_internal.index')->with('success', 'Memo Internal berhasil dikirim.');
    }

    public function detail(string $encryptedKodeSurat)
    {
        Carbon::setLocale('id');
        $title = 'Detail Memo Internal';
        $kodeSurat = decrypt($encryptedKodeSurat);
        $surat = Surat::with(['pegawai', 'memoInternal'])
            ->where('kode_surat', $kodeSurat)
            ->whereHas('memoInternal')
            ->firstOrFail();

        $this->authorize('viewKeluar', $surat);

        $pdfUrl = route('memo_internal.streamPdf', $surat->memoInternal);
        $tanggalSurat = Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y');
        $verifikasiSurat = VerifikasiSurat::with('pegawai')->where('id_surat', $surat->id_surat)->get();
        $disposisiAll = DisposisiSurat::with('pegawai')->where('id_surat', $surat->id_surat)->get();

        return view('surat_keluar.detail', compact(
            'title',
            'surat',
            'pdfUrl',
            'tanggalSurat',
            'verifikasiSurat',
            'disposisiAll'
        ));
    }

    public function destroy(MemoInternal $memo_internal)
    {
        $surat = $memo_internal->surat;
        if (! $surat) {
            abort(404);
        }

        $this->authorize('deleteKeluar', $surat);

        if ($surat->file_surat) {
            Storage::disk('public')->delete($surat->file_surat);
        }
        if ($surat->file_lampiran) {
            Storage::disk('public')->delete($surat->file_lampiran);
        }

        $memo = $surat->memoInternal ?? $memo_internal;
        if ($memo?->file_pdf_signed && $memo->file_pdf_signed !== $surat->file_surat) {
            Storage::disk('public')->delete($memo->file_pdf_signed);
        }

        VerifikasiSurat::where('id_surat', $surat->id_surat)->delete();
        TandaTangan::where('id_surat', $surat->id_surat)->delete();
        $memo?->delete();
        $surat->delete();

        return redirect()->route('memo_internal.index')->with('success', 'Memo Internal berhasil dihapus.');
    }

    private function ensureCanSign(MemoInternal $memo): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        $level = strtolower((string) ($user->level ?? ''));
        if (in_array($level, ['admin', 'super admin', 'superadmin'], true)) {
            return;
        }

        if ((string) $user->username !== (string) ($memo->nik_penandatangan ?? '')) {
            abort(403, 'Hanya penandatangan utama yang dapat menandatangani memo ini.');
        }
    }

    private function inisialFromNama(string $nama): string
    {
        $words = preg_split('/\s+/', trim($nama), -1, PREG_SPLIT_NO_EMPTY);
        $inisial = '';
        foreach (array_slice($words, 0, 3) as $word) {
            $inisial .= mb_substr($word, 0, 1, 'UTF-8');
        }

        return mb_strtoupper($inisial, 'UTF-8');
    }

    private function persistSignatureImage(MemoInternal $memo, array $validated, array $existing, string $signatureType): array
    {
        $existingPath = (string) ($existing['image_path'] ?? '');
        if ($signatureType !== 'image') {
            if ($existingPath !== '' && Storage::disk('public')->exists($existingPath)) {
                Storage::disk('public')->delete($existingPath);
            }

            return ['path' => null, 'url' => ''];
        }

        $binary = $this->decodeSignatureImageDataUrl($validated['cropped_signature_image'] ?? null)
            ?? $this->decodeSignatureImageDataUrl($validated['signature_image_url'] ?? null);

        if ($binary === null) {
            return [
                'path' => $existingPath ?: null,
                'url' => (string) ($existing['image_url'] ?? ''),
            ];
        }

        $path = 'tanda_tangan/memo_' . $memo->id . '_' . time() . '.png';
        Storage::disk('public')->put($path, $binary);
        if ($existingPath !== '' && $existingPath !== $path) {
            Storage::disk('public')->delete($existingPath);
        }

        return ['path' => $path, 'url' => Storage::disk('public')->url($path)];
    }

    private function decodeSignatureImageDataUrl(?string $value): ?string
    {
        if (! is_string($value) || ! str_starts_with(trim($value), 'data:image')) {
            return null;
        }
        if (! preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=\r\n]+)$/', trim($value), $matches)) {
            return null;
        }

        return base64_decode($matches[1], true) ?: null;
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
