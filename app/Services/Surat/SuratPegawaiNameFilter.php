<?php

namespace App\Services\Surat;

use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filter surat by pegawai name without cross-database whereHas
 * (pegawai lives on server_74, surat on default connection).
 */
final class SuratPegawaiNameFilter
{
    public static function apply(Builder $query, string $keyword, bool $includeExternal = false): void
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return;
        }

        $niks = Pegawai::query()
            ->where('nama', 'like', '%' . addcslashes($keyword, '%_\\') . '%')
            ->pluck('nik');

        $query->where(function ($q) use ($keyword, $niks, $includeExternal) {
            $matched = false;

            if ($includeExternal) {
                $q->where('surat.pengirim_external', 'like', '%' . addcslashes($keyword, '%_\\') . '%');
                $matched = true;
            }

            if ($niks->isNotEmpty()) {
                if ($matched) {
                    $q->orWhereIn('surat.nik_pengirim', $niks);
                } else {
                    $q->whereIn('surat.nik_pengirim', $niks);
                }
            } elseif (! $matched) {
                $q->whereRaw('0 = 1');
            }
        });
    }
}
