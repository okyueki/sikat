<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class PortalSifastSsoService
{
    /** @var list<string> */
    private const ALLOWED_REDIRECT_PREFIXES = [
        '/surat_masuk',
        '/surat_keluar',
        '/surat_edaran',
        '/spo',
        '/cuti',
        '/ijin',
        '/pengajuan_lembur',
        '/verifikasi_pengajuan_libur',
        '/verifikasi_pengajuan_lembur',
        '/sifat_surat',
        '/klasifikasi_surat',
        '/template_surat',
        '/surat/tampillampiran',
        '/pengajuan_libur',
        '/pengajuan_lembur',
    ];

  private const NONCE_CACHE_PREFIX = 'portalsifast_sso_nonce:';

    private const NONCE_TTL_SECONDS = 120;

    public function isConfigured(): bool
    {
        $secret = config('services.portalsifast.sso_secret');

        return is_string($secret) && $secret !== '';
    }

    /**
     * @return array{nik: string, exp: int, nonce: string}
     */
    public function validateToken(?string $token): array
    {
        if (! $this->isConfigured()) {
            throw new InvalidArgumentException('Integrasi Portal Sifast belum dikonfigurasi.');
        }

        if ($token === null || $token === '') {
            throw new InvalidArgumentException('Token SSO tidak ditemukan.');
        }

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Format token SSO tidak valid.');
        }

        [$payloadB64, $signature] = $parts;

        $expectedSignature = hash_hmac(
            'sha256',
            $payloadB64,
            (string) config('services.portalsifast.sso_secret')
        );

        if (! hash_equals($expectedSignature, $signature)) {
            throw new InvalidArgumentException('Signature token SSO tidak valid.');
        }

        $payloadJson = $this->base64UrlDecode($payloadB64);
        if ($payloadJson === false) {
            throw new InvalidArgumentException('Payload token SSO tidak dapat dibaca.');
        }

        $payload = json_decode($payloadJson, true);
        if (! is_array($payload)) {
            throw new InvalidArgumentException('Payload token SSO tidak valid.');
        }

        $nik = $payload['nik'] ?? null;
        $exp = $payload['exp'] ?? null;
        $nonce = $payload['nonce'] ?? null;

        if (! is_string($nik) || $nik === '') {
            throw new InvalidArgumentException('NIK pada token SSO tidak valid.');
        }

        if (! is_int($exp) && ! (is_string($exp) && ctype_digit($exp))) {
            throw new InvalidArgumentException('Expiry token SSO tidak valid.');
        }

        $exp = (int) $exp;
        if ($exp < time()) {
            throw new InvalidArgumentException('Token SSO sudah kedaluwarsa.');
        }

        if (! is_string($nonce) || $nonce === '') {
            throw new InvalidArgumentException('Nonce token SSO tidak valid.');
        }

        if ($this->isNonceUsed($nonce)) {
            throw new InvalidArgumentException('Token SSO sudah pernah digunakan.');
        }

        return [
            'nik' => $nik,
            'exp' => $exp,
            'nonce' => $nonce,
        ];
    }

    public function markNonceUsed(string $nonce): void
    {
        Cache::put(self::NONCE_CACHE_PREFIX.$nonce, true, self::NONCE_TTL_SECONDS);
    }

    public function assertSafeRedirect(?string $redirect): string
    {
        $redirect = $redirect !== null && $redirect !== '' ? $redirect : '/surat_masuk';

        if (! str_starts_with($redirect, '/')) {
            throw new InvalidArgumentException('Tujuan redirect tidak diizinkan.');
        }

        if (str_contains($redirect, '://') || str_contains($redirect, '//')) {
            throw new InvalidArgumentException('Tujuan redirect tidak diizinkan.');
        }

        foreach (self::ALLOWED_REDIRECT_PREFIXES as $prefix) {
            if ($redirect === $prefix || str_starts_with($redirect, $prefix.'/')) {
                return $redirect;
            }
        }

        throw new InvalidArgumentException('Tujuan redirect tidak diizinkan.');
    }

    private function isNonceUsed(string $nonce): bool
    {
        return Cache::has(self::NONCE_CACHE_PREFIX.$nonce);
    }

  private function base64UrlDecode(string $payloadB64): string|false
    {
        $remainder = strlen($payloadB64) % 4;
        if ($remainder > 0) {
            $payloadB64 .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($payloadB64, '-_', '+/'), true);
    }
}
