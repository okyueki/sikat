<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Pegawai;
use App\Models\VerifikasiSurat;
use App\Models\DisposisiSurat;
use App\Models\PengajuanLibur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * API Dashboard untuk app (React/mobile).
 * Mengembalikan data yang nampil di dashboard user: undangan agenda, notifikasi (count).
 * Setiap user hanya dapat data miliknya (berdasarkan NIK dari token).
 */
class DashboardController extends Controller
{
    /**
     * GET /api/dashboard
     * Data dashboard: undangan agenda (otomatis nampil untuk user yang terundang) + jumlah notifikasi.
     */
    public function index(Request $request)
    {
        $nik = Auth::user()->username;

        // Undangan agenda: agenda yang mengundang user (hari ini + 7 hari ke depan, atau sedang berlangsung)
        $agendaTerundang = Agenda::with(['pimpinan', 'notulenPegawai'])
            ->where(function ($query) use ($nik) {
                $query->where(function ($q) use ($nik) {
                    $q->whereRaw('JSON_CONTAINS(yang_terundang, ?)', [json_encode('all')])
                        ->orWhereRaw('JSON_CONTAINS(yang_terundang, ?)', [json_encode($nik)]);
                });
            })
            ->where(function ($query) {
                $query->whereBetween('mulai', [Carbon::now()->startOfDay(), Carbon::now()->addDays(7)->endOfDay()])
                    ->orWhere(function ($q) {
                        $q->where('mulai', '<=', Carbon::now())
                            ->where(function ($q2) {
                                $q2->whereNull('akhir')->orWhere('akhir', '>=', Carbon::now());
                            });
                    });
            })
            ->where('akhir', '>=', Carbon::now()->subHour()) // belum lewat 1 jam setelah akhir (masih relevan)
            ->orderBy('mulai', 'asc')
            ->limit(20)
            ->get()
            ->filter(fn ($agenda) => $agenda->userTerundang($nik))
            ->map(function ($agenda) use ($nik) {
                $agendaId = (int) $agenda->id;
                $row = DB::selectOne(
                    'SELECT status_kehadiran FROM absensi_agenda WHERE agenda_id = ? AND nik = ? LIMIT 1',
                    [$agendaId, (string) $nik]
                );
                $statusKehadiran = $row ? ($row->status_kehadiran ?? null) : null;
                $sudahAbsen = $row && ($row->status_kehadiran === 'hadir');

                $mulai = Carbon::parse($agenda->mulai);
                $akhir = $agenda->akhir ? Carbon::parse($agenda->akhir) : null;
                $now = Carbon::now();

                if ($now->lt($mulai)) {
                    $statusLabel = 'Akan Datang';
                    $statusClass = 'info';
                    $waktuInfo = $mulai->format('d M Y H:i');
                } elseif ($akhir && $now->gt($akhir)) {
                    $statusLabel = 'Selesai';
                    $statusClass = 'secondary';
                    $waktuInfo = $akhir->format('d M Y H:i');
                } else {
                    $statusLabel = 'Sedang Berlangsung';
                    $statusClass = 'success';
                    $waktuInfo = 'Sekarang';
                }

                return [
                    'id' => $agenda->id,
                    'judul' => $agenda->judul,
                    'deskripsi' => $agenda->deskripsi ?? null,
                    'mulai' => $agenda->mulai,
                    'akhir' => $agenda->akhir ?? null,
                    'tempat' => $agenda->tempat ?? null,
                    'pimpinan_rapat' => $agenda->pimpinan_rapat,
                    'pimpinan_nama' => $agenda->pimpinan ? $agenda->pimpinan->nama : null,
                    'status_label' => $statusLabel,
                    'status_class' => $statusClass,
                    'waktu_info' => $waktuInfo,
                    'sudah_absen' => $sudahAbsen,
                    'status_kehadiran' => $statusKehadiran,
                    'mulai_format' => $mulai->format('d M Y H:i'),
                    'akhir_format' => $akhir ? $akhir->format('d M Y H:i') : null,
                ];
            })
            ->values();

        // Jumlah notifikasi (untuk badge): verifikasi surat, disposisi, pengajuan libur menunggu
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

        // Sebagai atasan: pengajuan cuti/ijin yang menunggu persetujuan saya
        $jumlahPengajuanMenunggu = PengajuanLibur::where('nik_atasan_langsung', $nik)
            ->where('status', 'Dikirim')
            ->count();

        // Sebagai pegawai: pengajuan cuti/ijin saya yang masih menunggu persetujuan atasan
        $pengajuanCutiIjinSayaMenunggu = PengajuanLibur::where('nik', $nik)
            ->where('status', 'Dikirim')
            ->count();

        $totalNotif = $jumlahVerifikasiBelumDibaca + $jumlahDisposisiBelumDibaca + $jumlahPengajuanMenunggu + $pengajuanCutiIjinSayaMenunggu;

        return response()->json([
            'success' => true,
            'data' => [
                'undangan_agenda' => $agendaTerundang,
                'notifikasi' => [
                    'verifikasi_surat_belum_dibaca' => $jumlahVerifikasiBelumDibaca,
                    'disposisi_belum_dibaca' => $jumlahDisposisiBelumDibaca,
                    'pengajuan_menunggu' => $jumlahPengajuanMenunggu,
                    'pengajuan_cuti_ijin_saya_menunggu' => $pengajuanCutiIjinSayaMenunggu,
                    'pengajuan_cuti_ijin_saya_menunggu_pesan' => $pengajuanCutiIjinSayaMenunggu > 0
                        ? 'Pengajuan cuti/ijin menunggu persetujuan atasan'
                        : null,
                    'total' => $totalNotif,
                ],
            ],
        ]);
    }

    /**
     * GET /api/dashboard/ulang-tahun
     * Daftar pegawai yang ulang tahun hari ini (pegawai aktif saja).
     */
    public function ulangTahunHariIni(Request $request)
    {
        $today = Carbon::today();

        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')
            ->whereNotNull('tgl_lahir')
            ->whereMonth('tgl_lahir', $today->month)
            ->whereDay('tgl_lahir', $today->day)
            ->orderBy('nama')
            ->get(['id', 'nik', 'nama', 'departemen', 'tgl_lahir']);

        $data = $pegawai->map(function ($p) {
            return [
                'id' => $p->id,
                'nik' => $p->nik,
                'nama' => $p->nama,
                'departemen' => $p->departemen ?? null,
                'tgl_lahir' => $p->tgl_lahir ? Carbon::parse($p->tgl_lahir)->format('Y-m-d') : null,
                'tgl_lahir_format' => $p->tgl_lahir ? Carbon::parse($p->tgl_lahir)->format('d M Y') : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'tanggal' => $today->format('Y-m-d'),
            'tanggal_format' => $today->format('d M Y'),
        ]);
    }
}
