<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dokter extends Model
{
    use HasFactory;

    protected $connection = 'server_74';
    protected $table = 'dokter';
    protected $primaryKey = 'kd_dokter'; // biar jelas primary key-nya
    public $incrementing = false;       // kd_dokter biasanya varchar, bukan auto increment
    protected $keyType = 'string';      // kalau varchar

    protected $fillable = [
        'kd_dokter', 'nm_dokter', 'jk', 'tmp_lahir', 'tgl_lahir', 'gol_drh',
        'agama', 'almt_tgl', 'no_telp', 'email', 'stts_nikah',
        'kd_sps', 'alumni', 'no_ijn_praktek', 'status'
    ];

    // Relasi ke JadwalBudayaKerja (kalau kamu mau dokter ikut jadwal)
    public function jadwalBudayaKerja()
    {
        return $this->hasMany(JadwalBudayaKerja::class, 'nik', 'kd_dokter');
    }

    // Kalau ada tabel Pegawai juga bisa dihubungkan
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'kd_dokter', 'nik');
    }
}
