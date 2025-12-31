<?php

namespace App\Observers;

use App\Models\DisposisiSurat;
use App\Models\TelegramUser;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

class DisposisiObserver
{
    public function created(DisposisiSurat $disposisi)
    {
        Log::info("Observer Disposisi jalan, penerima={$disposisi->nik_penerima}");

        $penerimaNik = $disposisi->nik_penerima;

        $telegramUser = TelegramUser::where('nik', $penerimaNik)->first();
        if ($telegramUser) {
            $surat = $disposisi->surat; // pastikan ada relasi disposisi -> surat()
            if (!$surat) {
                Log::warning("Disposisi {$disposisi->id_disposisi} tidak punya surat.");
                return;
            }

            // amanin biar gak error kalau null
            $namaPengirim  = optional($disposisi->pegawai)->nama ?: '-';
            $namaPenerima  = optional($disposisi->pegawai2)->nama ?: '-';
            $perihal       = $surat->perihal ?: '-';
            $catatan       = $disposisi->catatan_disposisi ?: '-';

            // generate URL detail surat (pakai route biar sama kayak view)
            $url = route('surat_masuk.detail', ['encryptedKodeSurat' => encrypt($surat->kode_surat)]);

            $message = "📌 <b>Disposisi Baru</b>\n" .
                       "Dari: {$namaPengirim}\n" .
                       "Untuk: {$namaPenerima}\n" .
                       "Perihal: {$perihal}\n\n" .
                       "Catatan: {$catatan}\n\n" .
                       "👉 <a href=\"{$url}\">Lihat Surat</a>";

            Log::info("Notif disposisi dikirim ke NIK {$penerimaNik}, chat_id={$telegramUser->chat_id}, url={$url}");

            (new TelegramService())->sendMessage($telegramUser->chat_id, $message);
        } else {
            Log::warning("Tidak ditemukan TelegramUser untuk NIK {$penerimaNik}");
        }
    }
}