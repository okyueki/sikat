<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\User;
use App\Services\PortalSifastSsoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class PortalSifastSsoController extends Controller
{
    public function __construct(
        private PortalSifastSsoService $sso
    ) {
    }

    /**
     * GET /sso/portalsifast?token=...&redirect=/surat_masuk
     * Login otomatis dari Portal Sifast (SSO via NIK).
     */
    public function handle(Request $request)
    {
        if (! $this->sso->isConfigured()) {
            abort(503, 'Integrasi Portal Sifast belum dikonfigurasi.');
        }

        try {
            $payload = $this->sso->validateToken($request->query('token'));
            $redirect = $this->sso->assertSafeRedirect($request->query('redirect'));
        } catch (InvalidArgumentException $e) {
            abort(403, $e->getMessage());
        }

        $user = User::query()
            ->where('username', $payload['nik'])
            ->first();

        if (! $user) {
            abort(403, 'Akun SIKAT tidak ditemukan untuk NIK ini.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        $this->sso->markNonceUsed($payload['nonce']);

        AuditTrail::log(
            'login',
            'sso',
            "User {$user->name} ({$user->username}) login via Portal Sifast SSO",
            $user->id,
            null,
            [
                'nik' => $payload['nik'],
                'redirect' => $redirect,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            'users'
        );

        return redirect($redirect);
    }
}
