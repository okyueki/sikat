<?php

namespace App\Services\Surat;

use App\Models\Surat;
use Illuminate\Contracts\Auth\Authenticatable;

class SuratMasukActionResolver
{
    public function render(Surat $row, Authenticatable $user): string
    {
        $level = $user->level ?? '';
        $statusVerifikasi = $row->user_verifikasi_status ?? null;
        $statusDisposisi = $row->user_disposisi_status ?? null;
        $hasDisposisi = ! empty($row->user_disposisi_id);
        $verifikasiDisetujui = $statusVerifikasi && trim($statusVerifikasi) === 'Disetujui';
        $disposisiPerluTindak = $hasDisposisi && ($statusDisposisi === null || ($statusDisposisi !== 'Ditindaklanjuti' && $statusDisposisi !== 'Selesai'));

        $editRoute = null;

        if ($level === 'Direktur') {
            if (! $verifikasiDisetujui) {
                $editRoute = 'surat_masuk.disposisi';
            }
        } elseif ($level === 'Kabag' || $level === 'Kabid') {
            if ($verifikasiDisetujui) {
                $editRoute = $disposisiPerluTindak ? 'surat_masuk.tindaklanjut' : null;
            } else {
                $editRoute = 'surat_masuk.verifikasidisposisi';
            }
        } elseif ($level === 'Kasie') {
            if ($verifikasiDisetujui) {
                $editRoute = $disposisiPerluTindak ? 'surat_masuk.tindaklanjut' : null;
            } else {
                $editRoute = 'surat_masuk.verifikasi';
            }
        } else {
            $editRoute = $disposisiPerluTindak ? 'surat_masuk.tindaklanjut' : null;
        }

        return view('components.surat.masuk-index-actions', [
            'encryptedKodeSurat' => encrypt($row->kode_surat),
            'editRoute' => $editRoute,
        ])->render();
    }
}
