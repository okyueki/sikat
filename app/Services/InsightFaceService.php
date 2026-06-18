<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InsightFaceService
{
    /**
     * @return array{success: bool, message: string, raw: string|null}
     */
    public function enroll(string $nik, Request $request): array
    {
        $file = $this->resolveImageFile($request);
        if (!$file) {
            return ['success' => false, 'message' => 'Foto wajah wajib diunggah.', 'raw' => null];
        }

        try {
            $raw = $this->uploadSelfie($nik, $file);

            if ($raw === null) {
                return ['success' => false, 'message' => 'Gagal mendaftarkan wajah ke server.', 'raw' => null];
            }

            $decoded = json_decode(trim($raw), true);
            if (is_array($decoded)
                && (int) ($decoded['status_code'] ?? 0) === 422
                && str_contains(strtolower($decoded['message'] ?? ''), 'unique')) {
                // Re-enroll: hapus wajah lama lalu upload ulang (overwrite)
                $this->deleteFace($nik);
                $raw = $this->uploadSelfie($nik, $file);
                if ($raw === null) {
                    return ['success' => false, 'message' => 'Gagal memperbarui wajah.', 'raw' => null];
                }
            }

            if ($this->responseIndicatesFailure($raw)) {
                return ['success' => false, 'message' => 'Gagal mendaftarkan wajah. Pastikan wajah terlihat jelas.', 'raw' => $raw];
            }

            return ['success' => true, 'message' => 'Wajah berhasil didaftarkan.', 'raw' => $raw];
        } catch (\Throwable $e) {
            Log::error('InsightFace enroll error', ['nik' => $nik, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Layanan verifikasi wajah tidak tersedia.', 'raw' => null];
        } finally {
            $this->cleanupTempFile($file);
        }
    }

    /**
     * @return array{matched: bool|null, score: float|null, raw: string|null}
     */
    public function verify(string $nik, Request $request): array
    {
        $file = $this->resolveImageFile($request);
        if (!$file) {
            return ['matched' => null, 'score' => null, 'raw' => null];
        }

        try {
            $response = Http::timeout(config('insightface.timeout', 5))
                ->attach('file', file_get_contents($file['path']), $file['filename'])
                ->post($this->urlWithName('/face-verification', $nik));

            $raw = $response->body();

            if (!$response->successful()) {
                Log::warning('InsightFace verify gagal', ['nik' => $nik, 'status' => $response->status(), 'body' => $raw]);
                return ['matched' => null, 'score' => null, 'raw' => $raw];
            }

            return $this->parseVerificationResponse($raw);
        } catch (\Throwable $e) {
            Log::error('InsightFace verify error', ['nik' => $nik, 'error' => $e->getMessage()]);
            return ['matched' => null, 'score' => null, 'raw' => null];
        } finally {
            $this->cleanupTempFile($file);
        }
    }

    public function ping(): bool
    {
        try {
            return Http::timeout(3)->get($this->url('/'))->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isEnabled(): bool
    {
        return (bool) config('insightface.enabled', true)
            && !empty(config('insightface.base_url'));
    }

    public function deleteFace(string $nik): bool
    {
        try {
            $response = Http::timeout(config('insightface.timeout', 5))
                ->delete($this->urlWithName('/faces', $nik));
            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('InsightFace deleteFace error', ['nik' => $nik, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * @param array{path: string, filename: string, is_temp: bool} $file
     */
    private function uploadSelfie(string $nik, array $file): ?string
    {
        $response = Http::timeout(config('insightface.timeout', 5))
            ->attach('file', file_get_contents($file['path']), $file['filename'])
            ->post($this->urlWithName('/upload-selfie', $nik));

        $raw = $response->body();

        if (!$response->successful() && $response->status() !== 422) {
            Log::warning('InsightFace upload-selfie gagal', ['nik' => $nik, 'status' => $response->status(), 'body' => $raw]);
            return null;
        }

        return $raw;
    }

    /**
     * Format aktual InsightFace (uji production):
     * {"status_code":200,"result":{"similarity":99,"status":true}}
     *
     * @return array{matched: bool|null, score: float|null, raw: string}
     */
    public function parseVerificationResponse(string $raw): array
    {
        $trimmed = trim($raw);
        $decoded = json_decode($trimmed, true);

        if (is_array($decoded)) {
            if (isset($decoded['status_code']) && (int) $decoded['status_code'] !== 200) {
                return ['matched' => false, 'score' => null, 'raw' => $trimmed];
            }

            $payload = isset($decoded['result']) && is_array($decoded['result'])
                ? $decoded['result']
                : $decoded;

            $matched = $this->extractBool($payload, ['status', 'matched', 'match', 'verified', 'success', 'is_match', 'isMatch']);
            if ($matched === null) {
                $matched = $this->extractBool($decoded, ['status', 'matched', 'match', 'verified', 'success']);
            }

            $score = $this->extractScore($payload);
            if ($score === null) {
                $score = $this->extractScore($decoded);
            }

            if ($matched === null && $score !== null) {
                $matched = $score >= (float) config('insightface.min_score', 70);
            }

            return ['matched' => $matched, 'score' => $score, 'raw' => $trimmed];
        }

        $lower = strtolower($trimmed);
        if (in_array($lower, ['true', '1', 'match', 'matched', 'yes'], true)) {
            return ['matched' => true, 'score' => null, 'raw' => $trimmed];
        }
        if (in_array($lower, ['false', '0', 'mismatch', 'no'], true)) {
            return ['matched' => false, 'score' => null, 'raw' => $trimmed];
        }

        if (is_numeric($trimmed)) {
            $score = (float) $trimmed;
            return [
                'matched' => $score >= (float) config('insightface.min_score', 70),
                'score' => $score,
                'raw' => $trimmed,
            ];
        }

        return ['matched' => null, 'score' => null, 'raw' => $trimmed];
    }

    /**
     * @return array{path: string, filename: string, is_temp: bool}|null
     */
    private function resolveImageFile(Request $request): ?array
    {
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            /** @var UploadedFile $uploaded */
            $uploaded = $request->file('image');
            return [
                'path' => $uploaded->getRealPath(),
                'filename' => $uploaded->getClientOriginalName() ?: 'selfie.jpg',
                'is_temp' => false,
            ];
        }

        $image = $request->input('image');
        if (is_string($image) && strpos($image, 'data:image') === 0) {
            $parts = explode(';base64,', $image, 2);
            if (count($parts) !== 2) {
                return null;
            }
            $binary = base64_decode($parts[1], true);
            if ($binary === false || strlen($binary) === 0) {
                return null;
            }
            $tmp = tempnam(sys_get_temp_dir(), 'if_');
            file_put_contents($tmp, $binary);
            return ['path' => $tmp, 'filename' => 'selfie.jpg', 'is_temp' => true];
        }

        return null;
    }

    /**
     * @param array{path: string, filename: string, is_temp: bool}|null $file
     */
    private function cleanupTempFile(?array $file): void
    {
        if ($file && ($file['is_temp'] ?? false) && is_file($file['path'])) {
            @unlink($file['path']);
        }
    }

    private function url(string $path): string
    {
        return config('insightface.base_url') . $path;
    }

    /** InsightFace (FastAPI) expects `name` as query param, not multipart body. */
    private function urlWithName(string $path, string $nik): string
    {
        return $this->url($path) . '?' . http_build_query(['name' => $nik]);
    }

    private function responseIndicatesFailure(string $raw): bool
    {
        $decoded = json_decode(trim($raw), true);
        if (!is_array($decoded)) {
            return false;
        }
        if (isset($decoded['status_code']) && (int) $decoded['status_code'] !== 200) {
            return true;
        }
        if (isset($decoded['error']) && $decoded['error']) {
            return true;
        }
        if (isset($decoded['success']) && $decoded['success'] === false) {
            return true;
        }
        if (isset($decoded['detail']) && is_array($decoded['detail'])) {
            return true;
        }
        return false;
    }

    private function extractBool(array $data, array $keys): ?bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && is_bool($data[$key])) {
                return $data[$key];
            }
            if (array_key_exists($key, $data) && is_numeric($data[$key])) {
                return (bool) $data[$key];
            }
        }
        return null;
    }

    private function extractScore(array $data): ?float
    {
        foreach (['score', 'similarity', 'confidence', 'distance'] as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return (float) $data[$key];
            }
        }
        if (isset($data['result']) && is_array($data['result'])) {
            return $this->extractScore($data['result']);
        }
        return null;
    }
}
