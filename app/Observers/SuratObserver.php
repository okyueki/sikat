<?php

namespace App\Observers;

use App\Models\Surat;
use App\Models\TelegramUser;
use App\Jobs\SendTelegramNotification;

class SuratObserver
{
    /**
     * Handle the Surat "created" event.
     */
    public function created(Surat $surat): void
    {
        \Log::info("Observer Surat jalan, id_surat={$surat->id_surat}");
    
        $verifikasi = $surat->verifikasi;
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
    
            $message = "📩 Surat baru dari "
                . ($surat->pegawai->nama ?? $surat->pengirim_external)
                . "\nPerihal: {$surat->perihal}";
    
            SendTelegramNotification::dispatch($telegramUser->chat_id, $message);
            \Log::info("Job notifikasi dikirim ke queue untuk chat_id {$telegramUser->chat_id}");
        }
    }

}
