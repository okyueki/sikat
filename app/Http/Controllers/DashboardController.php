<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pegawai;
use App\Models\RegPeriksa;
use App\Models\Surat;
use App\Models\SuratEdaran;
use App\Models\PeraturanDirektur;
use App\Models\KeputusanDirektur;
use App\Models\Spo;
use App\Models\TemporaryPresensi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\PengajuanLibur;
use App\Models\Agenda;
use App\Models\Dokter;
use App\Models\JadwalPegawai;
use App\Models\RekapPresensi;
use App\Models\AbsensiAgenda;
use App\Services\DashboardSuratChartService;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardSuratChartService $suratChartService,
    ) {
    }

    public function index()
    {
        $dashboardErrorMessage = null;

        try {
            return $this->indexData($dashboardErrorMessage);
        } catch (\Throwable $e) {
            Log::error('Dashboard index error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $dashboardErrorMessage = 'Data dashboard sementara tidak lengkap. Periksa koneksi database (utama dan server_74/SIMRS) serta storage/logs/laravel.log.';
            return $this->indexData($dashboardErrorMessage, true);
        }
    }

    /**
     * Logic dashboard; jika $useFallback true, pakai data kosong agar halaman tetap tampil.
     */
    private function indexData(?string &$dashboardErrorMessage, bool $useFallback = false)
    {
        if ($useFallback) {
            $totalPegawaiAktif = 0;
            $jumlahPasienHariIni = 0;
            $presensiUser = collect();
            $presensiMessage = 'Data presensi sementara tidak tersedia.';
            $totalHari = 0;
            $agendaTerundang = collect();
            $jumlahDokter = 0;
            $pegawaiUlangTahunHariIni = collect();
            $suratMenyuratCards = $this->buildSuratMenyuratCards();
            $suratKlasifikasiChart = [];
            $suratDepartemenChart = [];
            return view('dashboard.index', compact(
                'totalPegawaiAktif', 'jumlahPasienHariIni',
                'presensiUser', 'presensiMessage', 'totalHari',
                'agendaTerundang', 'jumlahDokter',
                'pegawaiUlangTahunHariIni', 'suratMenyuratCards', 'dashboardErrorMessage',
                'suratKlasifikasiChart', 'suratDepartemenChart'
            ));
        }

    $totalPegawaiAktif = Pegawai::where('stts_aktif', 'AKTIF')->count();

    // NIK untuk absensi agenda: pakai dari Pegawai agar konsisten dengan tabel absensi_agenda
    $username = Auth::check() ? (Auth::user()->username ?? '') : '';
    $pegawai = $username ? Pegawai::where('nik', $username)->first() : null;
    $nik = $pegawai ? (string) $pegawai->nik : (string) $username;

    // Pastikan $presensiUser didefinisikan sebagai collection kosong
    $presensiUser = collect();

    if ($pegawai) {
        // Ambil data presensi jika pegawai ditemukan (temporary = dari web/barcode)
        $presensiUser = TemporaryPresensi::where('id', $pegawai->id)
            ->orderBy('jam_datang', 'desc')
            ->get();
    }

    // Cek apakah ada presensi hari ini: dari temporary (web) ATAU dari rekap (API/app)
    $presensiHariIni = $presensiUser->where('jam_datang', '>=', today())->first();
    if (!$presensiHariIni && $pegawai) {
        $rekapHariIni = RekapPresensi::where('id', $pegawai->id)
            ->whereDate('jam_datang', \Carbon\Carbon::today())
            ->first();
        if ($rekapHariIni) {
            $presensiHariIni = $rekapHariIni; // pakai objek rekap untuk pesan (punya jam_datang)
        }
    }

    // Tentukan pesan presensi berdasarkan apakah sudah ada data presensi hari ini atau belum
    if ($presensiHariIni) {
        try {
            $presensiMessage = "Anda sudah melakukan presensi pada pukul " . \Carbon\Carbon::parse($presensiHariIni->jam_datang)->format('H:i');
        } catch (\Exception $e) {
            $presensiMessage = "Anda sudah melakukan presensi hari ini.";
        }
    } else {
        $presensiMessage = "Anda belum melakukan presensi hari ini.";
    }


    // Get the current date
    $today = Carbon::today();

    try {
        $jumlahPasienHariIni = RegPeriksa::whereDate('tgl_registrasi', $today)->count();
    } catch (\Throwable $e) {
        Log::warning('Dashboard RegPeriksa count error: ' . $e->getMessage());
        $jumlahPasienHariIni = 0;
    }

    $suratMenyuratCards = $this->buildSuratMenyuratCards();

    try {
        $suratKlasifikasiChart = $this->suratChartService->klasifikasiBreakdown();
        $suratDepartemenChart = $this->suratChartService->departemenBreakdown();
    } catch (\Throwable $e) {
        Log::warning('Dashboard surat chart error: ' . $e->getMessage());
        $suratKlasifikasiChart = [];
        $suratDepartemenChart = [];
    }

    $tahunini = Carbon::now()->year;
    $totalHari = DB::table('pengajuan_libur')
        ->where('jenis_pengajuan_libur', 'Tahunan')
        ->where('nik', $nik)
        ->whereYear('tanggal_dibuat', $tahunini)
        ->sum('jumlah_hari');

    // Hitung jumlah dokter aktif dari database
    try {
        $jumlahDokter = Dokter::where(function($query) {
            $query->where('status', '!=', 'Tidak Aktif')
                  ->orWhereNull('status');
        })->count();
    } catch (\Exception $e) {
        // Fallback jika terjadi error
        $jumlahDokter = 0;
    }

    // Ambil agenda yang mengundang user (hari ini dan 7 hari ke depan)
    $agendaTerundang = Agenda::with(['pimpinan', 'notulenPegawai'])
        ->where(function ($query) use ($nik) {
            // Cek apakah user diundang (termasuk "all")
            $query->where(function ($q) use ($nik) {
                // Cek jika yang_terundang berisi "all" atau NIK user
                $q->whereRaw("JSON_CONTAINS(yang_terundang, ?)", [json_encode('all')])
                  ->orWhereRaw("JSON_CONTAINS(yang_terundang, ?)", [json_encode($nik)]);
            });
        })
        ->where(function ($query) {
            // Agenda yang akan datang (hari ini sampai 7 hari ke depan)
            $query->whereBetween('mulai', [Carbon::now()->startOfDay(), Carbon::now()->addDays(7)->endOfDay()])
                  // Atau agenda yang sedang berlangsung
                  ->orWhere(function ($q) {
                      $q->where('mulai', '<=', Carbon::now())
                        ->where(function ($q2) {
                            $q2->whereNull('akhir')
                               ->orWhere('akhir', '>=', Carbon::now());
                        });
                  });
        })
        ->orderBy('mulai', 'asc')
        ->limit(5) // Limit 5 agenda terdekat
        ->get()
        ->filter(fn ($agenda) => $agenda->userTerundang($nik))
        ->map(function ($agenda) use ($nik) {
            // Cek absensi: "Sudah Absen" hanya bila user benar-benar scan (status hadir). Record AUTO/tidak_hadir tidak dianggap sudah absen.
            $agendaId = (int) $agenda->id;
            $row = DB::selectOne(
                'SELECT status_kehadiran FROM absensi_agenda WHERE agenda_id = ? AND nik = ? LIMIT 1',
                [$agendaId, (string) $nik]
            );
            $agenda->status_kehadiran_agenda = $row ? ($row->status_kehadiran ?? null) : null; // hadir, tidak_hadir, ijin, cuti, dll
            $agenda->sudah_absen = $row && ($row->status_kehadiran === 'hadir');

            // Format waktu
            $mulai = Carbon::parse($agenda->mulai);
            $akhir = $agenda->akhir ? Carbon::parse($agenda->akhir) : null;
            $now = Carbon::now();
            
            // Tentukan status
            if ($now->lt($mulai)) {
                $agenda->status_label = 'Akan Datang';
                $agenda->status_class = 'info';
                $agenda->waktu_info = $mulai->format('d M Y H:i');
            } elseif ($akhir && $now->gt($akhir)) {
                $agenda->status_label = 'Selesai';
                $agenda->status_class = 'secondary';
                $agenda->waktu_info = $akhir->format('d M Y H:i');
            } else {
                $agenda->status_label = 'Sedang Berlangsung';
                $agenda->status_class = 'success';
                $agenda->waktu_info = 'Sekarang';
            }
            
            return $agenda;
        });

    // Pegawai yang ulang tahun hari ini (bulan + tanggal = hari ini, hanya pegawai aktif)
    $pegawaiUlangTahunHariIni = Pegawai::where('stts_aktif', 'AKTIF')
        ->whereNotNull('tgl_lahir')
        ->whereMonth('tgl_lahir', $today->month)
        ->whereDay('tgl_lahir', $today->day)
        ->orderBy('nama')
        ->get(['id', 'nik', 'nama', 'departemen', 'tgl_lahir']);

    return view('dashboard.index', compact(
        'totalPegawaiAktif', 'jumlahPasienHariIni',
        'presensiUser', 'presensiMessage', 'totalHari',
        'agendaTerundang', 'jumlahDokter',
        'pegawaiUlangTahunHariIni', 'suratMenyuratCards', 'dashboardErrorMessage',
        'suratKlasifikasiChart', 'suratDepartemenChart'
    ));
    }

    private function buildSuratMenyuratCards(bool $fallback = false): array
    {
        $cards = [
            [
                'label' => 'Surat Masuk',
                'route' => 'surat_masuk.index',
                'icon' => 'fa-inbox',
                'gradient' => 'bg-primary-gradient',
                'subtitle' => 'Total surat masuk',
            ],
            [
                'label' => 'Surat Keluar',
                'route' => 'surat_keluar.index',
                'icon' => 'fa-paper-plane',
                'gradient' => 'bg-info-gradient',
                'subtitle' => 'Total surat keluar',
            ],
            [
                'label' => 'Surat Edaran',
                'route' => 'surat_edaran.index',
                'icon' => 'fa-bullhorn',
                'gradient' => 'bg-warning-gradient',
                'subtitle' => 'Dokumen edaran',
            ],
            [
                'label' => 'Peraturan Direktur',
                'route' => 'peraturan_direktur.index',
                'icon' => 'fa-gavel',
                'gradient' => 'bg-success-gradient',
                'subtitle' => 'Dokumen PER',
            ],
            [
                'label' => 'Keputusan Direktur',
                'route' => 'keputusan_direktur.index',
                'icon' => 'fa-stamp',
                'gradient' => 'bg-danger-gradient',
                'subtitle' => 'Dokumen KEP',
            ],
            [
                'label' => 'SPO',
                'route' => 'spo.index',
                'icon' => 'fa-clipboard-list',
                'gradient' => 'bg-secondary-gradient',
                'subtitle' => 'Standar prosedur operasional',
            ],
        ];

        if ($fallback) {
            foreach ($cards as &$card) {
                $card['count'] = 0;
            }
            unset($card);

            return $cards;
        }

        try {
            $counts = [
                Surat::whereHas('verifikasi')->count(),
                Surat::whereNotNull('nik_pengirim')->count(),
                SuratEdaran::count(),
                PeraturanDirektur::count(),
                KeputusanDirektur::count(),
                Spo::count(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Dashboard surat menyurat count error: ' . $e->getMessage());
            $counts = array_fill(0, count($cards), 0);
        }

        foreach ($cards as $i => &$card) {
            $card['count'] = $counts[$i] ?? 0;
        }
        unset($card);

        return $cards;
    }

    public function getPegawaiBelumPresensi()
    {
        $today = now()->toDateString(); // Format: YYYY-MM-DD
        $currentMonth = now()->format('m'); // Format: MM
        $currentDayColumn = 'h' . now()->format('j'); // Format: h1, h2, ..., h31
        $shiftsPagi = [
            'Pagi', 'Pagi2', 'Pagi3', 'Pagi4', 'Pagi5', 'Pagi6', 'Pagi7', 'Pagi8', 'Pagi9', 'Pagi10',
            'Midle Pagi1', 'Midle Pagi2', 'Midle Pagi3', 'Midle Pagi4', 'Midle Pagi5', 'Midle Pagi6', 'Midle Pagi7', 'Midle Pagi8', 'Midle Pagi9', 'Midle Pagi10'
        ];

        // 1️⃣ Cari Pegawai yang Dijadwalkan Masuk Hari Ini
        $pegawaiDijadwalkan = JadwalPegawai::where('bulan', $currentMonth)
            ->whereIn($currentDayColumn, $shiftsPagi)
            ->pluck('id'); // Ambil ID pegawai yang masuk shift pagi hari ini

        // 2️⃣ Cari Pegawai yang Sudah Presensi Hari Ini
        $pegawaiSudahPresensi = TemporaryPresensi::whereDate('jam_datang', $today)
            ->whereIn('shift', $shiftsPagi)
            ->pluck('id'); // Ambil ID pegawai yang sudah absen

        // 3️⃣ Cari Pegawai yang Belum Presensi
        $pegawaiBelumPresensi = Pegawai::whereIn('id', $pegawaiDijadwalkan)
            ->whereNotIn('id', $pegawaiSudahPresensi)
            ->get();

        return response()->json($pegawaiBelumPresensi);
    }

}