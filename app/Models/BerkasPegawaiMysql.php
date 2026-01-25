<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Metadata berkas pegawai (DB internal mysql).
 *
 * Menyimpan masa berlaku, status verifikasi, catatan, dll untuk berkas SIMRS
 * tanpa mengubah struktur tabel SIMRS.
 */
class BerkasPegawaiMysql extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'berkas_pegawai_meta';
    protected $primaryKey = 'id';
    public $timestamps = false; // kompatibel jika tabel tidak punya created_at/updated_at

    protected $fillable = [
        'nik',
        'kode_berkas',
        'tgl_uploud',
        'berkas',
        'masa_berlaku_sampai',
        'verifikasi_status',
        'catatan_verifikator',
        'verified_at',
        'verified_by',
    ];
}