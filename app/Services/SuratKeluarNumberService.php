<?php

namespace App\Services;

use App\Models\KlasifikasiSurat;
use App\Models\Pegawai;
use App\Models\Surat;
use Illuminate\Support\Facades\DB;

final class SuratKeluarNumberService
{
    public static function format(int $noUrut, string $kodeKlasifikasi, string $departemen, int $tahun): string
    {
        return "RS'ASF/" . $noUrut . '/' . $kodeKlasifikasi . '/' . $departemen . '/' . $tahun;
    }

    public static function previewNext(string $nik, int $klasifikasiId, string $tanggalSurat): string
    {
        $klasifikasi = KlasifikasiSurat::find($klasifikasiId);
        $departemen = Pegawai::where('nik', $nik)->value('departemen') ?? '-';
        $tahun = (int) date('Y', strtotime($tanggalSurat));
        $nextNo = self::nextNoUrut();

        return self::format($nextNo, $klasifikasi?->kode_klasifikasi_surat ?? '-', $departemen, $tahun);
    }

    public static function assignToSurat(Surat $surat, string $nik): void
    {
        if ($surat->nomor_surat && $surat->no_urut) {
            return;
        }

        DB::transaction(function () use ($surat, $nik) {
            $surat->refresh();
            if ($surat->nomor_surat && $surat->no_urut) {
                return;
            }

            $nextNo = self::nextNoUrut();
            $klasifikasi = KlasifikasiSurat::find($surat->id_klasifikasi_surat);
            $departemen = Pegawai::where('nik', $nik)->value('departemen') ?? '-';
            $tahun = (int) date('Y', strtotime((string) $surat->tanggal_surat));

            $surat->update([
                'no_urut' => $nextNo,
                'nomor_surat' => self::format(
                    $nextNo,
                    $klasifikasi?->kode_klasifikasi_surat ?? '-',
                    $departemen,
                    $tahun
                ),
            ]);
        });
    }

    private static function nextNoUrut(): int
    {
        $lastNo = (int) Surat::query()
            ->whereNotNull('nik_pengirim')
            ->whereNotNull('no_urut')
            ->lockForUpdate()
            ->max('no_urut');

        return max(1, $lastNo + 1);
    }
}
