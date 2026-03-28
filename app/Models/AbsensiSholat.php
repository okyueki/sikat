<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsensiSholat extends Model
{
    protected $table = 'absensi_sholat';

    protected $fillable = [
        'nik',
        'waktu_absen',
        'jenis_sholat',
        'token_used',
    ];

    protected $casts = [
        'waktu_absen' => 'datetime',
    ];

    /**
     * Relasi ke Pegawai (untuk rekap nama/departemen).
     */
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'nik', 'nik');
    }

    /**
     * Nilai default jenis_sholat yang diizinkan.
     */
    public static function jenisSholatOptions(): array
    {
        return ['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya', 'sunnah'];
    }
}
