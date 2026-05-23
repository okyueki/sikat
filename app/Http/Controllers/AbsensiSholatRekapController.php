<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSholat;
use App\Models\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Backend: rekap absensi sholat — siapa saja yang sholat dalam periode bulan/tahun.
 */
class AbsensiSholatRekapController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);
        $start = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $records = AbsensiSholat::whereBetween('waktu_absen', [$start, $end])
            ->orderBy('waktu_absen')
            ->get();

        $byNik = $records->groupBy('nik');
        $niks = $byNik->keys()->toArray();
        $pegawaiMap = [];
        if (!empty($niks)) {
            $pegawaiMap = Pegawai::whereIn('nik', $niks)->get()->keyBy('nik');
        }

        $jenisList = ['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya', 'sunnah'];
        $rekap = [];
        foreach ($byNik as $nik => $items) {
            $pegawai = $pegawaiMap->get($nik);
            $perJenis = $items->groupBy('jenis_sholat')->map->count();
            $tanggalList = $items->map(function ($r) {
                return Carbon::parse($r->waktu_absen)->format('Y-m-d');
            })->unique()->sort()->values()->toArray();
            $detail = $items->sortBy('waktu_absen')->map(function ($r) {
                return [
                    'tanggal' => Carbon::parse($r->waktu_absen)->format('d M Y'),
                    'jam' => Carbon::parse($r->waktu_absen)->format('H:i'),
                    'jenis_sholat' => $r->jenis_sholat ?? 'sunnah',
                ];
            })->values()->toArray();
            $rekap[] = [
                'nik' => $nik,
                'nama' => $pegawai ? $pegawai->nama : '-',
                'departemen' => $pegawai ? ($pegawai->departemen ?? '-') : '-',
                'jabatan' => $pegawai ? ($pegawai->jbtn ?? '-') : '-',
                'total' => $items->count(),
                'subuh' => $perJenis->get('subuh', 0),
                'dzuhur' => $perJenis->get('dzuhur', 0),
                'ashar' => $perJenis->get('ashar', 0),
                'maghrib' => $perJenis->get('maghrib', 0),
                'isya' => $perJenis->get('isya', 0),
                'sunnah' => $perJenis->get('sunnah', 0),
                'tanggal_list' => $tanggalList,
                'detail' => $detail,
            ];
        }

        // Urutkan berdasarkan total descending, lalu nama
        usort($rekap, function ($a, $b) {
            if ($b['total'] !== $a['total']) {
                return $b['total'] - $a['total'];
            }
            return strcmp($a['nama'], $b['nama']);
        });

        return view('absensi_sholat.rekap', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'rekap' => $rekap,
            'jenisList' => $jenisList,
        ]);
    }
}
