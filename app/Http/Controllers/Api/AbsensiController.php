<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Models\Pegawai;
use App\Models\TemporaryPresensi;
use App\Models\RekapPresensi;
use App\Models\JamJaga;
use App\Models\JamMasuk;
use App\Models\JadwalPegawai;
use App\Models\JadwalTambahan;
use App\Models\SetKeterlambatan;
use Carbon\Carbon;

class AbsensiController extends Controller
{

    /**
     * GET /api/absensi/jadwal-hari-ini
     * Jadwal shift & jam masuk/pulang hari ini untuk user yang login.
     */
    public function jadwalHariIni(Request $request)
    {
        $pegawai = $this->getPegawaiFromAuth();
        if ($pegawai instanceof \Illuminate\Http\JsonResponse) {
            return $pegawai;
        }

        $shiftHariIni = $this->getShiftHariIni($pegawai->id);
        $jadwalAda = $shiftHariIni && !empty($shiftHariIni['shift']);
        $jamMasuk = null;
        $jamPulang = null;
        $shift = null;

        if ($jadwalAda) {
            $shift = trim($shiftHariIni['shift']);
            $jamJaga = $this->getJamJagaFromCache($shift);
            if (!$jamJaga) {
                $jamMasukModel = $this->getJamMasukFromCache($shift);
                if ($jamMasukModel) {
                    $jamMasuk = $jamMasukModel->jam_masuk ?? null;
                    $jamPulang = $jamMasukModel->jam_pulang ?? null;
                }
            } else {
                $jamMasuk = $jamJaga->jam_masuk ?? null;
                $jamPulang = $jamJaga->jam_pulang ?? null;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'jadwal_ada' => $jadwalAda,
                'shift' => $shift,
                'jam_masuk' => $jamMasuk,
                'jam_pulang' => $jamPulang,
            ],
        ]);
    }

    /**
     * GET /api/absensi/status-hari-ini
     * Status presensi hari ini: belum | datang | selesai.
     */
    public function statusHariIni(Request $request)
    {
        $pegawai = $this->getPegawaiFromAuth();
        if ($pegawai instanceof \Illuminate\Http\JsonResponse) {
            return $pegawai;
        }

        // Cek dari temporary dulu (presensi via API/web belum diverifikasi)
        $today = Carbon::today('Asia/Jakarta');
        $presensiHariIni = TemporaryPresensi::where('id', $pegawai->id)
            ->whereDate('jam_datang', $today)
            ->first();

        // Jika tidak ada di temporary, cek rekap (sudah pulang & tercatat)
        if (!$presensiHariIni) {
            $presensiHariIni = RekapPresensi::where('id', $pegawai->id)
                ->whereDate('jam_datang', $today)
                ->first();
        }

        $status = 'belum';
        $jam_datang = null;
        $jam_pulang = null;

        if ($presensiHariIni) {
            $jam_datang = $presensiHariIni->jam_datang ? Carbon::parse($presensiHariIni->jam_datang)->toIso8601String() : null;
            $jam_pulang = $presensiHariIni->jam_pulang ? Carbon::parse($presensiHariIni->jam_pulang)->toIso8601String() : null;
            $status = $presensiHariIni->jam_pulang ? 'selesai' : 'datang';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $status,
                'jam_datang' => $jam_datang,
                'jam_pulang' => $jam_pulang,
            ],
        ]);
    }

    /**
     * GET /api/absensi/config
     * Konfigurasi lokasi presensi (titik & radius) untuk validasi di client. Cache 10 menit.
     */
    public function config(Request $request)
    {
        $data = Cache::remember('api.absensi.config', 600, function () {
            return [
                'target_latitude' => config('presensi.target_latitude'),
                'target_longitude' => config('presensi.target_longitude'),
                'allowed_radius_meter' => config('presensi.allowed_radius_meter'),
            ];
        });
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * POST /api/absensi/submit
     * Submit presensi (datang atau pulang otomatis terdeteksi).
     * Body: image (required, file atau base64), latitude, longitude, is_mock_location (optional).
     */
    public function submit(Request $request)
    {
        $pegawai = $this->getPegawaiFromAuth();
        if ($pegawai instanceof \Illuminate\Http\JsonResponse) {
            return $pegawai;
        }

        $key = 'presensi-api:' . $request->ip() . ':' . (Auth::id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan. Coba lagi nanti.',
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $validator = Validator::make($request->all(), [
            'image' => 'required',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_mock_location' => 'boolean',
        ], [
            'image.required' => 'Foto presensi wajib.',
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

        $isMock = $request->boolean('is_mock_location');
        if ($isMock) {
            Log::warning('Fake GPS via API', ['user_id' => Auth::id(), 'pegawai_id' => $pegawai->id]);
            return response()->json([
                'success' => false,
                'message' => 'Fake GPS terdeteksi. Presensi tidak dapat dilakukan.',
                'is_fake_gps' => true,
            ], 403);
        }

        $lat = (float) $request->latitude;
        $lng = (float) $request->longitude;
        $targetLat = config('presensi.target_latitude');
        $targetLng = config('presensi.target_longitude');
        $allowedRadius = config('presensi.allowed_radius_meter');
        $distance = $this->calculateDistance($lat, $lng, $targetLat, $targetLng);
        if ($distance > $allowedRadius) {
            return response()->json([
                'success' => false,
                'message' => 'Anda berada di luar radius presensi. Jarak: ' . round($distance) . ' m (maks ' . $allowedRadius . ' m).',
                'distance_meter' => round($distance, 2),
                'allowed_radius_meter' => $allowedRadius,
            ], 400);
        }

        $shiftHariIni = $this->getShiftHariIni($pegawai->id);
        if (!$shiftHariIni) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada jadwal shift hari ini.',
            ], 400);
        }

        $jamJaga = $this->getJamJagaFromCache(trim($shiftHariIni['shift']));
        if (!$jamJaga) {
            $jamMasukModel = $this->getJamMasukFromCache(trim($shiftHariIni['shift']));
            if (!$jamMasukModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data jam shift tidak ditemukan.',
                ], 400);
            }
            $jamJaga = (object) [
                'shift' => $jamMasukModel->shift,
                'jam_masuk' => $jamMasukModel->jam_masuk,
                'jam_pulang' => $jamMasukModel->jam_pulang,
            ];
        }

        DB::beginTransaction();
        try {
            $today = Carbon::today('Asia/Jakarta');
            // Cek record hari ini (pakai timezone WIB agar konsisten dengan jam_datang yang disimpan)
            $temporaryPresensiHariIni = TemporaryPresensi::where('id', $pegawai->id)
                ->whereDate('jam_datang', $today)
                ->lockForUpdate()
                ->first();

            if ($temporaryPresensiHariIni && $temporaryPresensiHariIni->jam_pulang) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan presensi datang dan pulang hari ini.',
                ], 400);
            }

            if ($temporaryPresensiHariIni && !$temporaryPresensiHariIni->jam_pulang) {
                // Ada record hari ini, belum pulang → presensi pulang
                $result = $this->doPresensiPulang($request, $pegawai, $temporaryPresensiHariIni, $jamJaga, false);
            } else {
                // Tidak ada record hari ini → cek dulu apakah ada record tertinggal (datang tanpa pulang)
                $recordTertinggal = TemporaryPresensi::where('id', $pegawai->id)
                    ->whereNull('jam_pulang')
                    ->where('jam_datang', '<', $today->copy()->startOfDay())
                    ->lockForUpdate()
                    ->orderBy('jam_datang', 'asc')
                    ->first();

                if ($recordTertinggal) {
                    // Tutup record tertinggal (closing) — hanya pulang. Pegawai wajib presensi lagi untuk datang hari ini.
                    Log::info('Record presensi tertinggal ditemukan, closing (pulang)', [
                        'pegawai_id' => $pegawai->id,
                        'jam_datang_tertinggal' => $recordTertinggal->jam_datang?->toDateTimeString(),
                    ]);
                    $jamJagaTertinggal = $this->getJamJagaForShift($recordTertinggal->shift)
                        ?? $jamJaga; // fallback ke jadwal hari ini jika shift lama tidak ditemukan
                    $result = $this->doPresensiPulang($request, $pegawai, $recordTertinggal, $jamJagaTertinggal, true);
                } else {
                    // Sudah presensi lengkap hari ini di rekap? Hanya boleh lagi jika ada jadwal tambahan.
                    $sudahLengkapHariIni = RekapPresensi::where('id', $pegawai->id)
                        ->whereDate('jam_datang', $today)
                        ->whereNotNull('jam_pulang')
                        ->exists();

                    if ($sudahLengkapHariIni) {
                        $shiftTambahan = $this->getShiftTambahanHariIni($pegawai->id);
                        if (empty($shiftTambahan)) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Anda sudah melakukan presensi datang dan pulang hari ini. Tidak dapat presensi lagi kecuali ada jadwal tambahan.',
                            ], 400);
                        }
                        $jamJagaTambahan = $this->getJamJagaForShift($shiftTambahan);
                        if (!$jamJagaTambahan) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Data jam shift jadwal tambahan tidak ditemukan.',
                            ], 400);
                        }
                        $result = $this->doPresensiDatang($request, $pegawai, $jamJagaTambahan);
                    } else {
                        $result = $this->doPresensiDatang($request, $pegawai, $jamJaga);
                    }
                }
            }

            DB::commit();
            return response()->json($result);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API Presensi error', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
                'pegawai_id' => $pegawai->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses presensi.',
            ], 500);
        }
    }

    /**
     * GET /api/absensi/riwayat
     * Riwayat presensi user. Query: bulan, tahun (default bulan/tahun ini).
     */
    public function riwayat(Request $request)
    {
        $pegawai = $this->getPegawaiFromAuth();
        if ($pegawai instanceof \Illuminate\Http\JsonResponse) {
            return $pegawai;
        }

        $bulan = $request->get('bulan', Carbon::now()->month);
        $tahun = $request->get('tahun', Carbon::now()->year);
        $bulan = (int) $bulan;
        $tahun = (int) $tahun;

        $start = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $list = RekapPresensi::where('id', $pegawai->id)
            ->whereBetween('jam_datang', [$start, $end])
            ->orderBy('jam_datang', 'desc')
            ->get()
            ->map(function ($row) {
                return [
                    'tanggal' => Carbon::parse($row->jam_datang)->format('Y-m-d'),
                    'jam_datang' => $row->jam_datang ? Carbon::parse($row->jam_datang)->toIso8601String() : null,
                    'jam_pulang' => $row->jam_pulang ? Carbon::parse($row->jam_pulang)->toIso8601String() : null,
                    'shift' => $row->shift,
                    'status' => $row->status,
                    'keterlambatan' => $row->keterlambatan,
                    'durasi' => $row->durasi,
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

    /** Daftar JamJaga di-cache 1 jam untuk kurangi query. */
    private function getJamJagaFromCache(string $shift)
    {
        $list = Cache::remember('api.jam_jaga_list', 3600, fn () => JamJaga::all());
        return $list->firstWhere('shift', $shift);
    }

    /** Daftar JamMasuk di-cache 1 jam untuk kurangi query. */
    private function getJamMasukFromCache(string $shift)
    {
        $list = Cache::remember('api.jam_masuk_list', 3600, fn () => JamMasuk::all());
        return $list->firstWhere('shift', $shift);
    }

    /** Ambil jam jaga (jam_masuk, jam_pulang) untuk shift tertentu. */
    private function getJamJagaForShift(string $shift)
    {
        $jamJaga = $this->getJamJagaFromCache(trim($shift));
        if ($jamJaga) {
            return $jamJaga;
        }
        $jamMasukModel = $this->getJamMasukFromCache(trim($shift));
        if ($jamMasukModel) {
            return (object) [
                'shift' => $jamMasukModel->shift,
                'jam_masuk' => $jamMasukModel->jam_masuk,
                'jam_pulang' => $jamMasukModel->jam_pulang,
            ];
        }
        return null;
    }

    /**
     * Ambil shift efektif "hari ini" untuk pegawai.
     * Mendukung shift malam (21:00–07:00) yang dijadwalkan di hari PULANG (besok):
     * jika hari ini tidak ada shift tapi besok ada shift dengan jam_masuk > jam_pulang
     * dan sekarang sudah >= jam_masuk, dianggap shift itu "dimulai hari ini" dan dikembalikan.
     */
    private function getShiftHariIni($pegawaiId)
    {
        $today = Carbon::today('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $currentMonth = $today->format('m');
        $currentYear = $today->format('Y');
        $currentDay = (int) $today->format('j');
        $dayColumn = 'h' . $currentDay;

        $jadwalPegawai = JadwalPegawai::where('id', $pegawaiId)
            ->where('bulan', $currentMonth)
            ->where('tahun', $currentYear)
            ->first();

        if (!$jadwalPegawai) {
            return null;
        }

        $shiftValue = $jadwalPegawai->$dayColumn ?? null;
        if (!empty($shiftValue) && trim($shiftValue) !== '') {
            return ['shift' => trim($shiftValue)];
        }

        // Shift malam: jadwal sering diisi di hari PULANG (besok). Cek shift besok yang melewati tengah malam.
        $tomorrow = $today->copy()->addDay();
        $tomorrowMonth = $tomorrow->format('m');
        $tomorrowYear = $tomorrow->format('Y');
        $tomorrowDay = (int) $tomorrow->format('j');
        $tomorrowColumn = 'h' . $tomorrowDay;

        $jadwalBesok = JadwalPegawai::where('id', $pegawaiId)
            ->where('bulan', $tomorrowMonth)
            ->where('tahun', $tomorrowYear)
            ->first();

        if (!$jadwalBesok) {
            return null;
        }

        $shiftBesok = $jadwalBesok->$tomorrowColumn ?? null;
        if (empty($shiftBesok) || trim($shiftBesok) === '') {
            return null;
        }

        $jamJagaBesok = $this->getJamJagaFromCache(trim($shiftBesok));
        if (!$jamJagaBesok) {
            $jamJagaBesok = $this->getJamMasukFromCache(trim($shiftBesok));
        }
        if (!$jamJagaBesok) {
            return null;
        }

        $jamMasuk = $jamJagaBesok->jam_masuk ?? null;
        $jamPulang = $jamJagaBesok->jam_pulang ?? null;
        if (!$jamMasuk || !$jamPulang) {
            return null;
        }

        $jamMasukCarbon = Carbon::parse($jamMasuk, 'Asia/Jakarta');
        $jamPulangCarbon = Carbon::parse($jamPulang, 'Asia/Jakarta');
        // Shift malam: jam_masuk > jam_pulang (mis. 21:00 > 07:00)
        if ($jamMasukCarbon->gt($jamPulangCarbon)) {
            $nowTime = $now->format('H:i');
            $jamMasukTime = $jamMasukCarbon->format('H:i');
            if ($nowTime >= $jamMasukTime) {
                return ['shift' => trim($shiftBesok)];
            }
        }

        return null;
    }

    /** Shift jadwal tambahan hari ini (untuk presensi kedua jika sudah lengkap di rekap). */
    private function getShiftTambahanHariIni($pegawaiId)
    {
        $today = Carbon::today('Asia/Jakarta');
        $currentMonth = $today->format('m');
        $currentYear = (int) $today->format('Y');
        $currentDay = (int) $today->format('j');
        $dayColumn = 'h' . $currentDay;

        $jadwalTambahan = JadwalTambahan::where('id', $pegawaiId)
            ->where('bulan', $currentMonth)
            ->where('tahun', $currentYear)
            ->first();

        if (!$jadwalTambahan) {
            return null;
        }

        $shiftValue = $jadwalTambahan->$dayColumn ?? null;
        if (empty($shiftValue) || trim((string) $shiftValue) === '') {
            return null;
        }

        return trim($shiftValue);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371e3;
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lon2 - $lon1);
        $a = sin($Δφ/2) * sin($Δφ/2) +
             cos($φ1) * cos($φ2) * sin($Δλ/2) * sin($Δλ/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }

    private function doPresensiDatang(Request $request, $pegawai, $jamJaga)
    {
        $photoPath = $this->handleImageUpload($request, $pegawai);
        if (!$photoPath) {
            throw new \Exception('Gagal menyimpan foto.');
        }

        $now = Carbon::now('Asia/Jakarta');
        $jamMasukShift = Carbon::parse($jamJaga->jam_masuk, 'Asia/Jakarta');
        $keterlambatanData = $this->calculateKeterlambatan($jamMasukShift, $now);

        TemporaryPresensi::create([
            'id' => $pegawai->id,
            'shift' => $jamJaga->shift,
            'jam_datang' => $now,
            'status' => $keterlambatanData['status'],
            'keterlambatan' => $keterlambatanData['keterlambatan'],
            'photo' => $photoPath,
        ]);

        Log::info('Presensi datang via API', [
            'pegawai_id' => $pegawai->id,
            'shift' => $jamJaga->shift,
            'jam_datang' => $now->toDateTimeString(),
            'status' => $keterlambatanData['status'],
        ]);

        return [
            'success' => true,
            'message' => 'Presensi datang berhasil dicatat.',
            'data' => [
                'tipe' => 'datang',
                'jam_datang' => $now->toIso8601String(),
                'status' => $keterlambatanData['status'],
                'keterlambatan' => $keterlambatanData['keterlambatan'],
            ],
        ];
    }

    private function doPresensiPulang(Request $request, $pegawai, $temporaryPresensi, $jamJaga, bool $isClosing = false)
    {
        $jamDatang = Carbon::parse($temporaryPresensi->jam_datang);
        $jamPulang = Carbon::now('Asia/Jakarta');
        $jamPulangJadwal = Carbon::parse($jamJaga->jam_pulang, 'Asia/Jakarta');

        if ($jamPulang->lessThanOrEqualTo($jamDatang)) {
            throw new \Exception('Jam pulang tidak valid.');
        }

        $durasi = $jamPulang->diff($jamDatang)->format('%H:%I:%S');

        // Update temporary dengan jam_pulang & durasi
        $temporaryPresensi->update([
            'jam_pulang' => $jamPulang,
            'durasi' => $durasi,
        ]);

        // PSW = Pulang Sebelum Waktunya: tambah " & PSW" jika pulang sebelum jam_pulang jadwal
        $status = $this->appendPswIfPulangSebelumWaktu($temporaryPresensi->status, $jamPulang, $jamPulangJadwal);

        // Copy ke rekap_presensi (untuk riwayat & laporan)
        RekapPresensi::create([
            'id' => $pegawai->id,
            'shift' => $temporaryPresensi->shift,
            'jam_datang' => $temporaryPresensi->jam_datang,
            'jam_pulang' => $jamPulang,
            'status' => $status,
            'keterlambatan' => $temporaryPresensi->keterlambatan,
            'durasi' => $durasi,
            'keterangan' => '',
            'photo' => $temporaryPresensi->photo,
        ]);

        // Hapus dari temporary (sudah pindah ke rekap)
        $temporaryPresensi->delete();

        Log::info('Presensi pulang via API', [
            'pegawai_id' => $pegawai->id,
            'jam_pulang' => $jamPulang->toDateTimeString(),
            'durasi' => $durasi,
            'status' => $status,
        ]);

        return [
            'success' => true,
            'message' => $isClosing
                ? 'Presensi pulang (closing) berhasil dicatat. Silakan lakukan presensi datang untuk hari ini.'
                : 'Presensi pulang berhasil dicatat.',
            'data' => [
                'tipe' => 'pulang',
                'is_closing' => $isClosing,
                'jam_datang' => $jamDatang->toIso8601String(),
                'jam_pulang' => $jamPulang->toIso8601String(),
                'durasi' => $durasi,
                'status' => $status,
            ],
        ];
    }

    private function handleImageUpload(Request $request, $pegawai)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                throw new \Exception('Format gambar tidak didukung. Gunakan JPEG atau PNG.');
            }
            if ($file->getSize() > 2 * 1024 * 1024) {
                throw new \Exception('Ukuran gambar maksimal 2MB.');
            }
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo === false) {
                throw new \Exception('File bukan gambar yang valid.');
            }
            $fileName = now()->format('YmdHis') . '_' . preg_replace('/[^A-Za-z0-9]/', '', $pegawai->id) . '.' . $file->getClientOriginalExtension();
            $filePath = public_path('presensi');
            if (!file_exists($filePath)) {
                mkdir($filePath, 0755, true);
            }
            $file->move($filePath, $fileName);
            return $fileName;
        }

        if ($request->image && strpos($request->image, 'data:image') === 0) {
            list($type, $imageData) = explode(';base64,', $request->image);
            if (!in_array($type, ['data:image/jpeg', 'data:image/jpg', 'data:image/png'])) {
                throw new \Exception('Format gambar tidak didukung.');
            }
            $imageData = base64_decode($imageData);
            if (strlen($imageData) > 2 * 1024 * 1024) {
                throw new \Exception('Ukuran gambar maksimal 2MB.');
            }
            $imageInfo = @getimagesizefromstring($imageData);
            if ($imageInfo === false) {
                throw new \Exception('Data bukan gambar yang valid.');
            }
            $fileName = now()->format('YmdHis') . '_' . preg_replace('/[^A-Za-z0-9]/', '', $pegawai->id) . '.jpeg';
            $fullPath = public_path('presensi/' . $fileName);
            if (!file_exists(public_path('presensi'))) {
                mkdir(public_path('presensi'), 0755, true);
            }
            file_put_contents($fullPath, $imageData);
            return $fileName;
        }

        throw new \Exception('Gambar presensi wajib diunggah.');
    }

    /**
     * PSW = Pulang Sebelum Waktunya: tambah " & PSW" ke status jika pulang sebelum jam_pulang jadwal.
     */
    private function appendPswIfPulangSebelumWaktu(string $status, Carbon $jamPulangAktual, Carbon $jamPulangJadwal): string
    {
        if ($jamPulangAktual->lessThan($jamPulangJadwal) && strpos($status, 'PSW') === false) {
            return $status . ' & PSW';
        }
        return $status;
    }

    private function calculateKeterlambatan($jamMasukShift, $now)
    {
        $setKeterlambatan = SetKeterlambatan::first();
        $toleransi = $setKeterlambatan ? (int) $setKeterlambatan->toleransi : 0;
        $terlambat1 = $setKeterlambatan ? (int) $setKeterlambatan->terlambat1 : 15;
        $terlambat2 = $setKeterlambatan ? (int) $setKeterlambatan->terlambat2 : 30;

        if ($now->lessThanOrEqualTo($jamMasukShift)) {
            return ['keterlambatan' => '00:00:00', 'status' => 'Tepat Waktu'];
        }

        $selisihMenit = $now->diffInMinutes($jamMasukShift);
        $keterlambatan = $now->diff($jamMasukShift)->format('%H:%I:%S');

        if ($selisihMenit <= $toleransi) {
            return ['keterlambatan' => '00:00:00', 'status' => 'Tepat Waktu'];
        }
        if ($selisihMenit <= $terlambat1) {
            return ['keterlambatan' => $keterlambatan, 'status' => 'Terlambat Toleransi'];
        }
        if ($selisihMenit <= $terlambat2) {
            return ['keterlambatan' => $keterlambatan, 'status' => 'Terlambat I'];
        }
        return ['keterlambatan' => $keterlambatan, 'status' => 'Terlambat II'];
    }
}
