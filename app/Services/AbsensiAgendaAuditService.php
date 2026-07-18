<?php

namespace App\Services;

use App\Models\AbsensiAgendaAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

class AbsensiAgendaAuditService
{
    /**
     * Parse User-Agent menggunakan Jenssegers/Agent
     * Mengubah kode model jadi nama HP yang readable
     */
    public static function parseDeviceInfo(Request $request): array
    {
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent() ?? 'Mozilla/5.0 (Linux; Android 13; SM-A536B) AppleWebKit/537.36');

        $device = $agent->device();
        $platform = $agent->platform();
        $browser = $agent->browser();

        return [
            'device_model' => $device ?: 'Unknown Device',
            'os_version' => $platform . ' ' . ($agent->version($platform) ?: ''),
            'browser' => $browser . ' ' . ($agent->version($browser) ?: ''),
        ];
    }

    /**
     * Parse device info dari localStorage + server-side
     */
    public static function parseDeviceInfoFromLocalStorage(Request $request, ?array $localStorageData = null): array
    {
        $serverInfo = self::parseDeviceInfo($request);

        // Jika ada data dari localStorage, gunakan sebagai override
        if ($localStorageData) {
            return array_merge($serverInfo, [
                'device_token' => $localStorageData['device_token'] ?? null,
                'client_ip' => $localStorageData['ip_address'] ?? $request->ip(),
            ]);
        }

        return $serverInfo;
    }

    /**
     * Log audit perubahan status
     */
    public static function log(
        ?int $absensiId,
        int $agendaId,
        string $nik,
        string $aksi,
        ?string $statusLama,
        string $statusBaru,
        ?string $alasan,
        string $diubahOleh,
        Request $request,
        ?string $deviceToken = null
    ): AbsensiAgendaAudit {
        $deviceInfo = self::parseDeviceInfo($request);

        $deviceInfoJson = json_encode([
            'model' => $deviceInfo['device_model'],
            'os' => $deviceInfo['os_version'],
            'browser' => $deviceInfo['browser'],
            'user_agent' => $request->userAgent(),
        ]);

        try {
            return AbsensiAgendaAudit::create([
                'absensi_id' => $absensiId,
                'agenda_id' => $agendaId,
                'nik' => $nik,
                'aksi' => $aksi,
                'status_lama' => $statusLama,
                'status_baru' => $statusBaru,
                'alasan_perubahan' => $alasan,
                'perubahan_oleh' => $diubahOleh,
                'perubahan_pada' => now(),
                'ip_address' => $request->ip(),
                'device_token' => $deviceToken,
                'device_info' => $deviceInfoJson,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal menulis absensi_agenda_audit', [
                'absensi_id' => $absensiId,
                'agenda_id' => $agendaId,
                'nik' => $nik,
                'aksi' => $aksi,
                'error' => $e->getMessage(),
            ]);

            return new AbsensiAgendaAudit();
        }
    }

    /**
     * Log audit untuk create attendance
     */
    public static function logCreate(
        int $absensiId,
        int $agendaId,
        string $nik,
        string $status,
        string $diubahOleh,
        Request $request,
        ?string $deviceToken = null
    ): AbsensiAgendaAudit {
        return self::log(
            $absensiId,
            $agendaId,
            $nik,
            'create',
            null,
            $status,
            null,
            $diubahOleh,
            $request,
            $deviceToken
        );
    }

    /**
     * Log audit untuk update status
     */
    public static function logUpdateStatus(
        int $absensiId,
        int $agendaId,
        string $nik,
        string $statusLama,
        string $statusBaru,
        ?string $alasan,
        string $diubahOleh,
        Request $request,
        ?string $deviceToken = null
    ): AbsensiAgendaAudit {
        return self::log(
            $absensiId,
            $agendaId,
            $nik,
            'update_status',
            $statusLama,
            $statusBaru,
            $alasan,
            $diubahOleh,
            $request,
            $deviceToken
        );
    }

    /**
     * Log audit untuk manual create (ijin, cuti, sakit, etc)
     */
    public static function logManualCreate(
        ?int $absensiId,
        int $agendaId,
        string $nik,
        string $statusBaru,
        ?string $alasan,
        string $diubahOleh,
        Request $request,
        ?string $deviceToken = null
    ): AbsensiAgendaAudit {
        return self::log(
            $absensiId,
            $agendaId,
            $nik,
            'manual_create',
            null,
            $statusBaru,
            $alasan,
            $diubahOleh,
            $request,
            $deviceToken
        );
    }

    /**
     * Get audit trail untuk suatu absensi
     */
    public static function getAuditTrail(int $absensiId): \Illuminate\Database\Eloquent\Collection
    {
        return AbsensiAgendaAudit::where('absensi_id', $absensiId)
            ->with('perubahanOlehPegawai')
            ->orderBy('perubahan_pada', 'desc')
            ->get();
    }

    /**
     * Get audit trail untuk suatu agenda
     */
    public static function getAuditTrailByAgenda(int $agendaId): \Illuminate\Database\Eloquent\Collection
    {
        return AbsensiAgendaAudit::where('agenda_id', $agendaId)
            ->with(['absensi.pegawai', 'perubahanOlehPegawai'])
            ->orderBy('perubahan_pada', 'desc')
            ->get();
    }

    /**
     * Get riwayat perubahan untuk satu NIK
     */
    public static function getHistoryByNik(string $nik, ?int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AbsensiAgendaAudit::where('nik', $nik)
            ->with(['agenda', 'perubahanOlehPegawai'])
            ->orderBy('perubahan_pada', 'desc')
            ->limit($limit ?? 50)
            ->get();
    }
}