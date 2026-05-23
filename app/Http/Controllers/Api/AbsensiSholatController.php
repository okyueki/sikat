<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiSholat;
use App\Models\MasjidToken;
use App\Models\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

/**
 * API Absensi Sholat (QR + Geolocation).
 * Satu QR statis untuk masjid; masa berlaku token ditentukan admin/takmir.
 * Config lokasi dari config/masjid.php (tidak memakai config presensi).
 */
class AbsensiSholatController extends Controller
{
    /**
     * GET /api/absensi-sholat/config
     * Mengembalikan lat, lng, radius dari config masjid untuk validasi di client.
     */
    public function config(Request $request)
    {
        $data = Cache::remember('api.absensi_sholat.config', 600, function () {
            return [
                'target_latitude' => config('masjid.target_latitude'),
                'target_longitude' => config('masjid.target_longitude'),
                'allowed_radius_meter' => config('masjid.allowed_radius_meter'),
            ];
        });
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * POST /api/absensi-sholat/scan
     * Body: token (dari QR), latitude, longitude, jenis_sholat (optional), is_mock_location (optional).
     */
    public function scan(Request $request)
    {
        $pegawai = $this->getPegawaiFromAuth();
        if ($pegawai instanceof \Illuminate\Http\JsonResponse) {
            return $pegawai;
        }

        $key = 'absensi-sholat-scan:' . $request->ip() . ':' . (Auth::id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 15)) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan. Coba lagi nanti.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'jenis_sholat' => 'nullable|string|in:subuh,dzuhur,ashar,maghrib,isya,sunnah',
            'is_mock_location' => 'boolean',
        ], [
            'token.required' => 'Token dari QR wajib.',
            'latitude.required' => 'Lokasi latitude wajib.',
            'longitude.required' => 'Lokasi longitude wajib.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->boolean('is_mock_location')) {
            Log::warning('Fake GPS absensi sholat', ['user_id' => Auth::id(), 'nik' => $pegawai->nik]);
            return response()->json([
                'success' => false,
                'message' => 'Fake GPS terdeteksi. Absensi tidak dapat dilakukan.',
                'is_fake_gps' => true,
            ], 403);
        }

        $token = $request->input('token');
        if (!MasjidToken::isValid($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau telah kedaluwarsa.',
            ], 400);
        }

        $lat = (float) $request->latitude;
        $lng = (float) $request->longitude;
        $targetLat = config('masjid.target_latitude');
        $targetLng = config('masjid.target_longitude');
        $allowedRadius = config('masjid.allowed_radius_meter');
        $distance = $this->calculateDistance($lat, $lng, $targetLat, $targetLng);
        if ($distance > $allowedRadius) {
            return response()->json([
                'success' => false,
                'message' => 'Anda berada di luar radius masjid. Jarak: ' . round($distance) . ' m (maks ' . $allowedRadius . ' m).',
                'distance_meter' => round($distance, 2),
                'allowed_radius_meter' => $allowedRadius,
            ], 400);
        }

        $jenisSholat = $request->input('jenis_sholat', 'sunnah');
        $today = Carbon::today('Asia/Jakarta');

        $sudahAbsen = AbsensiSholat::where('nik', $pegawai->nik)
            ->whereDate('waktu_absen', $today)
            ->where('jenis_sholat', $jenisSholat)
            ->exists();

        if ($sudahAbsen) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan absensi sholat ' . $jenisSholat . ' untuk hari ini.',
            ], 409);
        }

        $waktuAbsen = Carbon::now('Asia/Jakarta');
        AbsensiSholat::create([
            'nik' => $pegawai->nik,
            'waktu_absen' => $waktuAbsen,
            'jenis_sholat' => $jenisSholat,
            'token_used' => $token,
        ]);

        Log::info('Absensi sholat via API', [
            'nik' => $pegawai->nik,
            'jenis_sholat' => $jenisSholat,
            'waktu_absen' => $waktuAbsen->toDateTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Absensi sholat berhasil dicatat.',
            'data' => [
                'waktu_absen' => $waktuAbsen->toIso8601String(),
                'jenis_sholat' => $jenisSholat,
            ],
        ]);
    }

    /**
     * GET /api/absensi-sholat/riwayat
     * Query: bulan, tahun. Riwayat absen sholat user yang login.
     */
    public function riwayat(Request $request)
    {
        $pegawai = $this->getPegawaiFromAuth();
        if ($pegawai instanceof \Illuminate\Http\JsonResponse) {
            return $pegawai;
        }

        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);
        $start = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $list = AbsensiSholat::where('nik', $pegawai->nik)
            ->whereBetween('waktu_absen', [$start, $end])
            ->orderBy('waktu_absen', 'desc')
            ->get()
            ->map(function ($row) {
                return [
                    'tanggal' => Carbon::parse($row->waktu_absen)->format('Y-m-d'),
                    'waktu_absen' => $row->waktu_absen ? Carbon::parse($row->waktu_absen)->toIso8601String() : null,
                    'jenis_sholat' => $row->jenis_sholat ?? 'sunnah',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'riwayat' => $list,
            ],
        ]);
    }

    /**
     * GET /api/absensi-sholat/rekap-bulanan
     * Query: bulan, tahun. Rekap user sendiri: total dan per jenis sholat.
     */
    public function rekapBulanan(Request $request)
    {
        $pegawai = $this->getPegawaiFromAuth();
        if ($pegawai instanceof \Illuminate\Http\JsonResponse) {
            return $pegawai;
        }

        $bulan = (int) $request->get('bulan', Carbon::now()->month);
        $tahun = (int) $request->get('tahun', Carbon::now()->year);
        $start = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $query = AbsensiSholat::where('nik', $pegawai->nik)
            ->whereBetween('waktu_absen', [$start, $end]);

        $total = (clone $query)->count();
        $perJenis = (clone $query)->selectRaw('jenis_sholat, count(*) as jumlah')
            ->groupBy('jenis_sholat')
            ->pluck('jumlah', 'jenis_sholat')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'total_kehadiran' => $total,
                'per_jenis_sholat' => $perJenis,
            ],
        ]);
    }

    private function getPegawaiFromAuth()
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }
        $nik = Auth::user()->username;
        $pegawai = Pegawai::where('nik', $nik)->first();
        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Data pegawai tidak ditemukan.',
            ], 404);
        }
        return $pegawai;
    }

    /**
     * Jarak dalam meter (Haversine). Pakai config masjid, bukan presensi.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371e3;
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lon2 - $lon1);
        $a = sin($deltaPhi / 2) * sin($deltaPhi / 2) +
             cos($phi1) * cos($phi2) * sin($deltaLambda / 2) * sin($deltaLambda / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }
}
