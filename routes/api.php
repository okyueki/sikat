<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use App\Models\Pegawai;
use App\Models\Agenda;
use App\Http\Controllers\Api\BookingOperasiController;
use App\Http\Controllers\Api\TelegramController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum', 'throttle:api-auth'])->get('/user', function (Request $request) {
    return $request->user();
});

// Endpoint sensitif: pegawai & agenda — wajib auth Bearer token
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->group(function () {
    Route::get('/pegawai/{nik}', function ($nik) {
        $pegawai = Pegawai::where('nik', $nik)->first();
        if ($pegawai) {
            return response()->json([
                'nama' => $pegawai->nama,
                'departemen' => $pegawai->departemen,
                'jabatan' => $pegawai->jbtn,
            ]);
        }
        return response()->json(['message' => 'Pegawai tidak ditemukan!'], 404);
    });
    Route::get('/agenda/{id}', function ($id) {
        $agenda = Agenda::findOrFail($id);
        return response()->json([
            'mulai' => $agenda->mulai,
        ]);
    });
});

Route::get('/booking-operasi', [BookingOperasiController::class, 'index']);
Route::post('/telegram/webhook', [TelegramController::class, 'handleWebhook']);

// API App Version (tanpa auth - untuk force update check)
Route::get('/app/version', [App\Http\Controllers\Api\AppController::class, 'version']);

// Login/Logout API (untuk mobile / token)
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

// GPS Validation API untuk Android WebView
Route::middleware(['auth:sanctum,web', 'throttle:api-auth'])->group(function () {
    Route::post('/gps/validate', [App\Http\Controllers\Api\GpsValidationController::class, 'validateGps']);
});

// API Absensi (presensi pegawai) - butuh token Bearer
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->prefix('absensi')->group(function () {
    Route::get('/jadwal-hari-ini', [App\Http\Controllers\Api\AbsensiController::class, 'jadwalHariIni']);
    Route::get('/status-hari-ini', [App\Http\Controllers\Api\AbsensiController::class, 'statusHariIni']);
    Route::get('/config', [App\Http\Controllers\Api\AbsensiController::class, 'config']);
    Route::get('/riwayat', [App\Http\Controllers\Api\AbsensiController::class, 'riwayat']);
    Route::post('/submit', [App\Http\Controllers\Api\AbsensiController::class, 'submit']);
});

// API Absensi Sholat (QR + geolocation masjid) - config dari config/masjid.php
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->prefix('absensi-sholat')->group(function () {
    Route::get('/config', [App\Http\Controllers\Api\AbsensiSholatController::class, 'config']);
    Route::get('/riwayat', [App\Http\Controllers\Api\AbsensiSholatController::class, 'riwayat']);
    Route::get('/rekap-bulanan', [App\Http\Controllers\Api\AbsensiSholatController::class, 'rekapBulanan']);
    Route::post('/scan', [App\Http\Controllers\Api\AbsensiSholatController::class, 'scan']);
});

Route::middleware(['auth:sanctum', 'throttle:api-auth'])->post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);

// API Cuti & Ijin (untuk app React) - butuh token Bearer
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->prefix('cuti')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\CutiController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\CutiController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\CutiController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\CutiController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\CutiController::class, 'destroy']);
});
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->prefix('ijin')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\IjinController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\IjinController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\IjinController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\IjinController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\IjinController::class, 'destroy']);
});
// Daftar pegawai (untuk dropdown atasan di form cuti/ijin). Cache 10 menit.
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->get('/pegawai-atasan', function () {
    $list = Cache::remember('api.pegawai_atasan', 600, function () {
        return Pegawai::where('stts_aktif', 'AKTIF')
            ->orderBy('nama')
            ->get(['nik', 'nama']);
    });
    return response()->json(['success' => true, 'data' => $list]);
});

// API Dashboard (undangan agenda + notifikasi, ulang tahun) - butuh token Bearer
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->prefix('dashboard')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\DashboardController::class, 'index']);
    Route::get('/ulang-tahun', [App\Http\Controllers\Api\DashboardController::class, 'ulangTahunHariIni']);
});

// API Jadwal Pegawai (CRUD jadwal presensi per bulan/tahun - nyambung dengan absensi)
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->prefix('jadwal-pegawai')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\JadwalPegawaiController::class, 'index']);
    Route::get('/data', [App\Http\Controllers\Api\JadwalPegawaiController::class, 'data']);
    Route::put('/', [App\Http\Controllers\Api\JadwalPegawaiController::class, 'update']);
});

// API Jadwal Tambahan (CRUD jadwal tambahan - untuk presensi kedua jika sudah lengkap di rekap)
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->prefix('jadwal-tambahan')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\JadwalTambahanController::class, 'index']);
    Route::get('/data', [App\Http\Controllers\Api\JadwalTambahanController::class, 'data']);
    Route::put('/', [App\Http\Controllers\Api\JadwalTambahanController::class, 'update']);
});

// API Absensi Agenda (scan barcode/QR untuk kehadiran rapat) - butuh token Bearer
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->prefix('absensi-agenda')->group(function () {
    Route::get('/agenda/{id}/rekap', [App\Http\Controllers\Api\AbsensiAgendaController::class, 'rekap']);
    Route::get('/agenda/{id}', [App\Http\Controllers\Api\AbsensiAgendaController::class, 'agendaDetail']);
    Route::get('/agenda', [App\Http\Controllers\Api\AbsensiAgendaController::class, 'agenda']);
    Route::get('/riwayat', [App\Http\Controllers\Api\AbsensiAgendaController::class, 'riwayat']);
    Route::post('/scan', [App\Http\Controllers\Api\AbsensiAgendaController::class, 'scan']);
});

// API Budaya Kerja (penilaian budaya kerja pegawai) - butuh token Bearer
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->prefix('budayakerja')->group(function () {
    Route::get('/pegawai', [App\Http\Controllers\Api\BudayaKerjaController::class, 'pegawaiBelumMengisi']);
    Route::get('/shift', [App\Http\Controllers\Api\BudayaKerjaController::class, 'shiftOptions']);
    Route::get('/{id}', [App\Http\Controllers\Api\BudayaKerjaController::class, 'show']);
    Route::post('/', [App\Http\Controllers\Api\BudayaKerjaController::class, 'store']);
});

// API Penilaian Harian (items_penilaian + penilaian_harian) - butuh token Bearer
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->prefix('penilaian-harian')->group(function () {
    Route::get('/items', [App\Http\Controllers\Api\PenilaianHarianController::class, 'items']);
    Route::get('/search-pegawai', [App\Http\Controllers\Api\PenilaianHarianController::class, 'searchPegawai']);
    Route::post('/', [App\Http\Controllers\Api\PenilaianHarianController::class, 'store']);
});

// API Surat Masuk (read-only: list + detail + notifikasi) - butuh token Bearer
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->prefix('surat-masuk')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\SuratMasukController::class, 'index']);
    Route::get('/notifikasi', [App\Http\Controllers\Api\SuratMasukController::class, 'notifikasi']);
    Route::get('/kode/{kodeSurat}', [App\Http\Controllers\Api\SuratMasukController::class, 'showByKode']);
    Route::post('/{id}/tandai-dibaca', [App\Http\Controllers\Api\SuratMasukController::class, 'tandaiDibaca']);
    Route::get('/{id}/file-pdf', [App\Http\Controllers\Api\SuratMasukController::class, 'filePdf'])->middleware('throttle:10,1');
    Route::get('/{id}/file-lampiran', [App\Http\Controllers\Api\SuratMasukController::class, 'fileLampiran']);
    Route::get('/{id}', [App\Http\Controllers\Api\SuratMasukController::class, 'show']);
});

// API Profil pegawai (untuk app React) - butuh token Bearer
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->prefix('profil')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\ProfilController::class, 'show']);
    Route::put('/', [App\Http\Controllers\Api\ProfilController::class, 'update']);
    Route::put('/photo', [App\Http\Controllers\Api\ProfilController::class, 'updatePhoto']);
    Route::put('/berkas/masa-berlaku', [App\Http\Controllers\Api\ProfilController::class, 'updateMasaBerlaku']);
    Route::put('/berkas', [App\Http\Controllers\Api\ProfilController::class, 'updateBerkas']);
});

// API FCM: daftar/hapus device token + test (untuk uji di dev)
Route::middleware(['auth:sanctum', 'throttle:api-auth'])->prefix('notifications')->group(function () {
    Route::post('/register-device', [App\Http\Controllers\Api\FcmDeviceController::class, 'registerDevice']);
    Route::delete('/register-device', [App\Http\Controllers\Api\FcmDeviceController::class, 'unregisterDevice']);
    Route::post('/test', [App\Http\Controllers\Api\FcmDeviceController::class, 'sendTest']);
});