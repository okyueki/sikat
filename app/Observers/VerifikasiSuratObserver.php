<?php

namespace App\Observers;

use App\Models\VerifikasiSurat;
use App\Models\TelegramUser;
use App\Jobs\SendTelegramNotification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Helpers\CryptoHelper;

class VerifikasiSuratObserver
{
    /**
     * Handle event ketika verifikasi surat dibuat
     */
    public function created(VerifikasiSurat $verifikasi): void
    {
        Log::info("Observer Verifikasi jalan, id_verifikasi={$verifikasi->id_verifikasi}");

        $nik = $verifikasi->nik_verifikator;

        if (!$nik) {
            Log::warning("Verifikasi {$verifikasi->id_verifikasi} tidak punya nik_verifikator.");
            return;
        }

        $telegramUser = TelegramUser::where('nik', $nik)->first();

        if (!$telegramUser) {
            Log::warning("Tidak ditemukan TelegramUser untuk NIK {$nik}");
            return;
        }

        $surat = $verifikasi->surat; // pastikan ada relasi di model VerifikasiSurat -> surat()
        if (!$surat) {
            Log::warning("Verifikasi {$verifikasi->id_verifikasi} tidak punya surat.");
            return;
        }

        $encryptedId = Crypt::encrypt($surat->kode_surat);
        $url = route('surat_masuk.detail', ['encryptedKodeSurat' => $encryptedId]);

        // Pesan Telegram
        $message = "📩 <b>Surat Baru untuk Verifikasi</b>\n" .
           "Pengirim: {$surat->pegawai->nama}\n" .
           "Perihal: {$surat->perihal}\n" .
           "Tanggal: " . \Carbon\Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y') . "\n\n" .
           "<a href=\"{$url}\">👉 Lihat Detail</a>";

        Log::info("Notif dikirim ke NIK {$nik}, chat_id={$telegramUser->chat_id}, url={$url}");

        // Kirim ke job queue
        SendTelegramNotification::dispatch($telegramUser->chat_id, $message);
    }
}
