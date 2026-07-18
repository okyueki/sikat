<?php

namespace App\Services\Surat;

use App\Models\MemoInternal;
use App\Models\Surat;
use App\Models\VerifikasiSurat;
use Illuminate\Database\Eloquent\Builder;

class MemoInternalIndexService
{
    public function indexQuery(string $nik): Builder
    {
        return Surat::query()
            ->select('surat.*')
            ->addSelect([
                'first_verifikasi_status' => VerifikasiSurat::select('status_surat')
                    ->whereColumn('verifikasi_surat.id_surat', 'surat.id_surat')
                    ->orderBy('id_verifikasi_surat')
                    ->limit(1),
                'memo_finalized' => MemoInternal::select('tanggal_ditandatangani')
                    ->whereColumn('memo_internal.id_surat', 'surat.id_surat')
                    ->limit(1),
            ])
            ->with(['pegawai', 'memoInternal'])
            ->where('surat.nik_pengirim', $nik)
            ->whereHas('memoInternal');
    }
}
