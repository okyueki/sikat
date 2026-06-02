<?php

namespace App\Services;

use App\Models\Pegawai;
use App\Models\PegawaiDataCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * PegawaiAnalyticsService
 *
 * Service untuk analisis data pegawai:
 * 1. Trend Analysis - Trenbulan ke bulan
 * 2. Ranking - Peringkat antar pegawai
 * 3. Anomaly Detection - Ketidakbiasaaan data
 * 4. Department Analysis - Analisis per departemen
 *
 * Usage:
 *   PegawaiAnalyticsService::getTrend($nik, $months = 6)
 *   PegawaiAnalyticsService::getRanking($bulan, $tahun)
 *   PegawaiAnalyticsService::detectAnomalies($bulan, $tahun)
 *   PegawaiAnalyticsService::getDepartmentAnalysis($bulan, $tahun)
 */
class PegawaiAnalyticsService
{
    /**
     * Get trend data untuk satu pegawai
     *
     * @param string $nik
     * @param int $months Jumlah bulan ke belakang
     * @return array
     */
    public static function getTrend(string $nik, int $months = 6): array
    {
        $startDate = Carbon::now()->subMonths($months)->startOfMonth()->toDateString();

        $data = PegawaiDataCache::where('nik', $nik)
            ->where('periode_type', 'bulanan')
            ->where('periode_start', '>=', $startDate)
            ->orderBy('periode_start', 'asc')
            ->get();

        if ($data->isEmpty()) {
            return [
                'nik' => $nik,
                'nama' => Pegawai::where('nik', $nik)->value('nama') ?? '-',
                'trend' => [],
                'analysis' => [
                    'direction' => 'unknown',
                    'message' => 'Tidak ada data untuk analisis trend',
                ],
            ];
        }

        $trendData = $data->map(function ($row) {
            return [
                'bulan' => Carbon::parse($row->periode_start)->format('M Y'),
                'periode' => $row->periode_start,
                'overall_score' => (float) $row->overall_score,
                'presensi_rate' => (float) $row->presensi_rate_kehadiran,
                'sholat_rate' => (float) $row->sholat_rate,
                'budaya_rate' => (float) $row->budaya_rate,
                'agenda_rate' => (float) $row->agenda_rate_kehadiran,
            ];
        })->toArray();

        // Calculate trend direction
        $firstScore = $trendData[0]['overall_score'] ?? 0;
        $lastScore = end($trendData)['overall_score'] ?? 0;
        $change = $lastScore - $firstScore;

        $direction = 'stable';
        $message = 'Skor relatif stabil';

        if ($change > 5) {
            $direction = 'improving';
            $message = 'Skor cenderung meningkat';
        } elseif ($change < -5) {
            $direction = 'declining';
            $message = 'Skor cenderung menurun';
        }

        // Calculate trend percentage
        $trendPercent = $firstScore > 0 ? round(($change / $firstScore) * 100, 1) : 0;

        return [
            'nik' => $nik,
            'nama' => $data->first()->nama ?? '-',
            'departemen' => $data->first()->departemen ?? '-',
            'trend' => $trendData,
            'analysis' => [
                'direction' => $direction,
                'change' => round($change, 2),
                'change_percent' => $trendPercent,
                'message' => $message,
                'first_month' => $trendData[0]['bulan'] ?? null,
                'last_month' => end($trendData)['bulan'] ?? null,
                'first_score' => $firstScore,
                'last_score' => $lastScore,
            ],
        ];
    }

    /**
     * Get ranking semua pegawai untuk periode tertentu
     *
     * @param string|null $bulan
     * @param string|null $tahun
     * @param string $type mingguan/bulanan
     * @param int $limit
     * @return array
     */
    public static function getRanking(
        ?string $bulan = null,
        ?string $tahun = null,
        string $type = 'bulanan',
        int $limit = 100
    ): array {
        $query = PegawaiDataCache::where('periode_type', $type)
            ->orderByScore('desc')
            ->limit($limit);

        if ($bulan && $tahun) {
            $start = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $end = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();
            $query->where('periode_start', $start)
                  ->where('periode_end', $end);
        }

        $data = $query->get();

        return [
            'total_pegawai' => $data->count(),
            'periode' => $bulan && $tahun ? "$bulan/$tahun" : 'Semua',
            'ranking' => $data->map(function ($row, $index) {
                return [
                    'rank' => $index + 1,
                    'nik' => $row->nik,
                    'nama' => $row->nama,
                    'departemen' => $row->departemen ?? '-',
                    'overall_score' => (float) $row->overall_score,
                    'presensi_rate' => (float) $row->presensi_rate_kehadiran,
                    'sholat_rate' => (float) $row->sholat_rate,
                    'budaya_rate' => (float) $row->budaya_rate,
                    'agenda_rate' => (float) $row->agenda_rate_kehadiran,
                    'status' => $row->status_keterlibatan,
                ];
            })->toArray(),
            'statistics' => [
                'avg_score' => round($data->avg('overall_score'), 2),
                'median_score' => self::calculateMedian($data->pluck('overall_score')->toArray()),
                'highest_score' => (float) $data->max('overall_score'),
                'lowest_score' => (float) $data->min('overall_score'),
            ],
        ];
    }

    /**
     * Detect anomalies - pegawai yang punya pola tidak biasa
     *
     * @param string|null $bulan
     * @param string|null $tahun
     * @return array
     */
    public static function detectAnomalies(?string $bulan = null, ?string $tahun = null): array
    {
        $query = PegawaiDataCache::where('periode_type', 'bulanan');

        if ($bulan && $tahun) {
            $start = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $end = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();
            $query->where('periode_start', $start)
                  ->where('periode_end', $end);
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return ['anomalies' => [], 'summary' => ['total' => 0]];
        }

        $anomalies = [];

        foreach ($data as $pegawai) {
            $issues = [];

            // Check: Presensi rendah (< 70%)
            if ($pegawai->presensi_rate_kehadiran < 70) {
                $issues[] = [
                    'type' => 'presensi_rendah',
                    'detail' => 'Tingkat kehadiran hanya ' . round($pegawai->presensi_rate_kehadiran, 1) . '%',
                    'severity' => 'high',
                ];
            }

            // Check: Sering terlambat (> 5 kali)
            if ($pegawai->presensi_terlambat > 5) {
                $issues[] = [
                    'type' => 'sering_terlambat',
                    'detail' => 'Terlambat ' . $pegawai->presensi_terlambat . ' kali (total ' . $pegawai->presensi_total_menit_terlambat . ' menit)',
                    'severity' => 'medium',
                ];
            }

            // Check: Sholat rendah (< 50%)
            if ($pegawai->sholat_rate < 50) {
                $issues[] = [
                    'type' => 'sholat_rendah',
                    'detail' => 'Tingkat sholat hanya ' . round($pegawai->sholat_rate, 1) . '%',
                    'severity' => 'medium',
                ];
            }

            // Check: Budaya kerja rendah (< 70%)
            if ($pegawai->budaya_rate < 70) {
                $issues[] = [
                    'type' => 'budaya_rendah',
                    'detail' => 'Nilai budaya kerja hanya ' . round($pegawai->budaya_rate, 1) . '%',
                    'severity' => 'high',
                ];
            }

            // Check: Sering tidak hadir agenda (> 2 kali)
            if ($pegawai->agenda_tidak_hadir > 2) {
                $issues[] = [
                    'type' => 'agenda_tidak_hadir',
                    'detail' => 'Tidak hadir di ' . $pegawai->agenda_tidak_hadir . ' agenda',
                    'severity' => 'medium',
                ];
            }

            // Check: Overall score rendah
            if ($pegawai->overall_score < 60) {
                $issues[] = [
                    'type' => 'overall_rendah',
                    'detail' => 'Overall score hanya ' . round($pegawai->overall_score, 1),
                    'severity' => 'high',
                ];
            }

            if (count($issues) > 0) {
                $totalSeverity = array_sum(array_column($issues, 'severity' === 'high' ? 3 : 2));
                $anomalies[] = [
                    'nik' => $pegawai->nik,
                    'nama' => $pegawai->nama,
                    'departemen' => $pegawai->departemen ?? '-',
                    'overall_score' => (float) $pegawai->overall_score,
                    'issues' => $issues,
                    'severity_score' => $totalSeverity,
                ];
            }
        }

        // Sort by severity
        usort($anomalies, function ($a, $b) {
            return $b['severity_score'] <=> $a['severity_score'];
        });

        return [
            'summary' => [
                'total_anomalies' => count($anomalies),
                'high_severity' => count(array_filter($anomalies, fn($a) => count(array_filter($a['issues'], fn($i) => $i['severity'] === 'high')) > 0)),
                'medium_severity' => count(array_filter($anomalies, fn($a) => count(array_filter($a['issues'], fn($i) => $i['severity'] === 'medium')) > 0)),
            ],
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Analisis per departemen
     *
     * @param string|null $bulan
     * @param string|null $tahun
     * @return array
     */
    public static function getDepartmentAnalysis(
        ?string $bulan = null,
        ?string $tahun = null
    ): array {
        $query = PegawaiDataCache::where('periode_type', 'bulanan');

        if ($bulan && $tahun) {
            $start = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $end = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();
            $query->where('periode_start', $start)
                  ->where('periode_end', $end);
        }

        $data = $query->get();

        $departemenStats = $data->groupBy('departemen')->map(function ($group, $dept) {
            $count = $group->count();
            $aktifCount = $group->where('status_keterlibatan', 'aktif')->count();
            $warningCount = $group->where('status_keterlibatan', 'warning')->count();
            $tidakAktifCount = $group->where('status_keterlibatan', 'tidak_aktif')->count();

            return [
                'departemen' => $dept ?? 'Tidak Diketahui',
                'total_pegawai' => $count,
                'avg_score' => round($group->avg('overall_score'), 2),
                'avg_presensi' => round($group->avg('presensi_rate_kehadiran'), 1),
                'avg_sholat' => round($group->avg('sholat_rate'), 1),
                'avg_budaya' => round($group->avg('budaya_rate'), 1),
                'avg_agenda' => round($group->avg('agenda_rate_kehadiran'), 1),
                'status_breakdown' => [
                    'aktif' => $aktifCount,
                    'warning' => $warningCount,
                    'tidak_aktif' => $tidakAktifCount,
                ],
                'compliance_rate' => $count > 0 ? round(($aktifCount / $count) * 100, 1) : 0,
            ];
        })->values()->sortByDesc('avg_score');

        // Calculate overall stats
        $totalPegawai = $data->count();
        $totalAktif = $data->where('status_keterlibatan', 'aktif')->count();
        $totalWarning = $data->where('status_keterlibatan', 'warning')->count();
        $totalTidakAktif = $data->where('status_keterlibatan', 'tidak_aktif')->count();

        return [
            'summary' => [
                'total_departemen' => $departemenStats->count(),
                'total_pegawai' => $totalPegawai,
                'overall_compliance_rate' => $totalPegawai > 0 ? round(($totalAktif / $totalPegawai) * 100, 1) : 0,
                'avg_score_overall' => $totalPegawai > 0 ? round($data->avg('overall_score'), 2) : 0,
            ],
            'status_summary' => [
                'aktif' => $totalAktif,
                'warning' => $totalWarning,
                'tidak_aktif' => $totalTidakAktif,
            ],
            'departemen' => $departemenStats->toArray(),
        ];
    }

    /**
     * Get comparison antar periode
     *
     * @param int $bulanIni
     * @param int $tahunIni
     * @return array
     */
    public static function getPeriodComparison(int $bulanIni, int $tahunIni): array
    {
        $bulanKemarin = $bulanIni == 1 ? 12 : $bulanIni - 1;
        $tahunKemarin = $bulanIni == 1 ? $tahunIni - 1 : $tahunIni;

        $thisMonth = self::getDepartmentAnalysis(str_pad($bulanIni, 2, '0', STR_PAD_LEFT), (string)$tahunIni);
        $lastMonth = self::getDepartmentAnalysis(str_pad($bulanKemarin, 2, '0', STR_PAD_LEFT), (string)$tahunKemarin);

        // Calculate changes
        $changeInScore = $thisMonth['summary']['avg_score_overall'] - $lastMonth['summary']['avg_score_overall'];
        $changeInCompliance = $thisMonth['summary']['overall_compliance_rate'] - $lastMonth['summary']['overall_compliance_rate'];

        return [
            'this_month' => [
                'bulan' => $bulanIni,
                'tahun' => $tahunIni,
                'avg_score' => $thisMonth['summary']['avg_score_overall'],
                'compliance_rate' => $thisMonth['summary']['overall_compliance_rate'],
            ],
            'last_month' => [
                'bulan' => $bulanKemarin,
                'tahun' => $tahunKemarin,
                'avg_score' => $lastMonth['summary']['avg_score_overall'],
                'compliance_rate' => $lastMonth['summary']['overall_compliance_rate'],
            ],
            'change' => [
                'score_change' => round($changeInScore, 2),
                'score_change_percent' => $lastMonth['summary']['avg_score_overall'] > 0
                    ? round(($changeInScore / $lastMonth['summary']['avg_score_overall']) * 100, 1)
                    : 0,
                'compliance_change' => round($changeInCompliance, 1),
                'direction' => $changeInScore > 0 ? 'improving' : ($changeInScore < 0 ? 'declining' : 'stable'),
            ],
        ];
    }

    /**
     * Calculate median value
     */
    private static function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0;
        }

        sort($values);
        $count = count($values);
        $middle = floor($count / 2);

        if ($count % 2 === 0) {
            return round(($values[$middle - 1] + $values[$middle]) / 2, 2);
        }

        return round($values[$middle], 2);
    }
}