<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Pegawai;
use App\Models\TemporaryPresensi;
use App\Models\RekapPresensi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MobileHomeController extends Controller
{
    public function index()
    {
        $nik = Auth::user()->username;

        // ===== Presensi ringkas (ambil yang hari ini) =====
        $pegawai = Pegawai::where('nik', $nik)->first();
        $presensiMessage = 'Anda belum melakukan presensi hari ini.';
        $presensiHariIni = null;
        $presensiUser = collect();

        if ($pegawai) {
            $presensiUser = TemporaryPresensi::where('id', $pegawai->id)
                ->orderBy('jam_datang', 'desc')
                ->limit(10) // Ambil 10 terakhir untuk tabel
                ->get();

            $presensiHariIni = $presensiUser->where('jam_datang', '>=', today())->first();

            if ($presensiHariIni) {
                try {
                    $presensiMessage = 'Anda sudah melakukan presensi pada pukul ' . Carbon::parse($presensiHariIni->jam_datang)->format('H:i');
                } catch (\Throwable $e) {
                    $presensiMessage = 'Anda sudah melakukan presensi hari ini.';
                }
            }
        }

        // ===== Sisa Cuti Tahunan =====
        $tahunIni = Carbon::now()->year;
        $totalHari = DB::table('pengajuan_libur')
            ->where('jenis_pengajuan_libur', 'Tahunan')
            ->where('nik', $nik)
            ->whereYear('tanggal_dibuat', $tahunIni)
            ->sum('jumlah_hari');
        $sisaCuti = 12 - (int)$totalHari;

        // ===== Data Chart Rekap Presensi Bulan Ini =====
        $bulanSekarang = Carbon::now()->format('m');
        $tahunSekarang = Carbon::now()->year;
        $bulanTahunPattern = $tahunSekarang . '-' . str_pad($bulanSekarang, 2, '0', STR_PAD_LEFT);

        $chartData = [
            'hadir' => 0,
            'sakit' => 0,
            'izin' => 0,
            'terlambat' => 0,
        ];

        if ($pegawai) {
            // Hitung hadir (ada jam_datang di bulan ini)
            $chartData['hadir'] = RekapPresensi::where('id', $pegawai->id)
                ->where('jam_datang', 'like', '%' . $bulanTahunPattern . '%')
                ->count();

            // Hitung terlambat (status mengandung "Terlambat")
            $chartData['terlambat'] = RekapPresensi::where('id', $pegawai->id)
                ->where('jam_datang', 'like', '%' . $bulanTahunPattern . '%')
                ->where(function ($q) {
                    $q->where('status', 'like', '%Terlambat%');
                })
                ->count();

            // TODO: Sakit & Izin perlu ambil dari tabel lain (pengajuan_libur dengan jenis tertentu)
            // Untuk sementara, kita set 0 atau bisa ambil dari pengajuan_libur jika ada mapping
        }

        // ===== Agenda terundang (hari ini s/d 7 hari) =====
        $agendaTerundang = Agenda::with(['pimpinan', 'notulenPegawai'])
            ->where(function ($query) use ($nik) {
                // Cek apakah user diundang (termasuk "all")
                $query->where(function ($q) use ($nik) {
                    $q->whereRaw("JSON_CONTAINS(yang_terundang, ?)", [json_encode('all')])
                        ->orWhereRaw("JSON_CONTAINS(yang_terundang, ?)", [json_encode($nik)]);
                });
            })
            ->where(function ($query) {
                $query->whereBetween('mulai', [Carbon::now()->startOfDay(), Carbon::now()->addDays(7)->endOfDay()])
                    ->orWhere(function ($q) {
                        $q->where('mulai', '<=', Carbon::now())
                            ->where(function ($q2) {
                                $q2->whereNull('akhir')
                                    ->orWhere('akhir', '>=', Carbon::now());
                            });
                    });
            })
            ->orderBy('mulai', 'asc')
            ->limit(5)
            ->get()
            ->filter(fn ($agenda) => $agenda->userTerundang($nik))
            ->map(function ($agenda) use ($nik) {
                $agenda->sudah_absen = $agenda->absensi()
                    ->where('nik', $nik)
                    ->exists();

                $mulai = Carbon::parse($agenda->mulai);
                $akhir = $agenda->akhir ? Carbon::parse($agenda->akhir) : null;
                $now = Carbon::now();

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

        return view('mobileui.home', compact(
            'pegawai',
            'presensiMessage',
            'presensiHariIni',
            'presensiUser',
            'agendaTerundang',
            'sisaCuti',
            'chartData'
        ));
    }
}

