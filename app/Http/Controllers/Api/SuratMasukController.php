<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Surat;
use App\Models\VerifikasiSurat;
use App\Models\DisposisiSurat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API Surat Masuk — read-only (list + detail).
 * Hanya surat yang terkait dengan user login (sebagai verifikator atau penerima disposisi).
 */
class SuratMasukController extends Controller
{
    /**
     * GET /api/surat-masuk
     * Daftar surat masuk untuk user login (yang punya akses sebagai verifikator atau penerima disposisi).
     */
    public function index(Request $request)
    {
        $nik = Auth::user()->username;

        $query = Surat::with(['pegawai', 'klasifikasi_surat', 'sifat_surat'])
            ->where(function ($q) use ($nik) {
                $q->whereHas('verifikasi', fn ($q2) => $q2->where('nik_verifikator', $nik))
                    ->orWhereHas('disposisi', fn ($q2) => $q2->where('nik_penerima', $nik));
            });

        // Optional filter
        if ($request->filled('tahun')) {
            $query->whereYear('tanggal_surat', $request->tahun);
        }
        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal_surat', $request->bulan);
        }

        $suratList = $query->orderByDesc('tanggal_surat')->get();

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
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'surat' => $data,
                'jumlah_verifikasi_belum_dibaca' => $jumlahVerifikasiBelumDibaca,
                'jumlah_disposisi_belum_dibaca' => $jumlahDisposisiBelumDibaca,
            ],
        ]);
    }

    /**
     * GET /api/surat-masuk/notifikasi
     * Jumlah notifikasi surat masuk (untuk badge): verifikasi belum dibaca + disposisi belum dibaca.
     * Ringan, cocok dipanggil saat buka dashboard atau refresh untuk menampilkan notifikasi.
     */
    public function notifikasi(Request $request)
    {
        $nik = Auth::user()->username;

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

        return response()->json([
            'success' => true,
            'data' => [
                'verifikasi_belum_dibaca' => $jumlahVerifikasiBelumDibaca,
                'disposisi_belum_dibaca' => $jumlahDisposisiBelumDibaca,
                'total' => $jumlahVerifikasiBelumDibaca + $jumlahDisposisiBelumDibaca,
            ],
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

        $bolehAkses = VerifikasiSurat::where('id_surat', $surat->id_surat)->where('nik_verifikator', $nik)->exists()
            || DisposisiSurat::where('id_surat', $surat->id_surat)->where('nik_penerima', $nik)->exists();

        if (!$bolehAkses) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke surat ini.',
            ], 403);
        }

        return $this->detailResponse($surat);
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

        $bolehAkses = VerifikasiSurat::where('id_surat', $surat->id_surat)->where('nik_verifikator', $nik)->exists()
            || DisposisiSurat::where('id_surat', $surat->id_surat)->where('nik_penerima', $nik)->exists();

        if (!$bolehAkses) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke surat ini.',
            ], 403);
        }

        return $this->detailResponse($surat);
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
