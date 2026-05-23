<?php

namespace App\Observers;

use App\Models\VerifikasiSurat;
use App\Models\TelegramUser;
use App\Models\User;
use App\Jobs\SendTelegramNotification;
use App\Events\SuratMasukNotifikasi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class VerifikasiSuratObserver
{
    /**
     * Handle event ketika verifikasi surat dibuat
     */
    public function created(VerifikasiSurat $verifikasi): void
    {
        Log::info("Observer Verifikasi jalan, id_verifikasi_surat=" . ($verifikasi->id_verifikasi_surat ?? $verifikasi->getKey()));

        $nik = $verifikasi->nik_verifikator;

        if (!$nik) {
            Log::warning("Verifikasi tidak punya nik_verifikator.");
            return;
        }

        $telegramUser = TelegramUser::where('nik', $nik)->first();

        if (!$telegramUser) {
            Log::warning("Tidak ditemukan TelegramUser untuk NIK {$nik}");
            return;
        }

        // Validasi chat_id
        if (!$telegramUser->chat_id) {
            Log::warning("TelegramUser untuk NIK {$nik} tidak punya chat_id.");
            return;
        }

        $surat = $verifikasi->surat; // pastikan ada relasi di model VerifikasiSurat -> surat()
        if (!$surat) {
            Log::warning("Verifikasi tidak punya surat.");
            return;
        }

        $encryptedId = Crypt::encrypt($surat->kode_surat);
        $url = route('surat_masuk.detail', ['encryptedKodeSurat' => $encryptedId]);

        $pengirimNama = $surat->pegawai ? $surat->pegawai->nama : ($surat->pengirim_external ?? '-');
        $tanggalStr = $surat->tanggal_surat
            ? \Carbon\Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y')
            : '-';

        // Pesan Telegram
        $message = "📩 <b>Surat Baru untuk Verifikasi</b>\n" .
           "Pengirim: {$pengirimNama}\n" .
           "Perihal: " . ($surat->perihal ?? '-') . "\n" .
           "Tanggal: {$tanggalStr}\n\n" .
           "<a href=\"{$url}\">👉 Lihat Detail</a>";

        Log::info("Notif dikirim ke NIK {$nik}, chat_id={$telegramUser->chat_id}, url={$url}");

        // Kirim ke job queue
        SendTelegramNotification::dispatch($telegramUser->chat_id, $message);

        // Invalidate cache notifikasi API agar polling dapat data terbaru
        Cache::forget('surat_notif_' . $nik);

        // Broadcast ke frontend (React/mobile) agar bisa tampil notifikasi real-time
        $user = User::where('username', $nik)->first();
        if ($user) {
            event(new SuratMasukNotifikasi(
                $user->id,
                'verifikasi',
                'Surat baru untuk verifikasi: ' . ($surat->perihal ?? 'Surat masuk'),
                $surat->id_surat,
                $surat->kode_surat
            ));
        }
    }
}
