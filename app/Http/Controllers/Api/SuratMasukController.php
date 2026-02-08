<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Surat;
use App\Models\VerifikasiSurat;
use App\Models\DisposisiSurat;
use App\Services\SuratMasukPdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * API Surat Masuk — read-only (list + detail).
 * Hanya surat yang terkait dengan user login (sebagai verifikator atau penerima disposisi).
 */
class SuratMasukController extends Controller
{
    /**
     * GET /api/surat-masuk
     * Daftar surat masuk untuk user login. Query: per_page, page, tahun, bulan, sort, order, q, verifikasi_status, disposisi_status.
     */
    public function index(Request $request)
    {
        $nik = Auth::user()->username;
        $perPage = min((int) $request->query('per_page', 20), 50);
        $perPage = $perPage >= 1 ? $perPage : 20;

        $query = Surat::with(['pegawai', 'klasifikasi_surat', 'sifat_surat'])
            ->where(function ($q) use ($nik) {
                $q->whereHas('verifikasi', fn ($q2) => $q2->where('nik_verifikator', $nik))
                    ->orWhereHas('disposisi', fn ($q2) => $q2->where('nik_penerima', $nik));
            });

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal_surat', $request->tahun);
        }
        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal_surat', $request->bulan);
        }
        if ($request->filled('q')) {
            $q = $request->query('q');
            $query->where(function ($qry) use ($q) {
                $qry->where('perihal', 'like', '%' . $q . '%')
                    ->orWhere('nomor_surat', 'like', '%' . $q . '%')
                    ->orWhere('kode_surat', 'like', '%' . $q . '%');
            });
        }
        if ($request->filled('verifikasi_status')) {
            $status = $request->query('verifikasi_status');
            $query->whereHas('verifikasi', fn ($q2) => $q2->where('nik_verifikator', $nik)->where('status_surat', $status));
        }
        if ($request->filled('disposisi_status')) {
            $status = $request->query('disposisi_status');
            $query->whereHas('disposisi', fn ($q2) => $q2->where('nik_penerima', $nik)->where('status_disposisi', $status));
        }

        $sortAllowed = ['tanggal_surat', 'perihal', 'nomor_surat', 'kode_surat', 'tanggal_surat_diterima'];
        $sort = $request->query('sort', 'tanggal_surat');
        $sort = in_array($sort, $sortAllowed, true) ? $sort : 'tanggal_surat';
        $order = strtolower($request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $order);

        $paginator = $query->paginate($perPage);
        $suratList = $paginator->getCollection();

        $jumlahVerifikasiBelumDibaca = VerifikasiSurat::where('nik_verifikator', $nik)
            ->where(function ($q) {
                $q->whereNull('status_surat')->orWhere('status_surat', 'Dikirim');
            })
            ->count();

        $jumlahDisposisiBelumDibaca = DisposisiSurat::where('nik_penerima', $nik)
            ->where(function ($q) {
                $q->whereNull('status_disposisi')->orWhere('status_disposisi', 'Dikirim');
            })
            ->count();

        $data = $suratList->map(function ($row) use ($nik) {
            $verifikasi = $row->verifikasi()
                ->where('nik_verifikator', $nik)
                ->where('id_surat', $row->id_surat)
                ->orderByDesc('id_verifikasi_surat')
                ->first();
            $disposisi = $row->disposisi()
                ->where('nik_penerima', $nik)
                ->where('id_surat', $row->id_surat)
                ->orderByDesc('id_disposisi_surat')
                ->first();

            return [
                'id_surat' => $row->id_surat,
                'kode_surat' => $row->kode_surat,
                'nomor_surat' => $row->nomor_surat,
                'perihal' => $row->perihal,
                'pengirim' => $this->pengirimNama($row),
                'tanggal_surat' => $row->tanggal_surat ? Carbon::parse($row->tanggal_surat)->format('Y-m-d') : null,
                'tanggal_surat_diterima' => $row->tanggal_surat_diterima ? Carbon::parse($row->tanggal_surat_diterima)->format('Y-m-d') : null,
                'klasifikasi' => $row->klasifikasi_surat ? $row->klasifikasi_surat->nama_klasifikasi_surat ?? null : null,
                'sifat' => $row->sifat_surat ? $row->sifat_surat->nama_sifat_surat ?? null : null,
                'status_verifikasi' => $verifikasi ? $verifikasi->status_surat : null,
                'status_disposisi' => $disposisi ? $disposisi->status_disposisi : null,
                'punya_file_surat' => !empty($row->file_surat),
                'punya_file_lampiran' => !empty($row->file_lampiran),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => [
                'surat' => $data,
                'jumlah_verifikasi_belum_dibaca' => $jumlahVerifikasiBelumDibaca,
                'jumlah_disposisi_belum_dibaca' => $jumlahDisposisiBelumDibaca,
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * GET /api/surat-masuk/notifikasi
     * Jumlah notifikasi surat masuk (untuk badge): verifikasi belum dibaca + disposisi belum dibaca.
     * Cache 2 menit per user; di-invalidate saat user tandai-dibaca.
     */
    public function notifikasi(Request $request)
    {
        $nik = Auth::user()->username;
        $cacheKey = 'surat_notif_' . $nik;
        $ttl = 120; // 2 menit

        $data = Cache::remember($cacheKey, $ttl, function () use ($nik) {
            $jumlahVerifikasiBelumDibaca = VerifikasiSurat::where('nik_verifikator', $nik)
                ->where(function ($q) {
                    $q->whereNull('status_surat')->orWhere('status_surat', 'Dikirim');
                })
                ->count();

            $jumlahDisposisiBelumDibaca = DisposisiSurat::where('nik_penerima', $nik)
                ->where(function ($q) {
                    $q->whereNull('status_disposisi')->orWhere('status_disposisi', 'Dikirim');
                })
                ->count();

            return [
                'verifikasi_belum_dibaca' => $jumlahVerifikasiBelumDibaca,
                'disposisi_belum_dibaca' => $jumlahDisposisiBelumDibaca,
                'total' => $jumlahVerifikasiBelumDibaca + $jumlahDisposisiBelumDibaca,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * POST /api/surat-masuk/{id}/tandai-dibaca
     * Tandai verifikasi/disposisi user sebagai "Dibaca" saat user membuka detail di app (selaras dengan web).
     * Hanya mengubah jika status saat ini Dikirim atau null. Response: success + jumlah yang diupdate.
     */
    public function tandaiDibaca(Request $request, $id)
    {
        $nik = Auth::user()->username;

        $surat = Surat::where('id_surat', $id)->firstOrFail();

        if (!$this->userBolehAksesSurat($surat, $nik)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke surat ini.',
            ], 403);
        }

        $updated = 0;

        $verifikasi = VerifikasiSurat::where('id_surat', $surat->id_surat)
            ->where('nik_verifikator', $nik)
            ->orderByDesc('id_verifikasi_surat')
            ->first();
        if ($verifikasi && in_array($verifikasi->status_surat, [null, 'Dikirim'], true)) {
            $verifikasi->update(['status_surat' => 'Dibaca']);
            $updated++;
        }

        $disposisi = DisposisiSurat::where('id_surat', $surat->id_surat)
            ->where('nik_penerima', $nik)
            ->orderByDesc('id_disposisi_surat')
            ->first();
        if ($disposisi && in_array($disposisi->status_disposisi, [null, 'Dikirim'], true)) {
            $disposisi->update(['status_disposisi' => 'Dibaca']);
            $updated++;
        }

        if ($updated > 0) {
            Cache::forget('surat_notif_' . $nik);
        }

        return response()->json([
            'success' => true,
            'message' => $updated > 0 ? 'Surat ditandai sudah dibaca.' : 'Status sudah Dibaca atau lebih.',
            'data' => ['updated' => $updated],
        ]);
    }

    /**
     * GET /api/surat-masuk/{id}
     * Detail surat masuk by id_surat. Hanya boleh akses jika user terlibat (verifikator atau penerima disposisi).
     */
    public function show(Request $request, $id)
    {
        $nik = Auth::user()->username;

        $surat = Surat::with(['pegawai', 'klasifikasi_surat', 'sifat_surat'])
            ->where('id_surat', $id)
            ->firstOrFail();

        if (!$this->userBolehAksesSurat($surat, $nik)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke surat ini.',
            ], 403);
        }

        return $this->detailResponse($surat);
    }

    /**
     * GET /api/surat-masuk/{id}/file-pdf
     * Stream file surat dalam bentuk PDF. Jika asli PDF → stream langsung; jika DOCX → konversi dulu lalu stream.
     * Butuh auth dan akses (verifikator atau penerima disposisi).
     */
    public function filePdf(Request $request, $id)
    {
        $nik = Auth::user()->username;

        $surat = Surat::with(['pegawai', 'klasifikasi_surat', 'sifat_surat'])
            ->where('id_surat', $id)
            ->firstOrFail();

        if (!$this->userBolehAksesSurat($surat, $nik)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke surat ini.',
            ], 403);
        }

        $pdfPath = SuratMasukPdfService::getPdfPath($surat);
        if (!$pdfPath || !file_exists($pdfPath)) {
            return response()->json([
                'success' => false,
                'message' => 'File surat tidak ditemukan atau konversi PDF gagal.',
            ], 404);
        }

        $etag = '"' . md5_file($pdfPath) . '"';
        if (trim($request->header('If-None-Match', '')) === $etag) {
            return response('', 304)->withHeaders(['ETag' => $etag]);
        }

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="surat-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $surat->kode_surat) . '.pdf"',
            'ETag' => $etag,
        ]);
    }

    /**
     * Detail by kode_surat (alternatif: GET /api/surat-masuk/kode/{kode_surat}).
     */
    public function showByKode(Request $request, string $kodeSurat)
    {
        $nik = Auth::user()->username;

        $surat = Surat::with(['pegawai', 'klasifikasi_surat', 'sifat_surat'])
            ->where('kode_surat', $kodeSurat)
            ->firstOrFail();

        if (!$this->userBolehAksesSurat($surat, $nik)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke surat ini.',
            ], 403);
        }

        return $this->detailResponse($surat);
    }

    /**
     * GET /api/surat-masuk/{id}/file-lampiran
     * Stream file lampiran surat dengan auth. Akses sama seperti detail (verifikator atau penerima disposisi).
     */
    public function fileLampiran(Request $request, $id)
    {
        $nik = Auth::user()->username;

        $surat = Surat::where('id_surat', $id)->firstOrFail();

        if (!$this->userBolehAksesSurat($surat, $nik)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke surat ini.',
            ], 403);
        }

        if (empty($surat->file_lampiran)) {
            return response()->json([
                'success' => false,
                'message' => 'Surat ini tidak memiliki lampiran.',
            ], 404);
        }

        // Hindari path traversal: hanya path di bawah storage/app/public
        $basePath = realpath(storage_path('app/public'));
        $fullPath = realpath($basePath . DIRECTORY_SEPARATOR . ltrim(str_replace('\\', '/', $surat->file_lampiran), '/'));
        if ($basePath === false || $fullPath === false || !is_file($fullPath) || strpos($fullPath, $basePath) !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'File lampiran tidak ditemukan.',
            ], 404);
        }

        $ext = strtolower(pathinfo($surat->file_lampiran, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
        $filename = 'lampiran-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $surat->kode_surat) . '.' . $ext;

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Cek apakah user (nik) boleh akses surat (sebagai verifikator atau penerima disposisi).
     */
    private function userBolehAksesSurat(Surat $surat, string $nik): bool
    {
        return VerifikasiSurat::where('id_surat', $surat->id_surat)->where('nik_verifikator', $nik)->exists()
            || DisposisiSurat::where('id_surat', $surat->id_surat)->where('nik_penerima', $nik)->exists();
    }

    private function detailResponse(Surat $surat)
    {
        $verifikasiSurat = VerifikasiSurat::with('pegawai')
            ->where('id_surat', $surat->id_surat)
            ->orderBy('id_verifikasi_surat')
            ->get();

        $disposisiAll = DisposisiSurat::with('pegawai2')
            ->where('id_surat', $surat->id_surat)
            ->orderBy('id_disposisi_surat')
            ->get();

        $fileSuratUrl = !empty($surat->file_surat) ? asset('storage/' . $surat->file_surat) : null;
        $fileLampiranUrl = !empty($surat->file_lampiran) ? asset('storage/' . $surat->file_lampiran) : null;
        // URL untuk tampil sebagai PDF: jika asli sudah PDF pakai file langsung, jika DOCX pakai endpoint konversi
        $isPdf = !empty($surat->file_surat) && strtolower(pathinfo($surat->file_surat, PATHINFO_EXTENSION)) === 'pdf';
        $fileSuratPdfUrl = !empty($surat->file_surat)
            ? ($isPdf ? $fileSuratUrl : url('/api/surat-masuk/' . $surat->id_surat . '/file-pdf'))
            : null;

        $data = [
            'id_surat' => $surat->id_surat,
            'kode_surat' => $surat->kode_surat,
            'nomor_surat' => $surat->nomor_surat,
            'perihal' => $surat->perihal,
            'pengirim' => $this->pengirimNama($surat),
            'nik_pengirim' => $surat->nik_pengirim,
            'pengirim_external' => $surat->pengirim_external,
            'tanggal_surat' => $surat->tanggal_surat ? Carbon::parse($surat->tanggal_surat)->format('Y-m-d') : null,
            'tanggal_surat_diterima' => $surat->tanggal_surat_diterima ? Carbon::parse($surat->tanggal_surat_diterima)->format('Y-m-d') : null,
            'lampiran' => $surat->lampiran,
            'klasifikasi' => $surat->klasifikasi_surat ? $surat->klasifikasi_surat->nama_klasifikasi_surat ?? null : null,
            'id_klasifikasi_surat' => $surat->id_klasifikasi_surat,
            'sifat' => $surat->sifat_surat ? $surat->sifat_surat->nama_sifat_surat ?? null : null,
            'id_sifat_surat' => $surat->id_sifat_surat,
            'file_surat' => $surat->file_surat,
            'file_surat_url' => $fileSuratUrl,
            'file_surat_pdf_url' => $fileSuratPdfUrl,
            'file_lampiran' => $surat->file_lampiran,
            'file_lampiran_url' => $fileLampiranUrl,
            'verifikasi' => $verifikasiSurat->map(fn ($v) => [
                'id_verifikasi_surat' => $v->id_verifikasi_surat,
                'nik_verifikator' => $v->nik_verifikator,
                'nama_verifikator' => $v->pegawai ? $v->pegawai->nama : null,
                'status_surat' => $v->status_surat,
                'tanggal_verifikasi' => $v->tanggal_verifikasi ? Carbon::parse($v->tanggal_verifikasi)->toIso8601String() : null,
                'catatan' => $v->catatan,
            ])->values()->all(),
            'disposisi' => $disposisiAll->map(fn ($d) => [
                'id_disposisi_surat' => $d->id_disposisi_surat,
                'nik_disposisi' => $d->nik_disposisi,
                'nik_penerima' => $d->nik_penerima,
                'nama_penerima' => $d->pegawai2 ? $d->pegawai2->nama : null,
                'status_disposisi' => $d->status_disposisi,
                'tanggal_disposisi' => $d->tanggal_disposisi ? Carbon::parse($d->tanggal_disposisi)->toIso8601String() : null,
                'catatan_disposisi' => $d->catatan_disposisi,
            ])->values()->all(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function pengirimNama(Surat $surat): string
    {
        if (!empty($surat->nik_pengirim)) {
            return $surat->pegawai ? $surat->pegawai->nama : '-';
        }
        return $surat->pengirim_external ?? '-';
    }
}
