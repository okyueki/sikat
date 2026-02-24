<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekapPresensi extends Model
{
    use HasFactory;

    protected $connection = 'server_74';
    protected $table = 'rekap_presensi';

    // Disable automatic timestamps
    public $timestamps = false;

    protected $fillable = [
        'id', 'shift', 'jam_datang', 'jam_pulang', 'status',
        'keterlambatan', 'durasi', 'keterangan', 'photo'
    ];

    protected $casts = [
        'jam_datang' => 'datetime',
        'jam_pulang' => 'datetime',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'id', 'id'); // Sesuaikan kolom foreign key di sini
    }
}
