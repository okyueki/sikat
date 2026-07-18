<?php

namespace App\Services;

use App\Models\FaceVerificationLog;
use App\Models\Pegawai;
use App\Models\PegawaiFaceProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FaceVerificationService
{
    private InsightFaceService $insightFace;

    public function __construct(InsightFaceService $insightFace)
    {
        $this->insightFace = $insightFace;
    }

    public function isStrictMode(): bool
    {
        return config('insightface.verify_mode', 'soft') === 'strict';
    }

    public function isReady(): bool
    {
        return $this->insightFace->isEnabled()
            && Schema::hasTable('pegawai_face_profiles')
            && Schema::hasTable('face_verification_logs');
    }

    public function hasEnrollment(int $pegawaiId): bool
    {
        if (!Schema::hasTable('pegawai_face_profiles')) {
            return false;
        }
        return PegawaiFaceProfile::where('pegawai_id', $pegawaiId)->exists();
    }

    public function getProfile(int $pegawaiId): ?PegawaiFaceProfile
    {
        if (!Schema::hasTable('pegawai_face_profiles')) {
            return null;
        }
        return PegawaiFaceProfile::where('pegawai_id', $pegawaiId)->first();
    }

    /**
     * @return array{face_enrolled: bool, enrolled_at: string|null, verify_mode: string, face_verification_enabled: bool, nik: string, nama: string}
     */
    public function statusPayload(Pegawai $pegawai): array
    {
        $profile = $this->getProfile($pegawai->id);
        $enabled = $this->insightFace->isEnabled();

        return array_merge($this->identityFields($pegawai), [
            'face_enrolled' => $profile !== null,
            'enrolled_at' => $profile?->enrolled_at?->toIso8601String(),
            'verify_mode' => config('insightface.verify_mode', 'soft'),
            // Frontend: jika false, skip face-api + /verify-face; langsung submit foto biasa.
            'face_verification_enabled' => $enabled,
            'enabled' => $enabled,
        ]);
    }

    /**
     * @return array{success: bool, message: string, data?: array}
     */
    public function enroll(Pegawai $pegawai, Request $request): array
    {
        if (!$this->insightFace->isEnabled()) {
            return ['success' => false, 'message' => 'Fitur verifikasi wajah belum diaktifkan.'];
        }
        if (!Schema::hasTable('pegawai_face_profiles')) {
            return ['success' => false, 'message' => 'Tabel pegawai_face_profiles belum tersedia. Jalankan migrasi database mysql.'];
        }

        $result = $this->insightFace->enroll($pegawai->nik, $request);
        if (!$result['success']) {
            return ['success' => false, 'message' => $result['message']];
        }

        $now = Carbon::now('Asia/Jakarta');
        PegawaiFaceProfile::updateOrCreate(
            ['pegawai_id' => $pegawai->id],
            ['nik' => $pegawai->nik, 'enrolled_at' => $now]
        );

        return [
            'success' => true,
            'message' => $result['message'],
            'data' => array_merge($this->identityFields($pegawai), [
                'face_enrolled' => true,
                'enrolled_at' => $now->toIso8601String(),
            ]),
        ];
    }

    /**
     * Verifikasi wajah saja (InsightFace) — tanpa simpan presensi.
     * Dipanggil mobile SEBELUM POST /submit agar cek tidak "telat".
     *
     * @return array{success: bool, message: string, face_verification: array, min_score?: float}
     */
    public function verifyOnly(Pegawai $pegawai, Request $request): array
    {
        $minScore = (float) config('insightface.min_score', 70);

        // Fitur dimatikan: jangan blokir frontend (terutama user yang sudah enroll).
        // Kembalikan skipped + success agar mobile bisa lanjut ke submit foto biasa.
        if (!$this->insightFace->isEnabled()) {
            $fv = $this->verificationPayload($pegawai, 'skipped', null);
            $fv['message'] = 'Verifikasi wajah sementara dinonaktifkan.';
            $fv['verified'] = false;
            return [
                'success' => true,
                'message' => 'Verifikasi wajah sementara dinonaktifkan. Lanjutkan presensi tanpa cek wajah.',
                'face_verification' => $fv,
                'min_score' => $minScore,
            ];
        }

        if (!$this->isReady()) {
            $fv = $this->verificationPayload($pegawai, 'skipped', null);
            $fv['message'] = 'Verifikasi wajah sementara tidak tersedia.';
            return [
                'success' => true,
                'message' => 'Verifikasi wajah sementara tidak tersedia. Lanjutkan presensi tanpa cek wajah.',
                'face_verification' => $fv,
                'min_score' => $minScore,
            ];
        }

        if (!$this->hasEnrollment($pegawai->id)) {
            $fv = $this->verificationPayload($pegawai, 'skipped', null);
            $fv['message'] = null;
            return [
                'success' => true,
                'message' => 'Wajah belum didaftarkan. Daftarkan wajah di Profil terlebih dahulu.',
                'face_verification' => $fv,
                'min_score' => $minScore,
            ];
        }

        $executed = $this->executeVerification($pegawai, $request);
        $fv = $executed['payload'];
        $this->safeLog($pegawai, 'verify', $fv['status'], $fv['score'], [], $executed['raw']);

        $previewSuccess = in_array($fv['status'], ['match', 'mismatch', 'skipped'], true);

        return [
            'success' => $previewSuccess,
            'message' => $fv['message'] ?? ($previewSuccess ? 'Verifikasi wajah selesai.' : 'Layanan verifikasi wajah sementara tidak tersedia. Coba lagi.'),
            'face_verification' => $fv,
            'min_score' => $minScore,
        ];
    }

    /**
     * Mode strict: verifikasi wajah SEBELUM transaksi presensi.
     *
     * @return array{allowed: bool, face_verification?: array, response?: array}
     */
    public function gateSubmit(Pegawai $pegawai, Request $request): array
    {
        if (!$this->isStrictMode() || !$this->insightFace->isEnabled()) {
            return ['allowed' => true, 'face_verification' => null];
        }

        if (!$this->isReady()) {
            $fv = $this->verificationPayload($pegawai, 'error', null);
            $this->safeLog($pegawai, null, 'error', null, [], null);
            return $this->rejectGate($fv, 'Verifikasi wajah belum siap. Hubungi administrator.');
        }

        if (!$this->hasEnrollment($pegawai->id)) {
            $fv = $this->verificationPayload($pegawai, 'skipped', null);
            $this->safeLog($pegawai, null, 'skipped', null, [], null);
            return $this->rejectGate(
                $fv,
                'Wajah belum didaftarkan. Daftarkan wajah di Profil terlebih dahulu.'
            );
        }

        $executed = $this->executeVerification($pegawai, $request);
        $fv = $executed['payload'];

        if ($fv['verified']) {
            return ['allowed' => true, 'face_verification' => $fv];
        }

        $this->safeLog($pegawai, null, $fv['status'], $fv['score'], [], $executed['raw']);

        $message = match ($fv['status']) {
            'mismatch' => $fv['message'] ?? 'Wajah tidak cocok dengan akun Anda.',
            'error' => 'Verifikasi wajah gagal. Pastikan wajah terlihat jelas dan coba lagi.',
            default => 'Presensi ditolak: verifikasi wajah gagal.',
        };

        return $this->rejectGate($fv, $message);
    }

    /**
     * Setelah commit: lampirkan face_verification (soft = verify di sini; strict = pakai hasil gate).
     *
     * @param array<string, mixed> $presensiResult
     * @param array<string, mixed>|null $preVerified
     * @return array<string, mixed>
     */
    public function finalizeSubmitResponse(
        Pegawai $pegawai,
        Request $request,
        array $presensiResult,
        ?array $preVerified = null
    ): array {
        if ($preVerified !== null) {
            $tipe = $presensiResult['data']['tipe'] ?? null;
            $this->safeLog(
                $pegawai,
                $tipe,
                $preVerified['status'],
                $preVerified['score'],
                $presensiResult['data'] ?? [],
                null
            );
            $presensiResult['face_verification'] = $preVerified;
            return $presensiResult;
        }

        return $this->appendSoftSubmitResponse($pegawai, $request, $presensiResult);
    }

    /**
     * @param array<string, mixed> $presensiResult
     * @return array<string, mixed>
     */
    private function appendSoftSubmitResponse(Pegawai $pegawai, Request $request, array $presensiResult): array
    {
        // Saat fitur off: jangan panggil InsightFace sama sekali (hindari latency).
        if (!$this->insightFace->isEnabled() || !$this->isReady()) {
            $presensiResult['face_verification'] = $this->verificationPayload($pegawai, 'skipped', null);
            $presensiResult['face_verification']['message'] = 'Verifikasi wajah sementara dinonaktifkan.';
            return $presensiResult;
        }

        $tipe = $presensiResult['data']['tipe'] ?? null;
        $executed = $this->executeVerification($pegawai, $request);
        $fv = $executed['payload'];

        if (!$this->isStrictMode()) {
            if (!$this->hasEnrollment($pegawai->id) && $fv['status'] === 'skipped') {
                $this->safeLog($pegawai, $tipe, 'skipped', null, $presensiResult['data'] ?? [], null);
            } else {
                $this->safeLog($pegawai, $tipe, $fv['status'], $fv['score'], $presensiResult['data'] ?? [], $executed['raw']);
            }
        }

        $presensiResult['face_verification'] = $fv;
        return $presensiResult;
    }

    /**
     * @return array{payload: array, raw: string|null}
     */
    private function executeVerification(Pegawai $pegawai, Request $request): array
    {
        $default = $this->verificationPayload($pegawai, 'skipped', null);

        if (!$this->insightFace->isEnabled() || !$this->isReady()) {
            return ['payload' => $default, 'raw' => null];
        }
        if (!$this->hasEnrollment($pegawai->id)) {
            return ['payload' => $default, 'raw' => null];
        }

        try {
            $result = $this->insightFace->verify($pegawai->nik, $request);
            $matched = $result['matched'];
            $score = $result['score'];

            if ($matched === null) {
                return [
                    'payload' => $this->verificationPayload($pegawai, 'error', $score),
                    'raw' => $result['raw'],
                ];
            }

            $status = $matched ? 'match' : 'mismatch';
            return [
                'payload' => $this->verificationPayload($pegawai, $status, $score),
                'raw' => $result['raw'],
            ];
        } catch (\Throwable $e) {
            Log::warning('Face verification error', ['pegawai_id' => $pegawai->id, 'error' => $e->getMessage()]);
            return [
                'payload' => $this->verificationPayload($pegawai, 'error', null),
                'raw' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{allowed: false, response: array}
     */
    private function rejectGate(array $faceVerification, string $message): array
    {
        return [
            'allowed' => false,
            'response' => [
                'success' => false,
                'message' => $message,
                'face_rejected' => true,
                'face_verification' => $faceVerification,
            ],
        ];
    }

    /**
     * @return array{nik: string, nama: string}
     */
    private function identityFields(Pegawai $pegawai): array
    {
        return [
            'nik' => (string) $pegawai->nik,
            'nama' => trim((string) ($pegawai->nama ?? '')),
        ];
    }

    /**
     * @return array{status: string, score: float|null, verified: bool, nik: string, nama: string, message: string|null}
     */
    private function verificationPayload(Pegawai $pegawai, string $status, ?float $score): array
    {
        $payload = array_merge($this->identityFields($pegawai), [
            'status' => $status,
            'score' => $score,
            'verified' => $status === 'match',
            'message' => $this->verificationMessage($status, $pegawai),
        ]);

        if ($status === 'mismatch' && !$this->isStrictMode()) {
            $payload['audit_notice'] = (string) config('insightface.soft_mismatch_audit_notice');
            $payload['show_confirm_dialog'] = true;
        }

        return $payload;
    }

    private function verificationMessage(string $status, Pegawai $pegawai): ?string
    {
        $label = trim((string) ($pegawai->nama ?? '')) ?: (string) $pegawai->nik;

        return match ($status) {
            'match' => "Wajah terverifikasi: {$label}",
            'mismatch' => "Wajah tidak cocok dengan {$label}",
            'skipped' => 'Wajah belum didaftarkan untuk akun ini',
            'error' => 'Verifikasi wajah gagal',
            default => null,
        };
    }

    private function safeLog(
        Pegawai $pegawai,
        ?string $tipe,
        string $status,
        ?float $score,
        array $data,
        ?string $raw
    ): void {
        if (!Schema::hasTable('face_verification_logs')) {
            return;
        }
        try {
            $jamDatang = null;
            if (!empty($data['jam_datang'])) {
                $jamDatang = Carbon::parse($data['jam_datang'])->format('Y-m-d H:i:s');
            }
            FaceVerificationLog::create([
                'pegawai_id' => $pegawai->id,
                'nik' => $pegawai->nik,
                'tipe' => in_array($tipe, ['datang', 'pulang', 'verify'], true) ? $tipe : 'unknown',
                'status' => $status,
                'score' => $score,
                'jam_datang' => $jamDatang,
                'shift' => $data['shift'] ?? null,
                'insightface_response' => $raw !== null ? mb_substr($raw, 0, 65000) : null,
                'created_at' => Carbon::now('Asia/Jakarta'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal menulis face_verification_logs', ['error' => $e->getMessage()]);
        }
    }
}
