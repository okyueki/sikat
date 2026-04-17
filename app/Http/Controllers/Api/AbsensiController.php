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
use App\Models\JamMasuk;
use App\Models\JadwalPegawai;
use App\Models\JadwalTambahan;
use App\Models\SetKeterlambatan;
use App\Models\AuditTrail;
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
            $jamMasukModel = $this->getJamMasukFromCache($shift);
            if ($jamMasukModel) {
                $jamMasuk = $jamMasukModel->jam_masuk ?? null;
                $jamPulang = $jamMasukModel->jam_pulang ?? null;
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

        // Record tertinggal: ada presensi datang di hari sebelumnya tanpa pulang (user wajib tau)
        $recordTertinggal = null;
        $tertinggal = TemporaryPresensi::where('id', $pegawai->id)
            ->whereNull('jam_pulang')
            ->where('jam_datang', '<', $today->copy()->startOfDay())
            ->orderBy('jam_datang', 'desc')
            ->first();
        if ($tertinggal) {
            $recordTertinggal = [
                'ada' => true,
                'jam_datang' => $tertinggal->jam_datang ? Carbon::parse($tertinggal->jam_datang)->toIso8601String() : null,
                'tanggal' => $tertinggal->jam_datang ? Carbon::parse($tertinggal->jam_datang)->format('Y-m-d') : null,
                'shift' => $tertinggal->shift ?? null,
            ];
        }

        // Presensi belum pulang: satu baris di temporary_presensi (jam_pulang null), tanpa filter tanggal.
        // Front end pakai ini untuk tampilkan "Jam datang: 06:58:55" — kalau ada di temporary, tampilkan.
        $presensiBelumPulang = null;
        $openTemporary = TemporaryPresensi::where('id', $pegawai->id)
            ->whereNull('jam_pulang')
            ->orderBy('jam_datang', 'desc')
            ->first();
        if ($openTemporary && $openTemporary->jam_datang) {
            $dt = Carbon::parse($openTemporary->jam_datang, 'Asia/Jakarta');
            $presensiBelumPulang = [
                'jam_datang' => $dt->toIso8601String(),
                'jam_datang_time' => $dt->format('H:i:s'),
                'tanggal' => $dt->format('Y-m-d'),
                'shift' => $openTemporary->shift ?? null,
                'status' => $openTemporary->status ?? null,
                'keterlambatan' => $openTemporary->keterlambatan ?? null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $status,
                'jam_datang' => $jam_datang,
                'jam_pulang' => $jam_pulang,
                'record_tertinggal' => $recordTertinggal,
                'presensi_belum_pulang' => $presensiBelumPulang,
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
                // Presensi pulang (datang hari ini): pakai shift dari record, bukan jadwal hari ini
                // (hari libur di jadwal tidak menghalangi pulang)
                $jamJagaPulang = $this->getJamJagaForShift(trim((string) ($temporaryPresensiHariIni->shift ?? '')));
                if (!$jamJagaPulang) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Data jam shift tidak ditemukan untuk shift: ' . ($temporaryPresensiHariIni->shift ?? '-') . '.',
                    ], 400);
                }
                $result = $this->doPresensiPulang($request, $pegawai, $temporaryPresensiHariIni, $jamJagaPulang, false);
            } else {
                // Tidak ada record hari ini → cek dulu apakah ada record tertinggal (datang tanpa pulang)
                $recordTertinggal = TemporaryPresensi::where('id', $pegawai->id)
                    ->whereNull('jam_pulang')
                    ->where('jam_datang', '<', $today->copy()->startOfDay())
                    ->lockForUpdate()
                    ->orderBy('jam_datang', 'asc')
                    ->first();

                if ($recordTertinggal) {
                    // Closing pulang: pakai shift dari record tertinggal (hari ini boleh libur / tanpa jadwal)
                    Log::info('Record presensi tertinggal ditemukan, closing (pulang)', [
                        'pegawai_id' => $pegawai->id,
                        'jam_datang_tertinggal' => $recordTertinggal->jam_datang?->toDateTimeString(),
                    ]);
                    $jamJagaTertinggal = $this->getJamJagaForShift(trim((string) ($recordTertinggal->shift ?? '')));
                    if (!$jamJagaTertinggal) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Data jam shift tidak ditemukan untuk shift: ' . ($recordTertinggal->shift ?? '-') . '.',
                        ], 400);
                    }
                    $result = $this->doPresensiPulang($request, $pegawai, $recordTertinggal, $jamJagaTertinggal, true);
                } else {
                    // Presensi datang baru: wajib ada jadwal shift hari ini (atau jadwal tambahan jika sudah lengkap di rekap)
                    $shiftHariIni = $this->getShiftHariIni($pegawai->id);
                    if (!$shiftHariIni) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Tidak ada jadwal shift hari ini.',
                        ], 400);
                    }

                    $jamMasukModel = $this->getJamMasukFromCache(trim($shiftHariIni['shift']));
                    if (!$jamMasukModel) {
                        DB::rollBack();
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

    /** Daftar JamMasuk di-cache 1 jam untuk kurangi query. */
    private function getJamMasukFromCache(string $shift)
    {
        $list = Cache::remember('api.jam_masuk_list', 3600, fn () => JamMasuk::all());
        return $list->firstWhere('shift', $shift);
    }

    /** Ambil jam masuk/pulang untuk shift tertentu (dari tabel jam_masuk). */
    private function getJamJagaForShift(string $shift)
    {
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
        $currentMonthPadded = $today->format('m');
        $currentMonthInt = (int) $today->format('n');
        $currentYear = (int) $today->format('Y');
        $currentDay = (int) $today->format('j');
        $dayColumn = 'h' . $currentDay;

        // Bulan di DB bisa "03", "3", atau integer 3 — pakai whereIn agar selalu ketemu
        $jadwalPegawai = JadwalPegawai::where('id', $pegawaiId)
            ->whereIn('bulan', array_unique([$currentMonthPadded, (string) $currentMonthInt, $currentMonthInt]))
            ->where(function ($q) use ($currentYear) {
                $q->where('tahun', $currentYear)->orWhere('tahun', (string) $currentYear);
            })
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
        $tomorrowMonthPadded = $tomorrow->format('m');
        $tomorrowMonthInt = (int) $tomorrow->format('n');
        $tomorrowYear = (int) $tomorrow->format('Y');
        $tomorrowDay = (int) $tomorrow->format('j');
        $tomorrowColumn = 'h' . $tomorrowDay;

        $jadwalBesok = JadwalPegawai::where('id', $pegawaiId)
            ->whereIn('bulan', array_unique([$tomorrowMonthPadded, (string) $tomorrowMonthInt, $tomorrowMonthInt]))
            ->where(function ($q) use ($tomorrowYear) {
                $q->where('tahun', $tomorrowYear)->orWhere('tahun', (string) $tomorrowYear);
            })
            ->first();

        if (!$jadwalBesok) {
            return null;
        }

        $shiftBesok = $jadwalBesok->$tomorrowColumn ?? null;
        if (empty($shiftBesok) || trim($shiftBesok) === '') {
            return null;
        }

        $jamJagaBesok = $this->getJamMasukFromCache(trim($shiftBesok));
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
        $currentMonthPadded = $today->format('m');
        $currentMonthInt = (int) $today->format('n');
        $currentYear = (int) $today->format('Y');
        $currentDay = (int) $today->format('j');
        $dayColumn = 'h' . $currentDay;

        $jadwalTambahan = JadwalTambahan::where('id', $pegawaiId)
            ->whereIn('bulan', array_unique([$currentMonthPadded, (string) $currentMonthInt, $currentMonthInt]))
            ->where(function ($q) use ($currentYear) {
                $q->where('tahun', $currentYear)->orWhere('tahun', (string) $currentYear);
            })
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
        // Pastikan jam_masuk dibandingkan dengan HARI INI di Jakarta (hindari salah tanggal/timezone)
        $jamMasukShift = $this->parseJamMasukHariIni($jamJaga->jam_masuk);
        $keterlambatanData = $this->calculateKeterlambatan($jamMasukShift, $now);

        // Debug: pastikan jam_masuk di DB sesuai ekspektasi (bila status aneh, cek nilai ini)
        Log::info('Cek jam masuk shift untuk presensi', [
            'pegawai_id' => $pegawai->id,
            'shift' => $jamJaga->shift,
            'jam_masuk_db' => $jamJaga->jam_masuk,
            'jam_masuk_parsed' => $jamMasukShift->toDateTimeString(),
            'jam_datang' => $now->toDateTimeString(),
            'status' => $keterlambatanData['status'],
        ]);

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

        // PSW = Pulang Sebelum Waktunya: tambah " & PSW" ke status jika pulang sebelum jam_pulang jadwal.
        $status = $this->appendPswIfPulangSebelumWaktu($temporaryPresensi->status, $jamPulang, $jamPulangJadwal);

        // Audit trail untuk presensi pulang
        AuditTrail::logCreate('absensi', 'temporary_presensi', $pegawai->id, [
            'pegawai_id' => $pegawai->id,
            'shift' => $temporaryPresensi->shift,
            'jam_datang' => $temporaryPresensi->jam_datang->toDateTimeString(),
            'jam_pulang' => $jamPulang->toDateTimeString(),
            'status' => $status,
        ], "Presensi pulang untuk pegawai {$pegawai->nama} (ID: {$pegawai->id})");

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

        // Audit trail untuk presensi pulang
        AuditTrail::logCreate('absensi', 'rekap_presensi', $pegawai->id, [
            'pegawai_id' => $pegawai->id,
            'shift' => $temporaryPresensi->shift,
            'jam_datang' => $jamDatang->toDateTimeString(),
            'jam_pulang' => $jamPulang->toDateTimeString(),
            'durasi' => $durasi,
            'status' => $status,
            'is_closing' => $isClosing,
        ], $isClosing
            ? "Presensi pulang (closing) untuk pegawai {$pegawai->nama} (ID: {$pegawai->id})"
            : "Presensi pulang untuk pegawai {$pegawai->nama} (ID: {$pegawai->id})"
        );

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

    /**
     * Parse jam_masuk (time atau datetime dari DB) sebagai hari ini 00:00 + time di Jakarta.
     * Menghindari bug timezone: kalau pakai Carbon::parse() saja, tanggal bisa salah.
     */
    private function parseJamMasukHariIni($jamMasuk): Carbon
    {
        $today = Carbon::today('Asia/Jakarta');
        if ($jamMasuk instanceof \DateTimeInterface) {
            $timeStr = Carbon::parse($jamMasuk)->format('H:i:s');
        } else {
            $timeStr = trim((string) $jamMasuk);
            if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?/', $timeStr, $m)) {
                $timeStr = strlen($m[0]) === 5 ? $m[0] . ':00' : $m[0];
            }
        }
        return $today->copy()->setTimeFromTimeString($timeStr);
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
