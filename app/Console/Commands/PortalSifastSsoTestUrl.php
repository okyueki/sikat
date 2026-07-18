<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PortalSifastSsoTestUrl extends Command
{
    protected $signature = 'portalsifast:sso-test-url
                            {nik : NIK pegawai (= users.username di SIKAT)}
                            {--redirect=/surat_masuk : Path tujuan setelah login}';

    protected $description = 'Generate URL SSO sekali pakai untuk uji /sso/portalsifast (development/staging)';

    public function handle(): int
    {
        $secret = config('services.portalsifast.sso_secret');
        if (! is_string($secret) || $secret === '') {
            $this->error('PORTALSIFAST_SIKAT_SSO_SECRET belum diisi di .env');

            return self::FAILURE;
        }

        $nik = $this->argument('nik');
        $redirect = $this->option('redirect');

        $payload = [
            'nik' => $nik,
            'exp' => now()->addSeconds(120)->timestamp,
            'nonce' => (string) Str::uuid(),
        ];

        $payloadB64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $payloadB64, $secret);
        $token = $payloadB64.'.'.$signature;

        $url = rtrim(config('app.url'), '/')
            .'/sso/portalsifast?token='.urlencode($token)
            .'&redirect='.urlencode($redirect);

        $this->info('Token berlaku ~2 menit, sekali pakai.');
        $this->line('NIK: '.$nik);
        $this->line('Redirect: '.$redirect);
        $this->newLine();
        $this->line($url);

        return self::SUCCESS;
    }
}
