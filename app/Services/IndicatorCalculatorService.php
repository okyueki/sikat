<?php

namespace App\Services;

use App\Models\PegawaiDataCache;
use Carbon\Carbon;

/**
 * IndicatorCalculatorService
 *
 * Service untuk menghitung skor dan indikator kehadiran pegawai.
 * Digunakan untuk:
 * 1. Calculate overall score dari 4 komponen
 * 2. Determine status keterlibatan (aktif/warning/tidak_aktif)
 * 3. Generate badge/label untuk visualisasi
 *
 * Bobot default:
 * - Presensi: 30%
 * - Absensi Sholat: 25%
 * - Budaya Kerja: 25%
 * - Absensi Agenda: 20%
 *
 * Threshold:
 * - Aktif: score >= 70
 * - Warning: score 50-69
 * - Tidak Aktif: score < 50
 */
class IndicatorCalculatorService
{
    /**
     * Bobot untuk setiap komponen (total harus 1)
     */
    public const WEIGHT_PRESENSI = 0.30;
    public const WEIGHT_SHOLAT = 0.25;
    public const WEIGHT_BUDAYA = 0.25;
    public const WEIGHT_AGENDA = 0.20;

    /**
     * Threshold untuk status
     */
    public const THRESHOLD_AKTIF = 70;
    public const THRESHOLD_WARNING = 50;

    /**
     * Hitung overall score dari aggregated data
     *
     * @param array $aggregatedData Data dari PegawaiDataAggregatorService
     * @return array Data dengan overall_score dan status_keterlibatan
     */
    public static function calculateScore(array $aggregatedData): array
    {
        // Calculate component scores
        $presensiScore = self::calculatePresensiScore($aggregatedData['presensi'] ?? []);
        $sholatScore = self::calculateSholatScore($aggregatedData['sholat'] ?? []);
        $budayaScore = self::calculateBudayaScore($aggregatedData['budaya'] ?? []);
        $agendaScore = self::calculateAgendaScore($aggregatedData['agenda'] ?? []);

        // Calculate weighted overall score
        $overallScore = round((
            ($presensiScore * self::WEIGHT_PRESENSI) +
            ($sholatScore * self::WEIGHT_SHOLAT) +
            ($budayaScore * self::WEIGHT_BUDAYA) +
            ($agendaScore * self::WEIGHT_AGENDA)
        ), 2);

        // Determine status
        $status = self::determineStatus($overallScore);

        // Add breakdown
        $breakdown = [
            'presensi' => [
                'score' => $presensiScore,
                'weight' => self::WEIGHT_PRESENSI,
                'weighted' => round($presensiScore * self::WEIGHT_PRESENSI, 2),
            ],
            'sholat' => [
                'score' => $sholatScore,
                'weight' => self::WEIGHT_SHOLAT,
                'weighted' => round($sholatScore * self::WEIGHT_SHOLAT, 2),
            ],
            'budaya' => [
                'score' => $budayaScore,
                'weight' => self::WEIGHT_BUDAYA,
                'weighted' => round($budayaScore * self::WEIGHT_BUDAYA, 2),
            ],
            'agenda' => [
                'score' => $agendaScore,
                'weight' => self::WEIGHT_AGENDA,
                'weighted' => round($agendaScore * self::WEIGHT_AGENDA, 2),
            ],
        ];

        return [
            'overall_score' => $overallScore,
            'status_keterlibatan' => $status,
            'breakdown' => $breakdown,
            'badge' => self::getBadge($status),
            'color' => self::getColor($status),
            'interpretasi' => self::getInterpretasi($overallScore),
        ];
    }

    /**
     * Calculate score untuk presensi
     * Formula: (kehadiran_rate * 0.7) + (tepat_waktu_rate * 0.3)
     * Bobot: 30%
     */
    private static function calculatePresensiScore(array $presensiData): float
    {
        if (empty($presensiData)) {
            return 0;
        }

        $rateKehadiran = $presensiData['rate_kehadiran'] ?? 0;
        $rateTepatWaktu = $presensiData['rate_tepat_waktu'] ?? 0;

        // Jika tidak ada data, berikan 0
        $totalHadir = $presensiData['total_hadir'] ?? 0;
        if ($totalHadir === 0) {
            return 0;
        }

        // Score = (rate kehadiran * 0.7) + (rate tepat waktu * 0.3)
        return round(($rateKehadiran * 0.7) + ($rateTepatWaktu * 0.3), 2);
    }

    /**
     * Calculate score untuk absensi sholat
     * Formula: total sholat / (hari kerja * 5 jenis) * 100
     * Bobot: 25%
     */
    private static function calculateSholatScore(array $sholatData): float
    {
        if (empty($sholatData)) {
            return 0;
        }

        $totalSholat = $sholatData['total'] ?? 0;
        $hariKerja = $sholatData['hari_kerja'] ?? 0;

        if ($hariKerja === 0 || $totalSholat === 0) {
            return 0;
        }

        // 5 jenis sholat per hari
        $kesempatan = $hariKerja * 5;

        return round(($totalSholat / $kesempatan) * 100, 2);
    }

    /**
     * Calculate score untuk budaya kerja
     * Formula: (rata_rata / 11) * 100
     * Bobot: 25%
     */
    private static function calculateBudayaScore(array $budayaData): float
    {
        if (empty($budayaData)) {
            return 0;
        }

        $rataRata = $budayaData['rata_rata'] ?? 0;
        $maxNilai = $budayaData['max_nilai'] ?? 11;

        if ($rataRata === 0) {
            return 0;
        }

        return round(($rataRata / $maxNilai) * 100, 2);
    }

    /**
     * Calculate score untuk absensi agenda
     * Formula: (hadir + ijin + cuti) / diundang * 100
     * Bobot: 20%
     */
    private static function calculateAgendaScore(array $agendaData): float
    {
        if (empty($agendaData)) {
            return 0;
        }

        $diundang = $agendaData['diundang'] ?? 0;

        if ($diundang === 0) {
            return 0; // Tidak ada agenda = tidak ada score
        }

        $hadir = $agendaData['hadir'] ?? 0;
        $ijin = $agendaData['ijin'] ?? 0;
        $cuti = $agendaData['cuti'] ?? 0;

        $totalHadir = $hadir + $ijin + $cuti;

        return round(($totalHadir / $diundang) * 100, 2);
    }

    /**
     * Determine status based on overall score
     */
    private static function determineStatus(float $score): string
    {
        if ($score >= self::THRESHOLD_AKTIF) {
            return 'aktif';
        } elseif ($score >= self::THRESHOLD_WARNING) {
            return 'warning';
        }

        return 'tidak_aktif';
    }

    /**
     * Get badge info
     */
    public static function getBadge(string $status): string
    {
        return match ($status) {
            'aktif' => 'badge-success',
            'warning' => 'badge-warning',
            'tidak_aktif' => 'badge-danger',
        };
    }

    /**
     * Get color for visualization
     */
    public static function getColor(string $status): string
    {
        return match ($status) {
            'aktif' => 'success',
            'warning' => 'warning',
            'tidak_aktif' => 'danger',
        };
    }

    /**
     * Get interpretasi score
     */
    public static function getInterpretasi(float $score): string
    {
        if ($score >= 90) {
            return 'Sangat Baik - Pegawai sangat aktif dalam semua kegiatan';
        } elseif ($score >= 80) {
            return 'Baik - Pegawai menunjukkan kinerja yang baik';
        } elseif ($score >= 70) {
            return 'Cukup Baik - Pegawai cukup aktif, ada ruang untuk improvement';
        } elseif ($score >= 60) {
            return 'Perlu Perhatian - Perlu peningkatan di beberapa area';
        } elseif ($score >= 50) {
            return 'Warning - Banyak area yang perlu perhatian khusus';
        }

        return 'Kritis - Membutuhkan intervensi segera';
    }

    /**
     * Calculate score untuk semua pegawai dari aggregated data
     * Used when saving to cache
     */
    public static function calculateScoresFromAggregated($aggregatedCollection): \Illuminate\Support\Collection
    {
        return $aggregatedCollection->map(function ($data) {
            $scoreData = self::calculateScore($data);
            $data['overall_score'] = $scoreData['overall_score'];
            $data['status_keterlibatan'] = $scoreData['status_keterlibatan'];
            $data['score_breakdown'] = $scoreData['breakdown'];
            $data['score_interpretasi'] = $scoreData['interpretasi'];
            return $data;
        });
    }

    /**
     * Batch calculate untuk save ke database
     */
    public static function prepareCacheData($aggregatedCollection, int $calculatedBy = null): array
    {
        return $aggregatedCollection->map(function ($data) use ($calculatedBy) {
            $scoreData = self::calculateScore($data);

            return [
                'nik' => $data['nik'],
                'nama' => $data['nama'],
                'departemen' => $data['departemen'],
                'jabatan' => $data['jabatan'],
                'periode_type' => 'bulanan',
                'periode_start' => $data['periode_start'],
                'periode_end' => $data['periode_end'],

                // Presensi
                'presensi_total_hadir' => $data['presensi']['total_hadir'] ?? 0,
                'presensi_tepat_waktu' => $data['presensi']['tepat_waktu'] ?? 0,
                'presensi_terlambat' => $data['presensi']['terlambat'] ?? 0,
                'presensi_total_menit_terlambat' => $data['presensi']['total_menit_terlambat'] ?? 0,
                'presensi_tidak_hadir' => $data['presensi']['tidak_hadir'] ?? 0,
                'presensi_rate_kehadiran' => $data['presensi']['rate_kehadiran'] ?? 0,
                'presensi_rate_tepat_waktu' => $data['presensi']['rate_tepat_waktu'] ?? 0,

                // Sholat
                'sholat_subuh' => $data['sholat']['by_jenis']['subuh']['jumlah'] ?? 0,
                'sholat_dzuhur' => $data['sholat']['by_jenis']['dzuhur']['jumlah'] ?? 0,
                'sholat_ashar' => $data['sholat']['by_jenis']['ashar']['jumlah'] ?? 0,
                'sholat_maghrib' => $data['sholat']['by_jenis']['maghrib']['jumlah'] ?? 0,
                'sholat_isya' => $data['sholat']['by_jenis']['isya']['jumlah'] ?? 0,
                'sholat_sunnah' => $data['sholat']['by_jenis']['sunnah']['jumlah'] ?? 0,
                'sholat_total' => $data['sholat']['total'] ?? 0,
                'sholat_rate' => $data['sholat']['rate'] ?? 0,

                // Budaya
                'budaya_total_penilaian' => $data['budaya']['total_penilaian'] ?? 0,
                'budaya_total_nilai' => $data['budaya']['total_nilai'] ?? 0,
                'budaya_rata_rata' => $data['budaya']['rata_rata'] ?? 0,
                'budaya_nilai_tertinggi' => $data['budaya']['nilai_tertinggi'] ?? 0,
                'budaya_nilai_terendah' => $data['budaya']['nilai_terendah'] ?? 0,
                'budaya_rate' => $data['budaya']['rate'] ?? 0,

                // Agenda
                'agenda_diundang' => $data['agenda']['diundang'] ?? 0,
                'agenda_hadir' => $data['agenda']['hadir'] ?? 0,
                'agenda_ijin' => $data['agenda']['ijin'] ?? 0,
                'agenda_cuti' => $data['agenda']['cuti'] ?? 0,
                'agenda_sakit' => $data['agenda']['sakit'] ?? 0,
                'agenda_berhalangan' => $data['agenda']['berhalangan'] ?? 0,
                'agenda_tidak_hadir' => $data['agenda']['tidak_hadir'] ?? 0,
                'agenda_rate_kehadiran' => $data['agenda']['rate_kehadiran'] ?? 0,

                // Overall
                'overall_score' => $scoreData['overall_score'],
                'status_keterlibatan' => $scoreData['status_keterlibatan'],
                'calculated_by' => $calculatedBy,
                'calculated_at' => now(),
            ];
        })->toArray();
    }
}