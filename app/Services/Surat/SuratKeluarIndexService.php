<?php

namespace App\Services\Surat;

use App\Models\Surat;
use App\Models\TemplateSurat;
use App\Models\VerifikasiSurat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SuratKeluarIndexService
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
            ])
            ->with(['pegawai'])
            ->where('surat.nik_pengirim', $nik)
            ->whereDoesntHave('memoInternal');
    }

    public function templates(): Collection
    {
        return Cache::remember('template_surat_list', 3600, fn () => TemplateSurat::all());
    }
}
