<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pegawai;
use App\Models\RegPeriksa;
use App\Models\KamarInap;
use App\Models\TemporaryPresensi;
use App\Models\PemeriksaanRalan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\PengajuanLibur;
use App\Models\Agenda;
use App\Models\Dokter;
use App\Models\JadwalPegawai;
use App\Models\RekapPresensi;

class DashboardController extends Controller
{
    public function index()
{
    // Data untuk pegawai yang terlambat hari ini
    $topTerlambat = TemporaryPresensi::with('pegawai')  // Memuat relasi dengan tabel pegawai
        ->whereIn('status', ['Terlambat Toleransi', 'Terlambat I', 'Terlambat II'])  // Filter berdasarkan status
        ->whereDate('jam_datang', today())  // Data untuk hari ini
        ->orderBy(DB::raw("TIME_TO_SEC(STR_TO_DATE(keterlambatan, '%H:%i:%s'))"), 'desc')  // Urutkan berdasarkan keterlambatan terbesar
        ->limit(5)  // Batas 7 pegawai
        ->get();
            
    $topPegawaiRajin = PemeriksaanRalan::select('pegawai.nama as nama_pegawai', 'pemeriksaan_ralan.nip', DB::raw('COUNT(pemeriksaan_ralan.no_rawat) as jumlah_entri'))
    ->join('pegawai', 'pemeriksaan_ralan.nip', '=', 'pegawai.nik')  // Relasi dengan pegawai melalui nik
    ->where('pemeriksaan_ralan.tgl_perawatan', '>=', now()->subDays(30))  // Data dari 30 hari terakhir
    ->groupBy('pemeriksaan_ralan.nip', 'pegawai.nama')  // Tambahkan pegawai.nama ke dalam GROUP BY
    ->orderBy('jumlah_entri', 'desc')  // Urutkan berdasarkan jumlah entri terbanyak
    ->limit(10)  // Ambil 10 pegawai teratas
    ->get();
            
    // Ambil data jumlah pegawai per departemen dengan filter stts_aktif = 'AKTIF'
    $pegawaiPerDepartemen = Pegawai::select('departemen', \DB::raw('count(*) as total'))
        ->where('stts_aktif', 'AKTIF')
        ->groupBy('departemen')
        ->get();

   $nik = Auth::user()->username;
    $pegawai = Pegawai::where('nik', $nik)->first();

    // Pastikan $presensiUser didefinisikan sebagai collection kosong
    $presensiUser = collect();

    if ($pegawai) {
        // Ambil data presensi jika pegawai ditemukan
        $presensiUser = TemporaryPresensi::where('id', $pegawai->id)
            ->orderBy('jam_datang', 'desc')
            ->get();
    }

    // Cek apakah ada presensi hari ini
    $presensiHariIni = $presensiUser->where('jam_datang', '>=', today())->first();

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


    // Siapkan data untuk chart per departemen
    $totalDepartemen = $pegawaiPerDepartemen->sum('total');
    $departemen = $pegawaiPerDepartemen->map(function($item) use ($totalDepartemen) {
        $percentage = ($item->total / $totalDepartemen) * 100;
        return [
            'x' => $item->departemen,
            'y' => $item->total,
            'label' => "{$item->total} (" . number_format($percentage, 2) . "%)"
        ];
    });
    $jumlahPegawai = $pegawaiPerDepartemen->pluck('total');

    // Ambil data jumlah pegawai berdasarkan bidang dengan filter stts_aktif = 'AKTIF'
    $pegawaiPerBidang = Pegawai::select('bidang', \DB::raw('count(*) as total'))
        ->where('stts_aktif', 'AKTIF')
        ->groupBy('bidang')
        ->get();

    // Siapkan data untuk pie chart
    $totalBidang = $pegawaiPerBidang->sum('total');
    $bidang = $pegawaiPerBidang->map(function($item) use ($totalBidang) {
        $percentage = ($item->total / $totalBidang) * 100;
        return [
            'x' => $item->bidang,
            'y' => $item->total,
            'label' => "{$item->total} (" . number_format($percentage, 2) . "%)"
        ];
    });
    $jumlahPerBidang = $pegawaiPerBidang->pluck('total');
    
    // Get the current date
    $today = Carbon::today();
    $yesterday = Carbon::yesterday();

    // Query to count patients examined today
    $jumlahPasienHariIni = RegPeriksa::whereDate('tgl_registrasi', $today)->count();

    // Query to count patients examined yesterday
    $jumlahPasienKemarin = RegPeriksa::whereDate('tgl_registrasi', $yesterday)->count();

    // Hitung pertumbuhan pasien
    if ($jumlahPasienKemarin > 0) {
        $pertumbuhanPasien = $jumlahPasienHariIni - $jumlahPasienKemarin;
    } else {
        // Jika tidak ada pasien kemarin, pertumbuhan = jumlah pasien hari ini
        $pertumbuhanPasien = $jumlahPasienHariIni;
    }
    
    // Pastikan pertumbuhan tidak negatif jika ada error
    if ($pertumbuhanPasien < 0) {
        $pertumbuhanPasien = 0;
    }
    
    $jumlahPasienRawatInap = KamarInap::where('stts_pulang', '-')
        ->where('lama', '<', 6)
        ->count();
    
    $jumlahPasienIGD = RegPeriksa::whereDate('tgl_registrasi', $today)
        ->where('kd_poli', 'IGDK')
        ->count();
    
    // Ambil pegawai yang ulang tahun dalam rentang 10 hari ke depan
    $tanggalSekarang = Carbon::now();
    $tanggalAkhir = Carbon::now()->addDays(10);
    $pegawaiUlangTahun = Pegawai::where('stts_aktif', 'AKTIF')
        ->whereRaw("DATE_FORMAT(tgl_lahir, '%m-%d') BETWEEN ? AND ?", [
            $tanggalSekarang->format('m-d'),
            $tanggalAkhir->format('m-d')
        ])
        ->get()
        ->map(function ($pegawai) use ($tanggalSekarang) {
            $tanggalLahir = Carbon::parse($pegawai->tgl_lahir)->year($tanggalSekarang->year);
            $hariIni = $tanggalSekarang->isSameDay($tanggalLahir);
            $sisaHari = $tanggalSekarang->diffInDays($tanggalLahir, false);

            if ($hariIni) {
                $pegawai->status = 'Selamat Ulang Tahun';
                $pegawai->sisaHari = 0;
            } elseif ($sisaHari > 0) {
                $pegawai->status = "Ulang tahun {$sisaHari} hari lagi";
                $pegawai->sisaHari = $sisaHari;
            }

            return $pegawai;
        })
        ->filter(function ($pegawai) {
            return $pegawai->status !== null;
        })
        ->sortBy('sisaHari');

    // Ranking Tim Paling Rajin dan Paling Sering Terlambat
    $bulanSekarang = Carbon::now()->format('m');
    $tahunSekarang = Carbon::now()->year;
    $bulanTahunPattern = $tahunSekarang . '-' . str_pad($bulanSekarang, 2, '0', STR_PAD_LEFT);
    
    // Hitung jumlah hari dalam bulan
    $jumlahHari = Carbon::create($tahunSekarang, $bulanSekarang, 1)->daysInMonth;
    
    // Ambil semua pegawai aktif
    $semuaPegawai = Pegawai::where('stts_aktif', 'AKTIF')->get();
    
    $rankingData = [];
    
    foreach ($semuaPegawai as $pegawai) {
        // Hitung wajib masuk berdasarkan logika kompleks
        $wajibMasuk = $this->hitungWajibMasuk($pegawai, $bulanSekarang, $tahunSekarang, $jumlahHari);
        
        // Query Hadir: COUNT dengan LIKE pattern (sesuai query asli)
        $kehadiran = RekapPresensi::where('id', $pegawai->id)
            ->where('jam_datang', 'like', '%' . $bulanTahunPattern . '%')
            ->count();
        
        // Query Keterlambatan: SUM TIME_TO_SEC dan format kembali ke HH:MM:SS
        // Sesuai query asli: SUM(TIME_TO_SEC(keterlambatan))
        $totalKeterlambatanDetik = RekapPresensi::where('id', $pegawai->id)
            ->where('jam_datang', 'like', '%' . $bulanTahunPattern . '%')
            ->whereNotNull('keterlambatan')
            ->where('keterlambatan', '!=', '00:00:00')
            ->selectRaw('COALESCE(SUM(TIME_TO_SEC(keterlambatan)), 0) as total_detik')
            ->value('total_detik') ?? 0;
        
        // Format total keterlambatan ke HH:MM:SS sesuai query asli
        // round((sum - mod(sum,3600))/3600) : round((mod(sum,3600) - mod(sum,60))/60) : mod(sum,60)
        $jam = floor($totalKeterlambatanDetik / 3600);
        $sisaDetik = $totalKeterlambatanDetik % 3600;
        $menit = round(($sisaDetik - ($sisaDetik % 60)) / 60);
        $detik = $totalKeterlambatanDetik % 60;
        $totalKeterlambatanFormatted = sprintf('%02d:%02d:%02d', $jam, $menit, $detik);
        
        // Query Durasi Kerja: SUM TIME_TO_SEC durasi dan format kembali ke HH:MM:SS
        $totalDurasiDetik = RekapPresensi::where('id', $pegawai->id)
            ->where('jam_datang', 'like', '%' . $bulanTahunPattern . '%')
            ->whereNotNull('durasi')
            ->selectRaw('COALESCE(SUM(TIME_TO_SEC(durasi)), 0) as total_detik')
            ->value('total_detik') ?? 0;
        
        // Format total durasi ke HH:MM:SS sesuai query asli
        $jamDurasi = floor($totalDurasiDetik / 3600);
        $sisaDetikDurasi = $totalDurasiDetik % 3600;
        $menitDurasi = round(($sisaDetikDurasi - ($sisaDetikDurasi % 60)) / 60);
        $detikDurasi = $totalDurasiDetik % 60;
        $totalDurasiFormatted = sprintf('%02d:%02d:%02d', $jamDurasi, $menitDurasi, $detikDurasi);
        
        // Hitung jumlah terlambat berdasarkan status: Terlambat Toleransi, Terlambat I, Terlambat II
        $jumlahTerlambatToleransi = RekapPresensi::where('id', $pegawai->id)
            ->where('jam_datang', 'like', '%' . $bulanTahunPattern . '%')
            ->where('status', 'like', '%Terlambat Toleransi%')
            ->count();
        
        $jumlahTerlambatI = RekapPresensi::where('id', $pegawai->id)
            ->where('jam_datang', 'like', '%' . $bulanTahunPattern . '%')
            ->where('status', 'like', '%Terlambat I%')
            ->count();
        
        $jumlahTerlambatII = RekapPresensi::where('id', $pegawai->id)
            ->where('jam_datang', 'like', '%' . $bulanTahunPattern . '%')
            ->where('status', 'like', '%Terlambat II%')
            ->count();
        
        $jumlahTerlambat = $jumlahTerlambatToleransi + $jumlahTerlambatI + $jumlahTerlambatII;
        
        // Hitung persentase kehadiran
        $persenKehadiran = $wajibMasuk > 0 ? ($kehadiran / $wajibMasuk) * 100 : 0;
        
        // Hitung ranking score untuk Tim Paling Rajin
        // Score = (persentase kehadiran * durasi kerja) - (keterlambatan * 1000)
        // Semakin tinggi score = semakin rajin (kehadiran tinggi, durasi kerja tinggi, keterlambatan rendah)
        $rankingScoreRajin = ($persenKehadiran * $totalDurasiDetik) - ($totalKeterlambatanDetik * 1000);
        
        // Hitung ranking score untuk Tim Paling Sering Terlambat (tetap pakai durasi keterlambatan)
        // Score = total_keterlambatan_detik (semakin tinggi = semakin sering terlambat)
        $rankingScoreTerlambat = $totalKeterlambatanDetik;
        
        $rankingData[] = [
            'pegawai_id' => $pegawai->id,
            'nama' => $pegawai->nama,
            'departemen' => $pegawai->departemen,
            'wajib_masuk' => $wajibMasuk,
            'kehadiran' => $kehadiran,
            'tidak_hadir' => $wajibMasuk - $kehadiran,
            'persen_kehadiran' => round($persenKehadiran, 2),
            'jumlah_terlambat' => $jumlahTerlambat,
            'total_keterlambatan_detik' => $totalKeterlambatanDetik,
            'total_keterlambatan_formatted' => $totalKeterlambatanFormatted,
            'total_durasi_detik' => $totalDurasiDetik,
            'total_durasi_formatted' => $totalDurasiFormatted,
            'ranking_score_rajin' => $rankingScoreRajin,
            'ranking_score_terlambat' => $rankingScoreTerlambat,
        ];
    }
    
    // Ranking Paling Rajin: berdasarkan score rajin tertinggi
    // Score = (persentase kehadiran * durasi kerja) - (keterlambatan * 1000)
    // Semakin tinggi score = semakin rajin
    $timPalingRajin = collect($rankingData)
        ->filter(function($item) {
            // Filter: wajib masuk > 0, kehadiran > 0, dan tidak pernah terlambat
            // (total_keterlambatan_detik == 0 dan jumlah_terlambat == 0)
            return $item['wajib_masuk'] > 0 
                && $item['kehadiran'] > 0 
                && $item['total_keterlambatan_detik'] == 0 
                && $item['jumlah_terlambat'] == 0;
        })
        ->sortByDesc('ranking_score_rajin') // Sort berdasarkan score rajin tertinggi
        ->take(10)
        ->values();
    
    // Ranking Paling Sering Terlambat: berdasarkan durasi keterlambatan tertinggi
    $timPalingSeringTerlambat = collect($rankingData)
        ->filter(function($item) {
            // Filter: wajib masuk > 0, kehadiran > 0, dan total keterlambatan > 0 (bukan 00:00:00)
            return $item['wajib_masuk'] > 0 
                && $item['kehadiran'] > 0 
                && $item['total_keterlambatan_detik'] > 0; // Harus ada keterlambatan
        })
        ->sortByDesc('total_keterlambatan_detik') // Sort berdasarkan durasi keterlambatan tertinggi
        ->take(10)
        ->values();
        
        $tahunini = Carbon::now()->year; // Tahun saat ini
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
        ->filter(function ($agenda) use ($nik) {
            // Double check: pastikan user benar-benar terundang
            $terundang = is_array($agenda->yang_terundang) 
                ? $agenda->yang_terundang 
                : (json_decode($agenda->yang_terundang, true) ?? []);
            
            // Cek apakah semua pegawai aktif terundang
            $semuaNikAktif = Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray();
            $isAll = false;
            
            if (is_array($terundang)) {
                $intersect = array_intersect($semuaNikAktif, $terundang);
                $isAll = count($intersect) === count($semuaNikAktif) && count($terundang) === count($semuaNikAktif);
            }
            
            return in_array('all', $terundang) || $isAll || in_array($nik, $terundang);
        })
        ->map(function ($agenda) use ($nik) {
            // Cek apakah user sudah absen
            $agenda->sudah_absen = \App\Models\AbsensiAgenda::where('agenda_id', $agenda->id)
                ->where('nik', $nik)
                ->exists();
            
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

    // Jangan lupa untuk menambahkan 'pertumbuhanPasien' ke compact()
    return view('dashboard.index', compact(
        'departemen', 'jumlahPegawai', 'pegawaiUlangTahun', 'bidang', 
        'jumlahPerBidang', 'jumlahPasienHariIni', 'pertumbuhanPasien',
        'jumlahPasienRawatInap', 'jumlahPasienIGD', 'topTerlambat', 
        'topPegawaiRajin', 'presensiUser', 'presensiMessage', 'totalHari',
        'agendaTerundang', 'jumlahDokter', 'timPalingRajin', 'timPalingSeringTerlambat'
    ));
}
    /**
     * Hitung wajib masuk berdasarkan logika kompleks dari sistem lama
     */
    private function hitungWajibMasuk($pegawai, $bulan, $tahun, $jumlahHari)
    {
        $wajibMasukValue = $pegawai->wajibmasuk ?? '0';
        
        // Hitung libur akhad (Sabtu + Minggu) dalam bulan
        $liburAkhad = 0;
        $liburHariRaya = 0; // TODO: Hitung libur hari raya jika ada data
        
        for ($day = 1; $day <= $jumlahHari; $day++) {
            $tanggal = Carbon::create($tahun, $bulan, $day);
            if ($tanggal->isWeekend()) {
                $liburAkhad++;
            }
        }
        
        // Logika sesuai query asli
        if ($wajibMasukValue == '-1') {
            return 0;
        } elseif ($wajibMasukValue == '-2') {
            return $jumlahHari - 4;
        } elseif ($wajibMasukValue == '-3') {
            return $jumlahHari - 2 - $liburHariRaya;
        } elseif ($wajibMasukValue == '-4') {
            return $jumlahHari - $liburAkhad;
        } elseif ($wajibMasukValue == '-5') {
            // Query jadwal pegawai - hitung dari jadwal
            $jadwalPegawai = JadwalPegawai::where('id', $pegawai->id)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->first();
            
            $wajibMasuk = 0;
            if ($jadwalPegawai) {
                for ($i = 1; $i <= 31; $i++) {
                    $field = 'h' . $i;
                    if (!empty($jadwalPegawai->$field)) {
                        $wajibMasuk++;
                    }
                }
            }
            return $wajibMasuk;
        } elseif ($wajibMasukValue != '0' && $wajibMasukValue != '') {
            return (int)$wajibMasukValue;
        } else {
            // Default: jumlahhari - liburakhad - liburhariraya
            return $jumlahHari - $liburAkhad - $liburHariRaya;
        }
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