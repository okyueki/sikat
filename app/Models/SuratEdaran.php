<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratEdaran extends Model
{
    use HasFactory;

    protected $table = 'surat_edaran';

    protected $fillable = [
        'judul_surat',
        'nomor_surat',
        'deskripsi',
        'tanggal',
        'nik_penandatangan',
        'file_pdf',
        'file_pdf_signed',
        'signature_detail',
        'created_by_username',
        'tanggal_ditandatangani',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'signature_detail' => 'array',
        'tanggal_ditandatangani' => 'datetime',
    ];

    public function penandatangan()
    {
        return $this->belongsTo(Pegawai::class, 'nik_penandatangan', 'nik');
    }

    public function placements()
    {
        return $this->hasMany(SuratEdaranPlacement::class, 'surat_edaran_id');
    }
}
