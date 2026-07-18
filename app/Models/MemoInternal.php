<?php

namespace App\Models;

use App\Contracts\SignableDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemoInternal extends Model implements SignableDocument
{
    protected $table = 'memo_internal';

    protected $fillable = [
        'id_surat',
        'nik_penandatangan',
        'signature_detail',
        'file_pdf_signed',
        'tanggal_ditandatangani',
        'created_by_username',
    ];

    protected $casts = [
        'signature_detail' => 'array',
        'tanggal_ditandatangani' => 'datetime',
    ];

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class, 'id_surat', 'id_surat');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(MemoInternalPlacement::class, 'memo_internal_id');
    }

    public function penandatangan(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'nik_penandatangan', 'nik');
    }

    public function getFilePdf(): ?string
    {
        if ($this->tanggal_ditandatangani && $this->file_pdf_signed) {
            return $this->file_pdf_signed;
        }

        return $this->surat?->file_surat;
    }

    public function getNomorSurat(): ?string
    {
        return $this->surat?->nomor_surat;
    }

    public function getNomorSuratAttribute(): ?string
    {
        return $this->surat?->nomor_surat;
    }

    public function getTanggal()
    {
        return $this->surat?->tanggal_surat;
    }

    public function getTanggalDitandatangani()
    {
        return $this->tanggal_ditandatangani;
    }

    public function getSignatureDetail(): ?array
    {
        return is_array($this->signature_detail) ? $this->signature_detail : null;
    }

    public function getJudulForFilename(): string
    {
        $perihal = $this->surat?->perihal ?? 'memo';

        return (string) preg_replace('/[^a-zA-Z0-9_-]/', '_', $perihal);
    }

    public function getJudulSuratAttribute(): string
    {
        return (string) ($this->surat?->perihal ?? 'Memo Internal');
    }

    public function getDeskripsiAttribute(): ?string
    {
        return null;
    }
}
