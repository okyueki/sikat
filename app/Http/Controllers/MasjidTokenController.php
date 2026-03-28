<?php

namespace App\Http\Controllers;

use App\Models\MasjidToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Backend: kelola token QR masjid untuk absensi sholat.
 * Admin/takmir bisa generate token, set masa berlaku, dan tampilkan QR untuk di-print.
 */
class MasjidTokenController extends Controller
{
    /**
     * Halaman kelola token: tampil token aktif (jika ada), form generate, dan QR.
     */
    public function index(Request $request)
    {
        $tokenAktif = MasjidToken::where('valid_until', '>', now())
            ->orderBy('valid_until', 'desc')
            ->first();

        $qrCodeUrl = null;
        $tokenString = null;
        if ($tokenAktif) {
            $tokenString = $tokenAktif->token;
            $qrCodeImage = QrCode::format('png')->size(280)->margin(1)->generate($tokenString);
            $qrCodeUrl = 'data:image/png;base64,' . base64_encode($qrCodeImage);
        }

        return view('absensi_sholat.token_index', [
            'tokenAktif' => $tokenAktif,
            'qrCodeUrl' => $qrCodeUrl,
            'tokenString' => $tokenString,
        ]);
    }

    /**
     * Generate token baru; masa berlaku ditentukan admin. Token lama di-nonaktifkan.
     */
    public function store(Request $request)
    {
        $request->validate([
            'valid_until' => 'required|date|after:today',
        ], [
            'valid_until.required' => 'Masa berlaku wajib diisi.',
            'valid_until.after' => 'Masa berlaku harus setelah hari ini.',
        ]);

        $validUntil = Carbon::parse($request->valid_until)->endOfDay();

        // Nonaktifkan semua token lama (supaya hanya satu yang aktif)
        MasjidToken::query()->update(['valid_until' => now()]);

        $token = Str::random(48);
        MasjidToken::create([
            'token' => $token,
            'valid_until' => $validUntil,
        ]);

        return redirect()
            ->route('masjid_token.index')
            ->with('success', 'Token baru berhasil dibuat. Berlaku sampai ' . $validUntil->format('d M Y') . '. Silakan cetak QR code untuk dipasang di masjid.');
    }
}
