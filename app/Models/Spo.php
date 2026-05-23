<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Spo extends Model
{
    use HasFactory;

    protected $table = 'spo';

    protected $fillable = [
        'judul_spo',
        'nomor_dokumen',
        'tanggal',
        'deskripsi_singkat',
        'nik_penandatangan',
        'petugas_upload_nik',
        'departemen_upload_id',
        'dep_terkait_ids',
        'file_pdf',
        'file_pdf_signed',
        'signature_detail',
        'created_by_username',
        'tanggal_ditandatangani',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'dep_terkait_ids' => 'array',
        'signature_detail' => 'array',
        'tanggal_ditandatangani' => 'datetime',
    ];

    public function penandatangan()
    {
        return $this->belongsTo(Pegawai::class, 'nik_penandatangan', 'nik');
    }

    public function uploaderPegawai()
    {
        return $this->belongsTo(Pegawai::class, 'petugas_upload_nik', 'nik');
    }

    public function departemenUploader()
    {
        return $this->belongsTo(Departemen::class, 'departemen_upload_id', 'dep_id');
    }

    public function placements()
    {
        return $this->hasMany(SpoPlacement::class, 'spo_id');
    }

    public function getJudulSuratAttribute(): string
    {
        return (string) $this->judul_spo;
    }

    public function getNomorSuratAttribute(): ?string
    {
        return $this->nomor_dokumen;
    }

    public function getDeskripsiAttribute(): ?string
    {
        return $this->deskripsi_singkat;
    }
}
