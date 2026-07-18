<?php

namespace App\Observers;

use App\Models\Surat;
use App\Models\TelegramUser;
use App\Models\User;
use App\Jobs\SendTelegramNotification;
use App\Events\SuratMasukNotifikasi;
use Illuminate\Support\Facades\Cache;

class SuratObserver
{
    /**
     * Handle the Surat "created" event.
     */
    public function created(Surat $surat): void
    {
        \Log::info("Observer Surat jalan, id_surat={$surat->id_surat}");
    
        $verifikasi = $surat->verifikasi()->orderByDesc('id_verifikasi_surat')->first();
        if (!$verifikasi) {
            \Log::warning("Surat {$surat->id_surat} belum punya verifikasi.");
            return;
        }
    
        $penerimaNik = $verifikasi->nik_verifikator;
        \Log::info("Surat {$surat->id_surat} -> nik_verifikator={$penerimaNik}");
    
        if ($penerimaNik) {
            $telegramUser = TelegramUser::where('nik', $penerimaNik)->first();
    
            if (!$telegramUser) {
                \Log::warning("Tidak ada TelegramUser untuk nik {$penerimaNik}");
                return;
            }

            // Validasi chat_id
            if (!$telegramUser->chat_id) {
                \Log::warning("TelegramUser untuk NIK {$penerimaNik} tidak punya chat_id.");
                return;
            }
    
            \Log::info("Chat ID ditemukan untuk nik {$penerimaNik}: {$telegramUser->chat_id}");
    
            $pengirimNama = $surat->pegawai ? $surat->pegawai->nama : ($surat->pengirim_external ?? '-');
            $message = "📩 Surat baru dari "
                . $pengirimNama
                . "\nPerihal: " . ($surat->perihal ?? '-');
    
            SendTelegramNotification::dispatch($telegramUser->chat_id, $message);
            \Log::info("Job notifikasi dikirim ke queue untuk chat_id {$telegramUser->chat_id}");

            Cache::forget('surat_notif_' . $penerimaNik);
            $user = User::where('username', $penerimaNik)->first();
            if ($user) {
                event(new SuratMasukNotifikasi(
                    $user->id,
                    'verifikasi',
                    'Surat baru: ' . $surat->perihal,
                    $surat->id_surat,
                    $surat->kode_surat
                ));
            }
        }
    }

}
