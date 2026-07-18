<?php

namespace App\Policies;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SuratPolicy
{
    use HandlesAuthorization;

    public function viewMasuk(User $user, Surat $surat): bool
    {
        $nik = $user->username;

        return $surat->verifikasi()->where('nik_verifikator', $nik)->exists()
            || $surat->disposisi()->where('nik_penerima', $nik)->exists();
    }

    public function viewKeluar(User $user, Surat $surat): bool
    {
        return $surat->nik_pengirim === $user->username;
    }

    public function deleteKeluar(User $user, Surat $surat): bool
    {
        if ($surat->nik_pengirim !== $user->username) {
            return false;
        }

        $firstVerifikasi = $surat->verifikasi()->orderBy('id_verifikasi_surat')->first();

        return ! ($firstVerifikasi && $firstVerifikasi->status_surat === 'Disetujui');
    }
}
