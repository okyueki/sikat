<?php

namespace App\Services\Surat;

use App\Models\Surat;

class SuratKeluarActionResolver
{
    public function render(Surat $row): string
    {
        $canEdit = $row->first_verifikasi_status !== 'Disetujui';

        return view('components.surat.keluar-index-actions', [
            'encryptedKodeSurat' => encrypt($row->kode_surat),
            'suratId' => $row->id_surat,
            'canEdit' => $canEdit,
        ])->render();
    }
}
