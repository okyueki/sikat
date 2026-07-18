<?php

namespace App\Models;

use App\Contracts\SignableDocument;
use App\Models\Concerns\ImplementsSignableDocument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KeputusanDirektur extends Model implements SignableDocument
{
    use HasFactory;
    use ImplementsSignableDocument;

    protected $table = 'keputusan_direktur';

    protected $fillable = [
        'judul_surat',
        'nomor_surat',
        'no_urut',
        'deskripsi',
        'tanggal',
        'tanggal_mulai_berlaku',
        'tanggal_berakhir_berlaku',
        'nik_penandatangan',
        'file_pdf',
        'file_pdf_signed',
        'signature_detail',
        'created_by_username',
        'tanggal_ditandatangani',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_mulai_berlaku' => 'date',
        'tanggal_berakhir_berlaku' => 'date',
        'signature_detail' => 'array',
        'tanggal_ditandatangani' => 'datetime',
    ];

    public function placements(): HasMany
    {
        return $this->hasMany(KeputusanDirekturPlacement::class, 'keputusan_direktur_id');
    }
}
