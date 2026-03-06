<?php

namespace App\Services;

use App\Models\FcmDeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /** @var array|null Credentials dari file JSON (lazy load). */
    private $credentials;

    /**
     * Baca credentials dari file JSON. Return null jika file tidak ada/invalid.
     */
    private function getCredentials(): ?array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $path = config('fcm.credentials_path');
        if (empty($path) || !is_file($path)) {
            Log::warning('FCM: File credentials tidak ditemukan.', ['path' => $path]);
            return null;
        }

        $json = @file_get_contents($path);
        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);
        if (!$data || empty($data['client_email']) || empty($data['private_key'])) {
            Log::warning('FCM: Format file credentials tidak valid.');
            return null;
        }

        $this->credentials = $data;
        return $this->credentials;
    }

    /**
     * Dapatkan OAuth2 access token (di-cache sampai mendekati expiry).
     */
    private function getAccessToken(): ?string
    {
        $cacheKey = 'fcm_access_token';
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $cred = $this->getCredentials();
        if (!$cred) {
            return null;
        }

        $jwt = $this->createJwtForGoogle($cred);
        if (!$jwt) {
            return null;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (!$response->successful()) {
            Log::warning('FCM: Gagal dapat access token.', ['body' => $response->body()]);
            return null;
        }

        $data = $response->json();
        $token = $data['access_token'] ?? null;
        $expiresIn = (int) ($data['expires_in'] ?? 3600);
        if ($token && $expiresIn > 0) {
            Cache::put($cacheKey, $token, $expiresIn - 60); // cache 1 menit sebelum expiry
        }

        return $token;
    }

    /**
     * Buat JWT untuk Google OAuth2 (RS256).
     */
    private function createJwtForGoogle(array $cred): ?string
    {
        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => $cred['client_email'],
            'sub' => $cred['client_email'],
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ];

        $b64Header = $this->base64UrlEncode(json_encode($header));
        $b64Payload = $this->base64UrlEncode(json_encode($payload));
        $signatureInput = $b64Header . '.' . $b64Payload;

        $privateKey = $cred['private_key'];
        if (strpos($privateKey, '\\n') !== false) {
            $privateKey = str_replace('\\n', "\n", $privateKey);
        }
        $key = openssl_pkey_get_private($privateKey);
        if (!$key) {
            Log::warning('FCM: Invalid private key.');
            return null;
        }

        $signature = '';
        openssl_sign($signatureInput, $signature, $key, OPENSSL_ALGO_SHA256);
        openssl_free_key($key);
        $b64Signature = $this->base64UrlEncode($signature);

        return $signatureInput . '.' . $b64Signature;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Kirim notifikasi push ke satu device token (FCM HTTP v1).
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $cred = $this->getCredentials();
        if (!$cred) {
            return false;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }

        $projectId = $cred['project_id'] ?? null;
        if (empty($projectId)) {
            Log::warning('FCM: project_id tidak ada di credentials.');
            return false;
        }

        $message = [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ];
        if (!empty($data)) {
            $message['data'] = array_map('strval', $data);
        }

        $url = sprintf(config('fcm.v1_endpoint'), $projectId);
        $response = Http::withToken($accessToken)
            ->post($url, ['message' => $message]);

        if (!$response->successful()) {
            Log::warning('FCM send failed', [
                'token_preview' => substr($token, 0, 20) . '...',
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * Kirim notifikasi ke semua device milik satu user.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): array
    {
        $tokens = FcmDeviceToken::where('user_id', $userId)
            ->whereNotNull('token')
            ->pluck('token')
            ->unique()
            ->values()
            ->all();

        $sent = 0;
        $failed = 0;
        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $title, $body, $data)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Kirim ke banyak token (satu per request di v1; batch bisa ditambah nanti).
     */
    public function sendMulticast(array $tokens, string $title, string $body, array $data = []): array
    {
        $tokens = array_unique(array_values($tokens));
        $success = 0;
        $failure = 0;
        foreach ($tokens as $token) {
            if ($this->sendToToken($token, $title, $body, $data)) {
                $success++;
            } else {
                $failure++;
            }
        }
        return ['success' => $success, 'failure' => $failure];
    }
}
