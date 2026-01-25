<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class BerkasPegawai extends Model
{
    use HasFactory;
    protected $connection = 'server_74';
    protected $table = 'berkas_pegawai';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null; // tabel SIMRS tidak punya id auto-increment

    protected $fillable = [
        'nik',
        'tgl_uploud',
        'kode_berkas',
        'berkas',
    ];
}