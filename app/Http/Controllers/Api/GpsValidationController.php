<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GpsValidationController extends Controller
{

    /**
     * Validasi GPS untuk presensi (dari Android native app)
     * Endpoint ini menerima lokasi dari Android yang sudah di-validasi native
     */
    public function validateGps(Request $request)
    {
        try {
            // Validasi input
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'is_mock_location' => 'boolean', // Flag dari Android apakah ini fake GPS
                'accuracy' => 'numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data lokasi tidak valid',
                    'errors' => $validator->errors()
                ], 400);
            }

            $lat = $request->latitude;
            $lng = $request->longitude;
            $isMockLocation = $request->input('is_mock_location', false);
            $accuracy = $request->input('accuracy', 0);

            // Cek apakah ini fake GPS (dari Android native detection)
            if ($isMockLocation) {
                Log::warning('Fake GPS terdeteksi', [
                    'user_id' => Auth::id(),
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'Fake GPS terdeteksi. Presensi tidak dapat dilakukan.',
                    'is_fake_gps' => true,
                ], 403);
            }

            $targetLat = config('presensi.target_latitude');
            $targetLng = config('presensi.target_longitude');
            $allowedRadius = config('presensi.allowed_radius_meter');
            $distance = $this->calculateDistance($lat, $lng, $targetLat, $targetLng);
            $isValid = $distance <= $allowedRadius;

            // Log aktivitas
            Log::info('Validasi GPS', [
                'user_id' => Auth::id(),
                'latitude' => $lat,
                'longitude' => $lng,
                'distance' => round($distance, 2),
                'is_valid' => $isValid,
                'is_mock' => $isMockLocation,
                'accuracy' => $accuracy,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'valid' => $isValid,
                'distance' => round($distance, 2),
                'allowed_radius' => $allowedRadius,
                'message' => $isValid 
                    ? "Lokasi valid. Jarak: " . round($distance, 2) . " meter"
                    : "Lokasi tidak valid. Jarak: " . round($distance, 2) . " meter (maksimal " . $allowedRadius . " meter)",
                'is_fake_gps' => false,
            ]);

        } catch (\Exception $e) {
            Log::error('Error validasi GPS', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat validasi GPS'
            ], 500);
        }
    }

    /**
     * Hitung jarak menggunakan Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371e3; // Radius bumi dalam meter
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lon2 - $lon1);

        $a = sin($Δφ/2) * sin($Δφ/2) +
             cos($φ1) * cos($φ2) *
             sin($Δλ/2) * sin($Δλ/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $R * $c; // Jarak dalam meter
    }
}
