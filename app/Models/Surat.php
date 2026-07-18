<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Surat extends Model
{
    use HasFactory;

    protected $table = 'surat';
    protected $primaryKey = 'id_surat';
    protected $fillable = [
        'kode_surat',
        'nomor_surat',
        'no_urut',
        'id_klasifikasi_surat',
        'id_sifat_surat',
        'nik_pengirim',
        'pengirim_external',
        'perihal',
        'tanggal_surat',
        'lampiran',
        'tanggal_surat_diterima',
        'file_surat',
        'file_lampiran',
    ];

    public function verifikasi()
    {
        return $this->hasMany(VerifikasiSurat::class, 'id_surat', 'id_surat');
    }

    public function disposisi()
    {
        return $this->hasMany(DisposisiSurat::class, 'id_surat', 'id_surat');
    }

    public function latestVerifikasiFor(string $nik): ?VerifikasiSurat
    {
        return $this->verifikasi()
            ->where('nik_verifikator', $nik)
            ->orderByDesc('id_verifikasi_surat')
            ->first();
    }

    public function latestDisposisiFor(string $nik): ?DisposisiSurat
    {
        return $this->disposisi()
            ->where('nik_penerima', $nik)
            ->orderByDesc('id_disposisi_surat')
            ->first();
    }
    public function klasifikasi_surat()
    {
        return $this->belongsTo(KlasifikasiSurat::class, 'id_klasifikasi_surat','id_klasifikasi_surat');
    }
    public function sifat_surat()
    {
        return $this->belongsTo(SifatSurat::class, 'id_sifat_surat');
    }
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'nik_pengirim', 'nik');
    }

    // Relasi ke Agenda (jika surat ini direalisasikan sebagai agenda)
    public function agenda()
    {
        return $this->hasOne(Agenda::class, 'id_surat_keluar', 'id_surat');
    }

    public function memoInternal()
    {
        return $this->hasOne(MemoInternal::class, 'id_surat', 'id_surat');
    }

    public function tandaTangan()
    {
        return $this->hasMany(TandaTangan::class, 'id_surat', 'id_surat');
    }

    public function isMemoInternal(): bool
    {
        return $this->relationLoaded('memoInternal')
            ? $this->memoInternal !== null
            : $this->memoInternal()->exists();
    }
}
