<?php

namespace App\Models;

use App\Contracts\SignableDocument;
use App\Models\Concerns\ImplementsSignableDocument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratEdaran extends Model implements SignableDocument
{
    use HasFactory;
    use ImplementsSignableDocument;

    protected $table = 'surat_edaran';

    protected $fillable = [
        'judul_surat',
        'nomor_surat',
        'no_urut',
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

    public function placements(): HasMany
    {
        return $this->hasMany(SuratEdaranPlacement::class, 'surat_edaran_id');
    }
}
