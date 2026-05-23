<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\AbsensiAgenda;
use App\Models\Pegawai;
use Carbon\Carbon;

class PenilaianAgendaService
{
    /**
     * Hitung nilai otomatis untuk kajian berdasarkan absensi agenda
     * 
     * @param string $nik NIK pegawai
     * @param string $bulan Format: Y-m (contoh: 2026-01)
     * @return array ['nilai' => int, 'persentase' => int, 'detail' => array]
     */
    public function hitungNilaiKajian($nik, $bulan)
    {
        return $this->hitungNilaiByJenis($nik, $bulan, 'kajian');
    }

    /**
     * Hitung nilai otomatis untuk kegiatan RS berdasarkan absensi agenda
     * 
     * @param string $nik NIK pegawai
     * @param string $bulan Format: Y-m (contoh: 2026-01)
     * @return array ['nilai' => int, 'persentase' => int, 'detail' => array]
     */
    public function hitungNilaiKegiatanRS($nik, $bulan)
    {
        return $this->hitungNilaiByJenis($nik, $bulan, 'kegiatan_rs');
    }

    /**
     * Hitung nilai otomatis untuk IHT/EHT berdasarkan absensi agenda
     * 
     * @param string $nik NIK pegawai
     * @param string $bulan Format: Y-m (contoh: 2026-01)
     * @return array ['nilai' => int, 'persentase' => int, 'detail' => array]
     */
    public function hitungNilaiIHT($nik, $bulan)
    {
        return $this->hitungNilaiByJenis($nik, $bulan, 'iht');
    }

    /**
     * Hitung nilai berdasarkan jenis agenda
     * 
     * @param string $nik NIK pegawai
     * @param string $bulan Format: Y-m
     * @param string $jenis jenis_agenda: kajian, kegiatan_rs, iht
     * @return array
     */
    private function hitungNilaiByJenis($nik, $bulan, $jenis)
    {
        // Validasi input
        if (empty($nik) || empty($bulan) || empty($jenis)) {
            \Log::warning('PenilaianAgendaService: Parameter tidak lengkap', [
                'nik' => $nik,
                'bulan' => $bulan,
                'jenis' => $jenis
            ]);
            return $this->getDefaultResponse();
        }

        // Parse bulan
        try {
            $carbonBulan = Carbon::createFromFormat('Y-m', $bulan);
            $startDate = $carbonBulan->copy()->startOfMonth()->format('Y-m-d 00:00:00');
            $endDate = $carbonBulan->copy()->endOfMonth()->format('Y-m-d 23:59:59');
        } catch (\Exception $e) {
            \Log::error('PenilaianAgendaService: Error parsing bulan', [
                'bulan' => $bulan,
                'error' => $e->getMessage()
            ]);
            return $this->getDefaultResponse();
        }

        // Ambil semua agenda dengan jenis yang sesuai dalam bulan tersebut
        // Hanya ambil agenda yang sudah lewat waktu mulai (untuk menghindari agenda yang belum terjadi)
        $agendas = Agenda::where('jenis_agenda', $jenis)
            ->whereBetween('mulai', [$startDate, $endDate])
            ->where('mulai', '<=', now()) // Hanya agenda yang sudah lewat waktu mulai
            ->get();

        if ($agendas->isEmpty()) {
            // Jika tidak ada agenda sama sekali, return nilai maksimal (kebijakan: tidak ada agenda = tidak ada kewajiban)
            return $this->getDefaultResponse();
        }

        // Optimasi: Ambil semua absensi sekaligus untuk menghindari N+1 problem
        $agendaIds = $agendas->pluck('id')->toArray();
        $absensiList = AbsensiAgenda::whereIn('agenda_id', $agendaIds)
            ->where('nik', $nik)
            ->whereNotNull('waktu_kehadiran')
            ->pluck('agenda_id')
            ->toArray();

        // Cek untuk setiap agenda apakah pegawai terundang dan hadir
        $totalAgenda = 0;
        $totalHadir = 0;
        $totalTidakHadir = 0;

        foreach ($agendas as $agenda) {
            // Cek apakah pegawai terundang
            $yangTerundang = is_array($agenda->yang_terundang) 
                ? $agenda->yang_terundang 
                : json_decode($agenda->yang_terundang, true);
            
            // Jika semua pegawai aktif terundang
            if ($agenda->isAllPegawaiTerundang()) {
                $terundang = true;
            } else {
                $terundang = is_array($yangTerundang) && in_array($nik, $yangTerundang);
            }

            if (!$terundang) {
                continue; // Skip jika tidak terundang
            }

            $totalAgenda++;

            // Cek absensi (sudah dioptimasi dengan query sebelumnya)
            if (in_array($agenda->id, $absensiList)) {
                $totalHadir++;
            } else {
                $totalTidakHadir++;
            }
        }

        // Hitung nilai berdasarkan skala sesuai form:
        // 5 = Selalu aktif/hadir di kegiatan dalam 1 bulan
        // 4 = Pernah ijin tidak hadir 1 kali dalam 1 bulan
        // 3 = Pernah ijin tidak hadir 2 kali dalam 1 bulan
        // 2 = Pernah 1 kali tidak hadir tanpa ijin dalam 1 bulan
        // 1 = Pernah 2 kali tidak hadir tanpa ijin dalam 1 bulan
        // 0 = Pernah 3 kali tidak hadir tanpa ijin dalam 1 bulan

        $nilai = 5; // Default nilai maksimal
        if ($totalAgenda > 0) {
            $tidakHadir = $totalTidakHadir;
            
            // Logika sesuai skala form
            if ($tidakHadir == 0) {
                $nilai = 5; // Selalu aktif/hadir
            } elseif ($tidakHadir == 1) {
                // Untuk sementara, kita anggap 1x tidak hadir = dengan ijin (nilai 4)
                // TODO: Tambahkan kolom status_ijin di absensi_agenda untuk membedakan ijin/tidak ijin
                $nilai = 4; // Pernah ijin tidak hadir 1 kali
            } elseif ($tidakHadir == 2) {
                // Untuk sementara, kita anggap 2x tidak hadir = dengan ijin (nilai 3)
                // TODO: Bisa dibedakan jika ada kolom status_ijin
                $nilai = 3; // Pernah ijin tidak hadir 2 kali
            } elseif ($tidakHadir == 3) {
                $nilai = 0; // Pernah 3 kali tidak hadir tanpa ijin
            } else {
                // Lebih dari 3x tidak hadir = nilai 0
                $nilai = 0;
            }
            
            // Catatan: Nilai 1 dan 2 (2x dan 1x tidak hadir tanpa ijin) 
            // saat ini tidak bisa dibedakan karena belum ada kolom status_ijin
            // Jika diperlukan, bisa ditambahkan kolom status_ijin di absensi_agenda
        }

        // Hitung persentase berdasarkan bobot
        $bobot = [
            'kajian' => 15,
            'kegiatan_rs' => 10,
            'iht' => 5,
        ];

        $persentase = ($nilai * $bobot[$jenis]) / 5;

        return [
            'nilai' => $nilai,
            'persentase' => round($persentase, 2),
            'detail' => [
                'total_agenda' => $totalAgenda,
                'total_hadir' => $totalHadir,
                'total_tidak_hadir' => $totalTidakHadir,
                'persentase_kehadiran' => $totalAgenda > 0 ? round(($totalHadir / $totalAgenda) * 100, 2) : 0,
            ]
        ];
    }

    /**
     * Ambil semua nilai otomatis untuk penilaian individu
     * 
     * @param string $nik NIK pegawai
     * @param string $bulan Format: Y-m
     * @return array
     */
    public function getNilaiOtomatis($nik, $bulan)
    {
        return [
            'kajian' => $this->hitungNilaiKajian($nik, $bulan),
            'kegiatan_rs' => $this->hitungNilaiKegiatanRS($nik, $bulan),
            'iht' => $this->hitungNilaiIHT($nik, $bulan),
        ];
    }

    /**
     * Response default jika tidak ada agenda
     */
    private function getDefaultResponse()
    {
        return [
            'nilai' => 5, // Default nilai maksimal jika tidak ada agenda (kebijakan: tidak ada agenda = tidak ada kewajiban)
            'persentase' => 100,
            'detail' => [
                'total_agenda' => 0,
                'total_hadir' => 0,
                'total_tidak_hadir' => 0,
                'persentase_kehadiran' => 0,
            ]
        ];
    }
}
