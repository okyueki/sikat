<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Pegawai;
use App\Models\RekapPresensi;
use App\Models\JamJaga;
use App\Models\JadwalPegawai;
use App\Models\SetKeterlambatan;
use Carbon\Carbon;

class AbsensiController extends Controller
{
    /**
     * Menampilkan formulir presensi (sederhana, hanya foto)
     */
    public function showPresensiForm()
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Ambil data pegawai untuk ditampilkan
        $nik = Auth::user()->username;
        $pegawai = Pegawai::where('nik', $nik)->first();

        if (!$pegawai) {
            return redirect()->route('dashboard')
                ->with('error', 'Data pegawai tidak ditemukan. Silakan hubungi administrator.');
        }

        // Cek jadwal shift hari ini
        $shiftHariIni = $this->getShiftHariIni($pegawai->id);
        $jamJaga = null;
        
        // Jadwal dianggap ada jika shift ditemukan di jadwal_pegawai
        $jadwalAda = ($shiftHariIni && !empty($shiftHariIni['shift']));
        
        if ($jadwalAda) {
            $shiftName = trim($shiftHariIni['shift']);
            
            // Cek di JamJaga dulu
            $jamJaga = JamJaga::where('shift', $shiftName)->first();
            
            // Jika tidak ditemukan di JamJaga, coba cek di JamMasuk
            if (!$jamJaga) {
                $jamMasuk = \App\Models\JamMasuk::where('shift', $shiftName)->first();
                if ($jamMasuk) {
                    // Convert JamMasuk ke format yang sama dengan JamJaga (gunakan stdClass)
                    $jamJaga = new \stdClass();
                    $jamJaga->shift = $jamMasuk->shift;
                    $jamJaga->jam_masuk = $jamMasuk->jam_masuk;
                    $jamJaga->jam_pulang = $jamMasuk->jam_pulang;
                }
            }
            
            // Log untuk debugging
            Log::info('Cek JamJaga setelah getShiftHariIni', [
                'shift' => $shiftName,
                'jamJaga_found' => $jamJaga ? 'yes' : 'no',
                'jadwalAda' => $jadwalAda,
                'jam_masuk' => $jamJaga ? $jamJaga->jam_masuk : 'not_found',
                'jam_pulang' => $jamJaga ? $jamJaga->jam_pulang : 'not_found',
            ]);
        }

        // Cek status presensi hari ini
        $presensiHariIni = RekapPresensi::where('id', $pegawai->id)
            ->whereDate('jam_datang', Carbon::today())
            ->first();

        $statusPresensi = null;
        if ($presensiHariIni) {
            if ($presensiHariIni->jam_pulang) {
                $statusPresensi = 'selesai'; // Sudah datang dan pulang
            } else {
                $statusPresensi = 'datang'; // Sudah datang, belum pulang
            }
        } else {
            $statusPresensi = 'belum'; // Belum presensi
        }

        // Data tambahan untuk ditampilkan
        $now = Carbon::now('Asia/Jakarta');
        $tanggalHariIni = $now->format('d F Y');
        $jamSaatIni = $now->format('H:i:s');
        $hariNama = $now->locale('id')->dayName;

        $presensiConfig = [
            'target_lat' => config('presensi.target_latitude'),
            'target_lng' => config('presensi.target_longitude'),
            'allowed_radius' => config('presensi.allowed_radius_meter'),
        ];
        return view('presensi.form', compact(
            'pegawai', 
            'statusPresensi', 
            'jadwalAda', 
            'shiftHariIni', 
            'jamJaga',
            'tanggalHariIni',
            'jamSaatIni',
            'hariNama',
            'presensiConfig'
        ));
    }

    /**
     * Menampilkan formulir presensi versi Mobile UI (Mobilekit).
     * Data & logic sama dengan showPresensiForm(), hanya view yang berbeda.
     */
    public function showPresensiFormMobile()
    {
        // Pastikan user sudah login
        if (!Auth::check()) {
            return redirect()->route('mobile.login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $nik = Auth::user()->username;
        $pegawai = Pegawai::where('nik', $nik)->first();

        if (!$pegawai) {
            return redirect()->route('mobile.home')
                ->with('error', 'Data pegawai tidak ditemukan. Silakan hubungi administrator.');
        }

        // Cek jadwal shift hari ini
        $shiftHariIni = $this->getShiftHariIni($pegawai->id);
        $jamJaga = null;
        $jadwalAda = ($shiftHariIni && !empty($shiftHariIni['shift']));

        if ($jadwalAda) {
            $shiftName = trim($shiftHariIni['shift']);

            $jamJaga = JamJaga::where('shift', $shiftName)->first();

            // Fallback: jika tidak ditemukan di JamJaga, coba JamMasuk (agar UI tetap informatif)
            if (!$jamJaga) {
                $jamMasuk = \App\Models\JamMasuk::where('shift', $shiftName)->first();
                if ($jamMasuk) {
                    $jamJaga = new \stdClass();
                    $jamJaga->shift = $jamMasuk->shift;
                    $jamJaga->jam_masuk = $jamMasuk->jam_masuk;
                    $jamJaga->jam_pulang = $jamMasuk->jam_pulang;
                }
            }
        }

        // Cek status presensi hari ini
        $presensiHariIni = RekapPresensi::where('id', $pegawai->id)
            ->whereDate('jam_datang', Carbon::today())
            ->first();

        if ($presensiHariIni) {
            $statusPresensi = $presensiHariIni->jam_pulang ? 'selesai' : 'datang';
        } else {
            $statusPresensi = 'belum';
        }

        $now = Carbon::now('Asia/Jakarta');
        $tanggalHariIni = $now->format('d F Y');
        $jamSaatIni = $now->format('H:i:s');
        $hariNama = $now->locale('id')->dayName;

        $presensiConfig = [
            'target_lat' => config('presensi.target_latitude'),
            'target_lng' => config('presensi.target_longitude'),
            'allowed_radius' => config('presensi.allowed_radius_meter'),
        ];
        return view('mobileui.presence', compact(
            'pegawai',
            'statusPresensi',
            'jadwalAda',
            'shiftHariIni',
            'jamJaga',
            'tanggalHariIni',
            'jamSaatIni',
            'hariNama',
            'presensiConfig'
        ));
    }

    /**
     * Handle presensi otomatis (datang atau pulang)
     */
    public function handlePresensi(Request $request)
    {
        // Rate limiting untuk mencegah spam
        $key = 'presensi:' . $request->ip() . ':' . (Auth::id() ?? 'guest');
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['error' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."]);
        }
        RateLimiter::hit($key, 60); // 60 detik

        // Pastikan user sudah login
        if (!Auth::check()) {
            return back()->withErrors(['error' => 'Silakan login terlebih dahulu.']);
        }

        DB::beginTransaction();
        try {
            // Validasi input
            $request->validate([
                'image' => 'required',
            ], [
                'image.required' => 'Foto presensi wajib diambil.',
            ]);

            // STEP 1: Auto-detect user dari login
            $nik = Auth::user()->username;
            $pegawai = Pegawai::where('nik', $nik)->first();

            if (!$pegawai) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Data pegawai tidak ditemukan. Silakan hubungi administrator.']);
            }

            // STEP 2: Cek jadwal shift hari ini
            $shiftHariIni = $this->getShiftHariIni($pegawai->id);
            
            if (!$shiftHariIni) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Tidak ada jadwal shift hari ini. Silakan hubungi administrator.']);
            }

            $jamJaga = JamJaga::where('shift', $shiftHariIni['shift'])->first();
            
            if (!$jamJaga) {
                DB::rollBack();
                return back()->withErrors(['error' => 'Data shift tidak ditemukan. Silakan hubungi administrator.']);
            }

            // STEP 3: Cek duplikasi presensi (dengan lock untuk mencegah race condition)
            $rekapPresensi = RekapPresensi::where('id', $pegawai->id)
                ->whereDate('jam_datang', Carbon::today())
                ->lockForUpdate()
                ->first();

            // STEP 4: Auto-detect datang atau pulang
            if ($rekapPresensi && $rekapPresensi->jam_pulang) {
                // Sudah datang dan pulang hari ini
                DB::rollBack();
                return back()->withErrors(['error' => 'Anda sudah melakukan presensi datang dan pulang hari ini.']);
            }

            if ($rekapPresensi && !$rekapPresensi->jam_pulang) {
                // Sudah datang, sekarang presensi PULANG
                return $this->handlePresensiPulang($request, $pegawai, $rekapPresensi, $jamJaga);
            } else {
                // Belum presensi, sekarang presensi DATANG
                return $this->handlePresensiDatang($request, $pegawai, $jamJaga);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::warning('Validation error dalam presensi', [
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
            ]);
            return back()->withErrors($e->validator)->withInput();
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Database error dalam presensi', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
                'pegawai_id' => $pegawai->id ?? null,
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan database. Silakan hubungi administrator.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Unexpected error dalam presensi', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'pegawai_id' => $pegawai->id ?? null,
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memproses presensi. Silakan coba lagi.']);
        }
    }

    /**
     * Handle presensi datang
     */
    private function handlePresensiDatang(Request $request, $pegawai, $jamJaga)
    {
        // Handle upload foto
        $photoPath = $this->handleImageUpload($request, $pegawai);
        if (!$photoPath) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan foto. Silakan coba lagi.']);
        }

        // Hitung status dan keterlambatan
        $now = Carbon::now('Asia/Jakarta');
        $jamMasukShift = Carbon::parse($jamJaga->jam_masuk, 'Asia/Jakarta');
        
        $keterlambatanData = $this->calculateKeterlambatan($jamMasukShift, $now);

        // Simpan langsung ke RekapPresensi (skip temporary)
        RekapPresensi::create([
            'id' => $pegawai->id,
            'shift' => $jamJaga->shift,
            'jam_datang' => $now,
            'status' => $keterlambatanData['status'],
            'keterlambatan' => $keterlambatanData['keterlambatan'],
            'photo' => $photoPath,
            'keterangan' => '',
        ]);

        DB::commit();

        // Log aktivitas
        Log::info('Presensi datang berhasil', [
            'pegawai_id' => $pegawai->id,
            'pegawai_nama' => $pegawai->nama,
            'shift' => $jamJaga->shift,
            'jam_datang' => $now->toDateTimeString(),
            'status' => $keterlambatanData['status'],
            'keterlambatan' => $keterlambatanData['keterlambatan'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $message = $keterlambatanData['status'] === 'Tepat Waktu' 
            ? "Selamat datang, {$pegawai->nama}! Presensi datang berhasil dicatat."
            : "Selamat datang, {$pegawai->nama}! Anda terlambat {$keterlambatanData['keterlambatan']}. Presensi datang berhasil dicatat.";

        return back()->with('success', $message);
    }

    /**
     * Handle presensi pulang
     */
    private function handlePresensiPulang(Request $request, $pegawai, $rekapPresensi, $jamJaga)
    {
        $jamDatang = Carbon::parse($rekapPresensi->jam_datang);
        $jamPulang = Carbon::now('Asia/Jakarta');

        // Validasi jam pulang harus setelah jam datang
        if ($jamPulang->lessThanOrEqualTo($jamDatang)) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Jam pulang tidak valid. Pastikan waktu pulang setelah waktu datang.']);
        }

        // Validasi durasi minimum - DISABLED untuk mode development
        // TODO: Aktifkan kembali saat production dengan konfigurasi yang sesuai
        // $minDuration = $jamDatang->copy()->addHour();
        // if ($jamPulang->lessThan($minDuration)) {
        //     DB::rollBack();
        //     return back()->withErrors(['error' => 'Durasi kerja terlalu singkat. Minimal 1 jam kerja.']);
        // }

        // Hitung durasi
        $durasi = $jamPulang->diff($jamDatang)->format('%H:%I:%S');

        // Update rekap presensi
        $rekapPresensi->update([
            'jam_pulang' => $jamPulang,
            'durasi' => $durasi,
        ]);

        DB::commit();

        // Log aktivitas
        Log::info('Presensi pulang berhasil', [
            'pegawai_id' => $pegawai->id,
            'pegawai_nama' => $pegawai->nama,
            'jam_datang' => $jamDatang->toDateTimeString(),
            'jam_pulang' => $jamPulang->toDateTimeString(),
            'durasi' => $durasi,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Presensi pulang berhasil dicatat. Durasi kerja: {$durasi}");
    }

    /**
     * Get shift hari ini dari JadwalPegawai
     */
    private function getShiftHariIni($pegawaiId)
    {
        $today = Carbon::today('Asia/Jakarta');
        $currentMonth = $today->format('m'); // Format: 01-12 (string)
        $currentYear = $today->format('Y'); // Format: 2026 (string)
        $currentDay = (int)$today->format('j'); // 1-31 tanpa leading zero
        $dayColumn = 'h' . $currentDay;

        // Pastikan tidak menggunakan cache, ambil data fresh dari database
        // Tahun di database adalah year(4), bisa string atau integer
        // Coba dengan format string dulu (seperti di JadwalController)
        $jadwalPegawai = JadwalPegawai::where('id', $pegawaiId)
            ->where('bulan', $currentMonth)
            ->where('tahun', $currentYear) // Coba sebagai string dulu
            ->first();
        
        // Jika tidak ditemukan, coba sebagai integer
        if (!$jadwalPegawai) {
            $jadwalPegawai = JadwalPegawai::where('id', $pegawaiId)
                ->where('bulan', $currentMonth)
                ->where('tahun', (int)$currentYear)
                ->first();
        }

        // Log untuk debugging
        Log::info('Cek jadwal hari ini', [
            'pegawai_id' => $pegawaiId,
            'bulan' => $currentMonth,
            'tahun' => $currentYear,
            'tahun_int' => (int)$currentYear,
            'hari' => $currentDay,
            'dayColumn' => $dayColumn,
            'jadwal_found' => $jadwalPegawai ? 'yes' : 'no',
            'shift_value' => $jadwalPegawai ? ($jadwalPegawai->$dayColumn ?? 'empty') : 'no_jadwal',
            'tahun_db' => $jadwalPegawai ? $jadwalPegawai->tahun : null,
        ]);

        if (!$jadwalPegawai) {
            return null;
        }

        // Cek apakah kolom hari ini memiliki nilai
        $shiftValue = $jadwalPegawai->$dayColumn;
        if (empty($shiftValue) || trim($shiftValue) === '') {
            return null;
        }

        return [
            'shift' => $shiftValue,
        ];
    }

    /**
     * Handle image upload dengan validasi keamanan
     */
    private function handleImageUpload(Request $request, $pegawai)
    {
        if ($request->hasFile('image')) {
            // File upload biasa
            $file = $request->file('image');
            
            // Validasi tipe file
            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                throw new \Exception('Format gambar tidak didukung. Gunakan JPEG atau PNG.');
            }

            // Validasi ukuran (max 2MB)
            if ($file->getSize() > 2 * 1024 * 1024) {
                throw new \Exception('Ukuran gambar terlalu besar. Maksimal 2MB.');
            }

            // Validasi bahwa ini benar-benar gambar
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo === false) {
                throw new \Exception('File yang diunggah bukan gambar yang valid.');
            }

            $fileName = now()->format('YmdHis') . '_' . preg_replace('/[^A-Za-z0-9]/', '', $pegawai->id) . '.' . $file->getClientOriginalExtension();
            $filePath = public_path('presensi');
            
            if (!file_exists($filePath)) {
                mkdir($filePath, 0755, true);
            }
            
            $file->move($filePath, $fileName);
            return $fileName;

        } elseif ($request->image && strpos($request->image, 'data:image') === 0) {
            // Base64 image dari webcam
            $imageData = $request->image;
            
            // Extract dan validasi base64
            list($type, $imageData) = explode(';base64,', $imageData);
            
            // Validasi tipe MIME
            if (!in_array($type, ['data:image/jpeg', 'data:image/jpg', 'data:image/png'])) {
                throw new \Exception('Format gambar tidak didukung. Gunakan JPEG atau PNG.');
            }
            
            $imageData = base64_decode($imageData);
            
            // Validasi ukuran (max 2MB)
            if (strlen($imageData) > 2 * 1024 * 1024) {
                throw new \Exception('Ukuran gambar terlalu besar. Maksimal 2MB.');
            }
            
            // Validasi bahwa ini benar-benar gambar
            $imageInfo = @getimagesizefromstring($imageData);
            if ($imageInfo === false) {
                throw new \Exception('File yang diunggah bukan gambar yang valid.');
            }
            
            // Sanitasi nama file
            $fileName = now()->format('YmdHis') . '_' . preg_replace('/[^A-Za-z0-9]/', '', $pegawai->id) . '.jpeg';
            $filePath = public_path('presensi/' . $fileName);
            
            if (!file_exists(public_path('presensi'))) {
                mkdir(public_path('presensi'), 0755, true);
            }
            
            file_put_contents($filePath, $imageData);
            
            // Validasi file berhasil disimpan
            if (!file_exists($filePath)) {
                throw new \Exception('Gagal menyimpan foto.');
            }
            
            return $fileName;
        } else {
            throw new \Exception('Gambar presensi harus diunggah!');
        }
    }

    /**
     * Hitung keterlambatan dengan menggunakan SetKeterlambatan
     */
    private function calculateKeterlambatan($jamMasukShift, $now)
    {
        // Ambil konfigurasi toleransi dari SetKeterlambatan
        $setKeterlambatan = SetKeterlambatan::first();
        $toleransi = $setKeterlambatan ? (int)$setKeterlambatan->toleransi : 0; // dalam menit
        $terlambat1 = $setKeterlambatan ? (int)$setKeterlambatan->terlambat1 : 15; // dalam menit
        $terlambat2 = $setKeterlambatan ? (int)$setKeterlambatan->terlambat2 : 30; // dalam menit

        // Jika datang sebelum atau sama dengan jam masuk shift
        if ($now->lessThanOrEqualTo($jamMasukShift)) {
            return [
                'keterlambatan' => '00:00:00',
                'status' => 'Tepat Waktu'
            ];
        }

        // Hitung selisih dalam menit
        $selisihMenit = $now->diffInMinutes($jamMasukShift);
        
        // Format keterlambatan dalam HH:MM:SS
        $keterlambatan = $now->diff($jamMasukShift)->format('%H:%I:%S');

        // Tentukan status berdasarkan toleransi
        if ($selisihMenit <= $toleransi) {
            return [
                'keterlambatan' => '00:00:00',
                'status' => 'Tepat Waktu'
            ];
        } elseif ($selisihMenit <= $terlambat1) {
            return [
                'keterlambatan' => $keterlambatan,
                'status' => 'Terlambat Toleransi'
            ];
        } elseif ($selisihMenit <= $terlambat2) {
            return [
                'keterlambatan' => $keterlambatan,
                'status' => 'Terlambat I'
            ];
        } else {
            return [
                'keterlambatan' => $keterlambatan,
                'status' => 'Terlambat II'
            ];
        }
    }
}
