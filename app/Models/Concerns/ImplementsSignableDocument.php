<?php

namespace App\Models\Concerns;

use App\Contracts\SignableDocument;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait ImplementsSignableDocument
{
    public function getFilePdf(): ?string
    {
        return $this->file_pdf;
    }

    public function getNomorSurat(): ?string
    {
        return $this->nomor_surat;
    }

    public function getTanggal()
    {
        return $this->tanggal;
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
        return (string) ($this->judul_surat ?? 'dokumen');
    }

    public function penandatangan(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Pegawai::class, 'nik_penandatangan', 'nik');
    }

    public function masaBerlakuStatus(): string
    {
        if (! isset($this->tanggal_mulai_berlaku, $this->tanggal_berakhir_berlaku)) {
            return 'unknown';
        }

        $today = now()->startOfDay();
        $mulai = $this->tanggal_mulai_berlaku->copy()->startOfDay();
        $akhir = $this->tanggal_berakhir_berlaku->copy()->startOfDay();

        if ($today->lt($mulai)) {
            return 'belum';
        }
        if ($today->gt($akhir)) {
            return 'berakhir';
        }

        return 'aktif';
    }
}
