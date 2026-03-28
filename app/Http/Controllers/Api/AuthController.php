<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\AuditTrail;

class AuthController extends Controller
{
    /**
     * POST /api/login
     * Login untuk aplikasi (mobile/API). Mengembalikan token Sanctum.
     * Body: username, password
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('username', 'password'))) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password salah.'],
            ]);
        }

        $user = Auth::user();
        $user->tokens()->where('name', 'presensi-api')->delete();
        $token = $user->createToken('presensi-api')->plainTextToken;

        // Audit trail untuk login
        AuditTrail::log('login', 'api', "User {$user->name} ({$user->username}) berhasil login via API", $user->id, null, [
            'username' => $user->username,
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ], 'users');

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                ],
            ],
        ]);
    }

    /**
     * POST /api/logout
     * Revoke token saat ini (logout).
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        // Audit trail untuk logout
        AuditTrail::log('logout', 'api', "User {$user->name} ({$user->username}) berhasil logout via API", $user->id, null, [
            'username' => $user->username,
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ], 'users');

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
