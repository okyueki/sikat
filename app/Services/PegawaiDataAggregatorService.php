<?php

namespace App\Services;

use App\Models\AbsensiAgenda;
use App\Models\AbsensiSholat;
use App\Models\BudayaKerja;
use App\Models\Pegawai;
use App\Models\RekapPresensi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * PegawaiDataAggregatorService
 *
 * Service untuk mengaggregasi semua data kehadiran pegawai dari berbagai sumber:
 * 1. Presensi Harian (rekap_presensi)
 * 2. Absensi Sholat (absensi_sholat)
 * 3. Budaya Kerja (budaya_kerja)
 * 4. Absensi Agenda (absensi_agenda)
 *
 * Usage:
 *   PegawaiDataAggregatorService::aggregate($startDate, $endDate, $nikList = null)
 *   PegawaiDataAggregatorService::aggregateSingle($nik, $startDate, $endDate)
 *   PegawaiDataAggregatorService::getAggregateByDepartemen($startDate, $endDate)
 */
class PegawaiDataAggregatorService
{
    /**
     * Agregasi data untuk semua pegawai aktif
     *
     * @param string $startDate Format: Y-m-d
     * @param string $endDate Format: Y-m-d
     * @param array|null $nikList Filter NIK tertentu (null = semua pegawai aktif)
     * @param string|null $departemen Filter departemen tertentu
     * @return \Illuminate\Support\Collection
     */
    public static function aggregate(
        string $startDate,
        string $endDate,
        ?array $nikList = null,
        ?string $departemen = null
    ): \Illuminate\Support\Collection {
        // Ambil daftar pegawai
        $query = Pegawai::where('stts_aktif', 'AKTIF')
            ->where(function ($q) {
                $q->where('stts_kerja', '!=', 'MIT')
                  ->where('stts_kerja', '!=', 'MITRA');
            });

        if ($nikList) {
            $query->whereIn('nik', $nikList);
        }

        if ($departemen) {
            $query->where('departemen', $departemen);
        }

        $pegawaiList = $query->select('id', 'nik', 'nama', 'departemen', 'jbtn')
            ->get()
            ->keyBy('nik');

        if ($pegawaiList->isEmpty()) {
            return collect();
        }

        $nikArray = $pegawaiList->pluck('nik')->toArray();
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // Hitung jumlah hari kerja dalam periode
        $hariKerja = self::countHariKerja($start, $end);

        return collect($nikArray)->map(function ($nik) use ($pegawaiList, $start, $end, $hariKerja) {
            $pegawai = $pegawaiList->get($nik);
            return self::buildEmployeeData($pegawai, $start, $end, $hariKerja);
        });
    }

    /**
     * Agregasi data untuk satu pegawai
     *
     * @param string $nik
     * @param string $startDate
     * @param string $endDate
     * @return array|null
     */
    public static function aggregateSingle(string $nik, string $startDate, string $endDate): ?array
    {
        $pegawai = Pegawai::where('nik', $nik)->first();
        if (!$pegawai) {
            return null;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        $hariKerja = self::countHariKerja($start, $end);

        return self::buildEmployeeData($pegawai, $start, $end, $hariKerja);
    }

    /**
     * Agregasi data per departemen
     *
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Support\Collection
     */
    public static function getAggregateByDepartemen(string $startDate, string $endDate): \Illuminate\Support\Collection
    {
        $aggregated = self::aggregate($startDate, $endDate);

        return $aggregated->groupBy('departemen')->map(function ($employees, $dept) {
            $count = $employees->count();
            $sum = function ($key) use ($employees) {
                return $employees->sum($key);
            };

            return [
                'departemen' => $dept,
                'total_pegawai' => $count,
                'avg_presensi_hadir' => $count > 0 ? round($sum('presensi.total_hadir') / $count, 1) : 0,
                'avg_presensi_tepat_waktu' => $count > 0 ? round($sum('presensi.tepat_waktu') / $count, 1) : 0,
                'avg_sholat_total' => $count > 0 ? round($sum('sholat.total') / $count, 1) : 0,
                'avg_budaya_nilai' => $count > 0 ? round($sum('budaya.rata_rata') / $count, 1) : 0,
                'avg_agenda_hadir' => $count > 0 ? round($sum('agenda.hadir') / $count, 1) : 0,
                'overall_score_avg' => $count > 0 ? round($sum('overall_score') / $count, 2) : 0,
            ];
        })->values()->sortByDesc('overall_score_avg');
    }

    /**
     * Build complete data untuk satu pegawai
     */
    private static function buildEmployeeData($pegawai, Carbon $start, Carbon $end, int $hariKerja): array
    {
        $nik = $pegawai->nik;

        return [
            'pegawai_id' => $pegawai->id,
            'nik' => $nik,
            'nama' => $pegawai->nama,
            'departemen' => $pegawai->departemen ?? '-',
            'jabatan' => $pegawai->jbtn ?? '-',
            'periode_start' => $start->toDateString(),
            'periode_end' => $end->toDateString(),
            'hari_kerja' => $hariKerja,

            // Presensi Data
            'presensi' => self::getPresensiData($pegawai->id, $start, $end, $hariKerja),

            // Absensi Sholat Data
            'sholat' => self::getSholatData($nik, $start, $end, $hariKerja),

            // Budaya Kerja Data
            'budaya' => self::getBudayaData($nik, $start, $end),

            // Absensi Agenda Data
            'agenda' => self::getAgendaData($nik, $start, $end),

            // Overall Score (akan dihitung oleh IndicatorCalculator)
            'overall_score' => 0,
            'status_keterlibatan' => 'aktif',
        ];
    }

    /**
     * Ambil data presensi dari rekap_presensi (server_74)
     */
    private static function getPresensiData(int $pegawaiId, Carbon $start, Carbon $end, int $hariKerja): array
    {
        $data = RekapPresensi::on('server_74')
            ->where('id', $pegawaiId)
            ->whereDate('jam_datang', '>=', $start->toDateString())
            ->whereDate('jam_datang', '<=', $end->toDateString())
            ->get();

        $statusTepatWaktu = ['Tepat Waktu', 'Tepat Waktu & PSW'];

        $totalHadir = $data->count();
        $tepatWaktu = $data->whereIn('status', $statusTepatWaktu)->count();
        $terlambat = $totalHadir - $tepatWaktu;

        // Hitung total menit terlambat
        $totalMenitTerlambat = 0;
        foreach ($data as $row) {
            if ($row->keterlambatan) {
                // Format: HH:MM:SS
                $parts = explode(':', $row->keterlambatan);
                if (count($parts) >= 2) {
                    $totalMenitTerlambat += (int)$parts[0] * 60 + (int)$parts[1];
                }
            }
        }

        return [
            'total_hadir' => $totalHadir,
            'tepat_waktu' => $tepatWaktu,
            'terlambat' => $terlambat,
            'total_menit_terlambat' => $totalMenitTerlambat,
            'tidak_hadir' => $hariKerja - $totalHadir,
            'rate_kehadiran' => $hariKerja > 0 ? round(($totalHadir / $hariKerja) * 100, 1) : 0,
            'rate_tepat_waktu' => $totalHadir > 0 ? round(($tepatWaktu / $totalHadir) * 100, 1) : 0,
            'list' => $data->map(function ($row) {
                return [
                    'tanggal' => $row->jam_datang ? Carbon::parse($row->jam_datang)->format('Y-m-d') : null,
                    'jam_datang' => $row->jam_datang ? Carbon::parse($row->jam_datang)->format('H:i') : null,
                    'jam_pulang' => $row->jam_pulang ? Carbon::parse($row->jam_pulang)->format('H:i') : null,
                    'status' => $row->status,
                    'keterlambatan' => $row->keterlambatan,
                ];
            })->toArray(),
        ];
    }

    /**
     * Ambil data absensi sholat dari absensi_sholat
     */
    private static function getSholatData(string $nik, Carbon $start, Carbon $end, int $hariKerja): array
    {
        $data = AbsensiSholat::where('nik', $nik)
            ->whereBetween('waktu_absen', [$start, $end])
            ->get();

        $jenisSholat = ['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya', 'sunnah'];
        $byJenis = [];

        foreach ($jenisSholat as $jenis) {
            $count = $data->where('jenis_sholat', $jenis)->count();
            $byJenis[$jenis] = [
                'jumlah' => $count,
                'persen' => $hariKerja > 0 ? round(($count / $hariKerja) * 100, 1) : 0,
            ];
        }

        $totalSholat = $data->count();
        $kesempatanSholat = $hariKerja * 5; // 5 jenis sholat per hari

        return [
            'total' => $totalSholat,
            'by_jenis' => $byJenis,
            'rate' => $kesempatanSholat > 0 ? round(($totalSholat / $kesempatanSholat) * 100, 1) : 0,
            'list' => $data->map(function ($row) {
                return [
                    'tanggal' => $row->waktu_absen ? Carbon::parse($row->waktu_absen)->format('Y-m-d') : null,
                    'waktu_absen' => $row->waktu_absen ? Carbon::parse($row->waktu_absen)->format('H:i') : null,
                    'jenis_sholat' => $row->jenis_sholat,
                ];
            })->toArray(),
        ];
    }

    /**
     * Ambil data budaya kerja dari budaya_kerja
     */
    private static function getBudayaData(string $nik, Carbon $start, Carbon $end): array
    {
        $data = BudayaKerja::where('nik_pegawai', $nik)
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->get();

        $totalPenilaian = $data->count();
        $totalNilai = $data->sum('total_nilai');
        $rataRata = $totalPenilaian > 0 ? round($totalNilai / $totalPenilaian, 2) : 0;
        $maxNilai = 11;

        // Hitung item yang sering tidak sesuai
        $items = ['sepatu', 'sabuk', 'make_up', 'minyak_wangi', 'jilbab', 'kuku', 'baju', 'celana', 'name_tag', 'perhiasan', 'kaos_kaki'];
        $itemTidakSesuai = [];

        foreach ($items as $item) {
            $countTidakSesuai = $data->where($item, 0)->count();
            if ($countTidakSesuai > 0) {
                $itemTidakSesuai[$item] = [
                    'jumlah' => $countTidakSesuai,
                    'persen' => $totalPenilaian > 0 ? round(($countTidakSesuai / $totalPenilaian) * 100, 1) : 0,
                ];
            }
        }

        return [
            'total_penilaian' => $totalPenilaian,
            'total_nilai' => $totalNilai,
            'rata_rata' => $rataRata,
            'nilai_tertinggi' => $data->max('total_nilai') ?? 0,
            'nilai_terendah' => $data->min('total_nilai') ?? 0,
            'max_nilai' => $maxNilai,
            'rate' => $totalPenilaian > 0 ? round(($rataRata / $maxNilai) * 100, 1) : 0,
            'item_tidak_sesuai' => $itemTidakSesuai,
            'list' => $data->map(function ($row) {
                return [
                    'tanggal' => $row->tanggal,
                    'jam' => $row->jam,
                    'shift' => $row->shift,
                    'total_nilai' => $row->total_nilai,
                    'petugas' => $row->petugas,
                ];
            })->toArray(),
        ];
    }

    /**
     * Ambil data absensi agenda dari absensi_agenda
     */
    private static function getAgendaData(string $nik, Carbon $start, Carbon $end): array
    {
        // Ambil agenda dalam periode
        $agendas = \App\Models\Agenda::whereDate('mulai', '>=', $start->toDateString())
            ->whereDate('mulai', '<=', $end->toDateString())
            ->get(['id', 'yang_terundang']);

        // Hitung yang diundang
        $diundang = 0;
        $byAgama = [];

        foreach ($agendas as $agenda) {
            $terundang = is_string($agenda->yang_terundang)
                ? json_decode($agenda->yang_terundang, true) ?? []
                : $agenda->yang_terundang;

            $isAll = is_array($terundang) && in_array('all', $terundang);

            if ($isAll || (is_array($terundang) && in_array($nik, $terundang))) {
                $diundang++;
                $byAgama[$agenda->id] = true;
            }
        }

        // Ambil absensi agenda
        $absensiAgendaIds = array_keys($byAgama);
        $absensiData = [];

        if (!empty($absensiAgendaIds)) {
            $absensiData = AbsensiAgenda::whereIn('agenda_id', $absensiAgendaIds)
                ->where('nik', $nik)
                ->get()
                ->keyBy('agenda_id');
        }

        $hadir = 0;
        $ijin = 0;
        $cuti = 0;
        $sakit = 0;
        $berhalangan = 0;
        $tidakHadir = 0;

        foreach ($byAgama as $agendaId => $_) {
            $absen = $absensiData->get($agendaId);
            if ($absen) {
                switch ($absen->status_kehadiran) {
                    case 'hadir': $hadir++; break;
                    case 'ijin': $ijin++; break;
                    case 'cuti': $cuti++; break;
                    case 'sakit': $sakit++; break;
                    case 'berhalangan': $berhalangan++; break;
                    case 'tidak_hadir': $tidakHadir++; break;
                }
            } else {
                $tidakHadir++;
            }
        }

        return [
            'diundang' => $diundang,
            'hadir' => $hadir,
            'ijin' => $ijin,
            'cuti' => $cuti,
            'sakit' => $sakit,
            'berhalangan' => $berhalangan,
            'tidak_hadir' => $tidakHadir,
            'rate_kehadiran' => $diundang > 0 ? round((($hadir + $ijin + $cuti) / $diundang) * 100, 1) : 0,
        ];
    }

    /**
     * Hitung jumlah hari kerja (Senin-Jumat, exclude Minggu)
     */
    private static function countHariKerja(Carbon $start, Carbon $end): int
    {
        $count = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->dayOfWeekIso !== 7) { // Bukan Minggu
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Get summary statistics untuk semua pegawai
     */
    public static function getSummaryStats($aggregatedData): array
    {
        $totalPegawai = $aggregatedData->count();

        if ($totalPegawai === 0) {
            return [
                'total_pegawai' => 0,
                'avg_presensi_rate' => 0,
                'avg_sholat_rate' => 0,
                'avg_budaya_rate' => 0,
                'avg_agenda_rate' => 0,
                'overall_score_avg' => 0,
            ];
        }

        return [
            'total_pegawai' => $totalPegawai,
            'avg_presensi_rate' => round($aggregatedData->avg('presensi.rate_kehadiran'), 1),
            'avg_sholat_rate' => round($aggregatedData->avg('sholat.rate'), 1),
            'avg_budaya_rate' => round($aggregatedData->avg('budaya.rate'), 1),
            'avg_agenda_rate' => round($aggregatedData->avg('agenda.rate_kehadiran'), 1),
            'overall_score_avg' => round($aggregatedData->avg('overall_score'), 2),
        ];
    }
}