<?php

namespace App\Services\Surat;

use App\Models\DisposisiSurat;
use App\Models\Surat;
use App\Models\VerifikasiSurat;
use Illuminate\Database\Eloquent\Builder;

class SuratMasukIndexService
{
    public function unreadCounts(string $nik): array
    {
        return [
            'verifikasi' => VerifikasiSurat::where('nik_verifikator', $nik)
                ->where(function ($q) {
                    $q->whereNull('status_surat')
                        ->orWhere('status_surat', 'Dikirim');
                })
                ->count(),
            'disposisi' => DisposisiSurat::where('nik_penerima', $nik)
                ->where(function ($q) {
                    $q->whereNull('status_disposisi')
                        ->orWhere('status_disposisi', 'Dikirim');
                })
                ->count(),
        ];
    }

    public function indexQuery(string $nik): Builder
    {
        return Surat::query()
            ->select('surat.*')
            ->addSelect([
                'user_verifikasi_status' => VerifikasiSurat::select('status_surat')
                    ->whereColumn('verifikasi_surat.id_surat', 'surat.id_surat')
                    ->where('nik_verifikator', $nik)
                    ->orderByDesc('id_verifikasi_surat')
                    ->limit(1),
                'user_disposisi_status' => DisposisiSurat::select('status_disposisi')
                    ->whereColumn('disposisi_surat.id_surat', 'surat.id_surat')
                    ->where('nik_penerima', $nik)
                    ->orderByDesc('id_disposisi_surat')
                    ->limit(1),
                'user_disposisi_id' => DisposisiSurat::select('id_disposisi_surat')
                    ->whereColumn('disposisi_surat.id_surat', 'surat.id_surat')
                    ->where('nik_penerima', $nik)
                    ->orderByDesc('id_disposisi_surat')
                    ->limit(1),
            ])
            ->with(['pegawai'])
            ->where(function ($q) use ($nik) {
                $q->whereHas('verifikasi', fn ($v) => $v->where('nik_verifikator', $nik))
                    ->orWhereHas('disposisi', fn ($d) => $d->where('nik_penerima', $nik));
            });
    }

    public function pengirimLabel(Surat $row): string
    {
        if (is_null($row->nik_pengirim) || $row->nik_pengirim === '') {
            return ! empty($row->pengirim_external) ? $row->pengirim_external : '-';
        }

        return $row->pegawai ? $row->pegawai->nama : '-';
    }
}
