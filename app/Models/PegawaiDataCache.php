<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PegawaiDataCache extends Model
{
    use HasFactory;

    protected $table = 'pegawai_data_cache';

    protected $fillable = [
        'nik',
        'nama',
        'departemen',
        'jabatan',
        'periode_type',
        'periode_start',
        'periode_end',
        // Presensi
        'presensi_total_hadir',
        'presensi_tepat_waktu',
        'presensi_terlambat',
        'presensi_total_menit_terlambat',
        'presensi_tidak_hadir',
        'presensi_rate_kehadiran',
        'presensi_rate_tepat_waktu',
        // Sholat
        'sholat_subuh',
        'sholat_dzuhur',
        'sholat_ashar',
        'sholat_maghrib',
        'sholat_isya',
        'sholat_sunnah',
        'sholat_total',
        'sholat_rate',
        // Budaya
        'budaya_total_penilaian',
        'budaya_total_nilai',
        'budaya_rata_rata',
        'budaya_nilai_tertinggi',
        'budaya_nilai_terendah',
        'budaya_rate',
        // Agenda
        'agenda_diundang',
        'agenda_hadir',
        'agenda_ijin',
        'agenda_cuti',
        'agenda_sakit',
        'agenda_berhalangan',
        'agenda_tidak_hadir',
        'agenda_rate_kehadiran',
        // Overall
        'overall_score',
        'status_keterlibatan',
        'calculated_by',
        'calculated_at',
    ];

    protected $casts = [
        'periode_start' => 'date',
        'periode_end' => 'date',
        'calculated_at' => 'datetime',
    ];

    /**
     * Relasi ke Pegawai
     */
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'nik', 'nik');
    }

    /**
     * Scope: filter by periode
     */
    public function scopeByPeriode($query, string $type, ?string $start = null, ?string $end = null)
    {
        $query->where('periode_type', $type);

        if ($start) {
            $query->where('periode_start', '>=', $start);
        }
        if ($end) {
            $query->where('periode_end', '<=', $end);
        }

        return $query;
    }

    /**
     * Scope: filter by departemen
     */
    public function scopeByDepartemen($query, string $departemen)
    {
        return $query->where('departemen', $departemen);
    }

    /**
     * Scope: filter by status keterlibatan
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status_keterlibatan', $status);
    }

    /**
     * Scope: sort by overall score
     */
    public function scopeOrderByScore($query, string $direction = 'desc')
    {
        return $query->orderBy('overall_score', $direction);
    }

    /**
     * Get aggregated data by departemen
     */
    public static function getByDepartemen(
        string $periodeType = 'bulanan',
        ?string $bulan = null,
        ?string $tahun = null
    ): \Illuminate\Support\Collection {
        $query = self::where('periode_type', $periodeType);

        if ($bulan && $tahun) {
            $start = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $end = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();
            $query->where('periode_start', $start)->where('periode_end', $end);
        }

        return $query->selectRaw('departemen,
            COUNT(*) as total_pegawai,
            AVG(overall_score) as avg_score,
            AVG(presensi_rate_kehadiran) as avg_presensi,
            AVG(sholat_rate) as avg_sholat,
            AVG(budaya_rate) as avg_budaya,
            AVG(agenda_rate_kehadiran) as avg_agenda,
            SUM(CASE WHEN status_keterlibatan = "aktif" THEN 1 ELSE 0 END) as aktif_count,
            SUM(CASE WHEN status_keterlibatan = "warning" THEN 1 ELSE 0 END) as warning_count,
            SUM(CASE WHEN status_keterlibatan = "tidak_aktif" THEN 1 ELSE 0 END) as tidak_aktif_count')
            ->groupBy('departemen')
            ->orderByRaw('AVG(overall_score) DESC');
    }

    /**
     * Get top performers
     */
    public static function getTopPerformers(
        string $periodeType = 'bulanan',
        int $limit = 10,
        ?string $bulan = null,
        ?string $tahun = null
    ): \Illuminate\Support\Collection {
        $query = self::where('periode_type', $periodeType)
            ->orderByScore('desc')
            ->limit($limit);

        if ($bulan && $tahun) {
            $start = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $end = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();
            $query->where('periode_start', $start)->where('periode_end', $end);
        }

        return $query->get();
    }

    /**
     * Get bottom performers (perlu perhatian)
     */
    public static function getBottomPerformers(
        string $periodeType = 'bulanan',
        int $limit = 10,
        ?string $bulan = null,
        ?string $tahun = null
    ): \Illuminate\Support\Collection {
        $query = self::where('periode_type', $periodeType)
            ->orderByScore('asc')
            ->limit($limit);

        if ($bulan && $tahun) {
            $start = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $end = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();
            $query->where('periode_start', $start)->where('periode_end', $end);
        }

        return $query->get();
    }

    /**
     * Get trend data (historical)
     */
    public static function getTrend(
        string $nik,
        int $months = 6
    ): \Illuminate\Support\Collection {
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();

        return self::where('nik', $nik)
            ->where('periode_type', 'bulanan')
            ->where('periode_start', '>=', $startDate->toDateString())
            ->orderBy('periode_start', 'asc')
            ->get();
    }
}