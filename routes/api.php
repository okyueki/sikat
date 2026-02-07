<?php

use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/pegawai/{nik}', function($nik) {
    // Cari pegawai berdasarkan NIK
    $pegawai = Pegawai::where('nik', $nik)->firstOrFail();
    
    // Kembalikan data pegawai sebagai JSON
    return response()->json([
        'nama' => $pegawai->nama,
        'departemen' => $pegawai->departemen,
        'jabatan' => $pegawai->jbtn
    ]);
});
Route::get('/agenda/{id}', function($id) {
    // Cari agenda berdasarkan ID
    $agenda = Agenda::findOrFail($id);
    
    // Kembalikan data tanggal mulai
    return response()->json([
        'mulai' => $agenda->mulai,
    ]);
});

Route::get('/pegawai/{nik}', function ($nik) {
    $pegawai = Pegawai::where('nik', $nik)->first();

    if ($pegawai) {
        return response()->json([
            'nama' => $pegawai->nama,
            'departemen' => $pegawai->departemen,
            'jabatan' => $pegawai->jbtn
        ]);
    }

    return response()->json(['message' => 'Pegawai tidak ditemukan!'], 404);
});

Route::get('/booking-operasi', [BookingOperasiController::class, 'index']);
Route::post('/telegram/webhook', [TelegramController::class, 'handleWebhook']);

// Login/Logout API (untuk mobile / token)
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

// GPS Validation API untuk Android WebView
Route::middleware(['auth:sanctum,web'])->group(function () {
    Route::post('/gps/validate', [App\Http\Controllers\Api\GpsValidationController::class, 'validateGps']);
});

// API Absensi (presensi pegawai) - butuh token Bearer
Route::middleware('auth:sanctum')->prefix('absensi')->group(function () {
    Route::get('/jadwal-hari-ini', [App\Http\Controllers\Api\AbsensiController::class, 'jadwalHariIni']);
    Route::get('/status-hari-ini', [App\Http\Controllers\Api\AbsensiController::class, 'statusHariIni']);
    Route::get('/config', [App\Http\Controllers\Api\AbsensiController::class, 'config']);
    Route::get('/riwayat', [App\Http\Controllers\Api\AbsensiController::class, 'riwayat']);
    Route::post('/submit', [App\Http\Controllers\Api\AbsensiController::class, 'submit']);
});

Route::middleware('auth:sanctum')->post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);

// API Cuti & Ijin (untuk app React) - butuh token Bearer
Route::middleware('auth:sanctum')->prefix('cuti')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\CutiController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\CutiController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\CutiController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\CutiController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\CutiController::class, 'destroy']);
});
Route::middleware('auth:sanctum')->prefix('ijin')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\IjinController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\IjinController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\IjinController::class, 'show']);
    Route::put('/{id}', [App\Http\Controllers\Api\IjinController::class, 'update']);
    Route::delete('/{id}', [App\Http\Controllers\Api\IjinController::class, 'destroy']);
});
// Daftar pegawai (untuk dropdown atasan di form cuti/ijin)
Route::middleware('auth:sanctum')->get('/pegawai-atasan', function () {
    $list = \App\Models\Pegawai::where('stts_aktif', 'AKTIF')
        ->orderBy('nama')
        ->get(['nik', 'nama']);
    return response()->json(['success' => true, 'data' => $list]);
});

// API Dashboard (undangan agenda + notifikasi) - butuh token Bearer
Route::middleware('auth:sanctum')->get('/dashboard', [App\Http\Controllers\Api\DashboardController::class, 'index']);

// API Jadwal Pegawai (CRUD jadwal presensi per bulan/tahun - nyambung dengan absensi)
Route::middleware('auth:sanctum')->prefix('jadwal-pegawai')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\JadwalPegawaiController::class, 'index']);
    Route::get('/data', [App\Http\Controllers\Api\JadwalPegawaiController::class, 'data']);
    Route::put('/', [App\Http\Controllers\Api\JadwalPegawaiController::class, 'update']);
});

// API Absensi Agenda (scan barcode/QR untuk kehadiran rapat) - butuh token Bearer
Route::middleware('auth:sanctum')->prefix('absensi-agenda')->group(function () {
    Route::get('/agenda', [App\Http\Controllers\Api\AbsensiAgendaController::class, 'agenda']);
    Route::post('/scan', [App\Http\Controllers\Api\AbsensiAgendaController::class, 'scan']);
});

// API Surat Masuk (read-only: list + detail + notifikasi) - butuh token Bearer
Route::middleware('auth:sanctum')->prefix('surat-masuk')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\SuratMasukController::class, 'index']);
    Route::get('/notifikasi', [App\Http\Controllers\Api\SuratMasukController::class, 'notifikasi']);
    Route::get('/kode/{kodeSurat}', [App\Http\Controllers\Api\SuratMasukController::class, 'showByKode']);
    Route::get('/{id}', [App\Http\Controllers\Api\SuratMasukController::class, 'show']);
});

// API Profil pegawai (untuk app React) - butuh token Bearer
Route::middleware('auth:sanctum')->prefix('profil')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\ProfilController::class, 'show']);
    Route::put('/', [App\Http\Controllers\Api\ProfilController::class, 'update']);
    Route::put('/photo', [App\Http\Controllers\Api\ProfilController::class, 'updatePhoto']);
    Route::put('/berkas/masa-berlaku', [App\Http\Controllers\Api\ProfilController::class, 'updateMasaBerlaku']);
    Route::put('/berkas', [App\Http\Controllers\Api\ProfilController::class, 'updateBerkas']);
});