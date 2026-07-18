<?php

namespace App\Models;

use App\Contracts\SignableDocument;
use App\Models\Concerns\ImplementsSignableDocument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Spo extends Model implements SignableDocument
{
    use HasFactory;
    use ImplementsSignableDocument;

    protected $table = 'spo';

    protected $fillable = [
        'judul_spo',
        'nomor_dokumen',
        'no_urut',
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

    public function uploaderPegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'petugas_upload_nik', 'nik');
    }

    public function departemenUploader(): BelongsTo
    {
        return $this->belongsTo(Departemen::class, 'departemen_upload_id', 'dep_id');
    }

    public function placements(): HasMany
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

    public function setNomorSuratAttribute(?string $value): void
    {
        $this->attributes['nomor_dokumen'] = $value;
    }

    public function getDeskripsiAttribute(): ?string
    {
        return $this->deskripsi_singkat;
    }

    public function getJudulForFilename(): string
    {
        return (string) ($this->judul_spo ?? 'dokumen');
    }
}
