<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmDeviceToken;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FcmDeviceController extends Controller
{
    /**
     * POST /api/notifications/register-device
     * Daftarkan FCM device token untuk user yang login (untuk terima push notification).
     * Body: fcm_token (wajib), platform (opsional: android|ios|web), device_name (opsional).
     */
    public function registerDevice(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string|max:512',
            'platform' => 'nullable|string|in:android,ios,web',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();
        $token = trim($validated['fcm_token']);

        $device = FcmDeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'token' => $token,
            ],
            [
                'platform' => $validated['platform'] ?? null,
                'device_name' => $validated['device_name'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device berhasil didaftarkan untuk notifikasi.',
            'data' => [
                'id' => $device->id,
                'platform' => $device->platform,
            ],
        ]);
    }

    /**
     * DELETE /api/notifications/register-device
     * Hapus FCM token (mis. saat logout di device).
     * Body atau query: fcm_token (wajib).
     */
    public function unregisterDevice(Request $request)
    {
        $token = $request->input('fcm_token') ?? $request->query('fcm_token');
        if (empty($token) || !is_string($token)) {
            return response()->json([
                'success' => false,
                'message' => 'fcm_token wajib diisi.',
            ], 400);
        }

        $deleted = FcmDeviceToken::where('user_id', Auth::id())
            ->where('token', trim($token))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted ? 'Device dihapus dari notifikasi.' : 'Token tidak ditemukan.',
        ]);
    }

    /**
     * POST /api/notifications/test
     * Kirim notifikasi uji ke semua device user yang login. Untuk testing di dev sebelum production.
     */
    public function sendTest(Request $request)
    {
        $user = Auth::user();
        $fcm = app(FcmService::class);

        $title = $request->input('title', 'Test dari SIKAT');
        $body = $request->input('body', 'Ini notifikasi uji. Jika Anda menerima ini, FCM dev berjalan dengan baik.');

        $result = $fcm->sendToUser($user->id, $title, $body, [
            'type' => 'test',
            'env' => app()->environment(),
        ]);

        if ($result['sent'] === 0 && $result['failed'] === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada device terdaftar untuk user ini. Daftarkan dulu via POST /api/notifications/register-device dari aplikasi.',
                'data' => ['sent' => 0, 'failed' => 0],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi uji dikirim.',
            'data' => [
                'sent' => $result['sent'],
                'failed' => $result['failed'],
            ],
        ]);
    }
}
