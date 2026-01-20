<?php

namespace App\Services;

use App\Models\PengajuanLibur;
use App\Models\AbsensiAgenda;
use Carbon\Carbon;

class AbsensiAgendaService
{
    /**
     * Auto-detect status kehadiran berdasarkan pengajuan libur yang disetujui
     * 
     * @param string $nik
     * @param Carbon $tanggalAgenda
     * @return array ['status' => string, 'alasan' => string|null]
     */
    public static function detectStatusKehadiran($nik, $tanggalAgenda)
    {
        // Cek apakah ada pengajuan libur yang disetujui dan overlap dengan tanggal agenda
        $pengajuanLibur = PengajuanLibur::where('nik', $nik)
            ->where('status', 'Disetujui')
            ->where(function($query) use ($tanggalAgenda) {
                $query->whereDate('tanggal_awal', '<=', $tanggalAgenda->format('Y-m-d'))
                      ->whereDate('tanggal_akhir', '>=', $tanggalAgenda->format('Y-m-d'));
            })
            ->first();

        if ($pengajuanLibur) {
            // Tentukan status berdasarkan jenis pengajuan
            $status = 'cuti'; // default
            $alasan = $pengajuanLibur->keterangan;

            switch ($pengajuanLibur->jenis_pengajuan_libur) {
                case 'Ijin':
                    $status = 'ijin';
                    break;
                case 'Tahunan':
                case 'Melahirkan':
                case 'Ambil Libur':
                case 'Menikah':
                    $status = 'cuti';
                    break;
            }

            return [
                'status' => $status,
                'alasan' => $alasan,
                'pengajuan_libur_id' => $pengajuanLibur->id_pengajuan_libur
            ];
        }

        // Tidak ada pengajuan libur yang overlap
        return [
            'status' => 'tidak_hadir',
            'alasan' => null,
            'pengajuan_libur_id' => null
        ];
    }

    /**
     * Generate atau update absensi untuk semua peserta yang terundang
     * Auto-detect status dari pengajuan libur jika belum ada absensi
     * 
     * @param int $agendaId
     * @return void
     */
    public static function syncAbsensiFromPengajuanLibur($agendaId)
    {
        $agenda = \App\Models\Agenda::find($agendaId);
        if (!$agenda) {
            return;
        }

        $tanggalAgenda = Carbon::parse($agenda->mulai);
        $terundang = is_array($agenda->yang_terundang) 
            ? $agenda->yang_terundang 
            : (json_decode($agenda->yang_terundang, true) ?? []);

        // Cek apakah semua pegawai aktif terundang
        $semuaNikAktif = \App\Models\Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray();
        $isAll = false;
        
        if (is_array($terundang)) {
            $intersect = array_intersect($semuaNikAktif, $terundang);
            $isAll = count($intersect) === count($semuaNikAktif) && count($terundang) === count($semuaNikAktif);
        }

        // Tentukan daftar NIK yang terundang
        if ($isAll || (is_array($terundang) && in_array("all", $terundang))) {
            $pegawaiIds = $semuaNikAktif;
        } else {
            $pegawaiIds = is_array($terundang) ? $terundang : [];
        }

        // Loop setiap peserta yang terundang
        foreach ($pegawaiIds as $nik) {
            // Cek apakah sudah ada absensi
            $absensi = AbsensiAgenda::where('agenda_id', $agendaId)
                ->where('nik', $nik)
                ->first();

            // Jika belum ada absensi, buat entry dengan auto-detect
            if (!$absensi) {
                $detected = self::detectStatusKehadiran($nik, $tanggalAgenda);
                
                AbsensiAgenda::create([
                    'nik' => $nik,
                    'agenda_id' => $agendaId,
                    'status_kehadiran' => $detected['status'],
                    'alasan' => $detected['alasan'],
                    'waktu_kehadiran' => $detected['status'] === 'hadir' ? now() : null,
                    'token' => 'AUTO-' . $agendaId . '-' . $nik . '-' . time(), // Token dummy untuk auto-detect
                ]);
            }
        }
    }
}
