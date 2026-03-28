<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppController extends Controller
{
    /**
     * GET /api/app/version
     * Mengembalikan versi minimum aplikasi mobile yang diizinkan.
     * Endpoint ini tanpa autentikasi (public) untuk force update check.
     */
    public function version(Request $request)
    {
        $minVersion = config('app.min_mobile_version', null);
        $playStoreUrl = config('app.play_store_url', 'https://play.google.com/store/apps/details?id=com.sikat.mobile');

        // Jika min_mobile_version null, force update tidak aktif
        if ($minVersion === null) {
            return response()->json([
                'success' => true,
                'data' => [
                    'min_app_version' => null,
                    'force_update' => false,
                    'message' => 'Force update tidak aktif.',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'min_app_version' => $minVersion,
                'force_update' => true,
                'force_update_message' => 'Aplikasi perlu diperbarui ke versi terbaru. Silakan update dari Play Store.',
                'play_store_url' => $playStoreUrl,
            ],
        ]);
    }
}
