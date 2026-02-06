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