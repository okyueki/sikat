<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface SignableDocument
{
    public function getKey();

    public function getFilePdf(): ?string;

    public function getNomorSurat(): ?string;

    public function getTanggal();

    public function getTanggalDitandatangani();

    public function getSignatureDetail(): ?array;

    public function getJudulForFilename(): string;

    public function placements(): HasMany;

    public function penandatangan(): BelongsTo;
}
