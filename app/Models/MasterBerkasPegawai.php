<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class MasterBerkasPegawai extends Model
{
    use HasFactory;
    protected $connection = 'server_74';
    protected $table = 'master_berkas_pegawai';
    protected $fillable = ['kode', 'kategori', 'nama_berkas', 'no_urut'];
    protected $enum = ['Tenaga klinis Dokter Umum','Tenaga klinis Dokter Spesialis','Tenaga klinis Perawat dan Bidan','Tenaga klinis Profesi Lain','Tenaga Non Klinis'];
}