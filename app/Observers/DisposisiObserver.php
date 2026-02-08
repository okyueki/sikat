<?php

namespace App\Observers;

use App\Models\DisposisiSurat;
use App\Models\TelegramUser;
use App\Models\User;
use App\Jobs\SendTelegramNotification;
use App\Events\SuratMasukNotifikasi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DisposisiObserver
{
    public function created(DisposisiSurat $disposisi)
    {
        Log::info("Observer Disposisi jalan, penerima={$disposisi->nik_penerima}");

        $penerimaNik = $disposisi->nik_penerima;

        if (!$penerimaNik) {
            Log::warning("Disposisi tidak punya nik_penerima.");
            return;
        }

        $telegramUser = TelegramUser::where('nik', $penerimaNik)->first();
        
        if (!$telegramUser) {
            Log::warning("Tidak ditemukan TelegramUser untuk NIK {$penerimaNik}");
            return;
        }

        // Validasi chat_id
        if (!$telegramUser->chat_id) {
            Log::warning("TelegramUser untuk NIK {$penerimaNik} tidak punya chat_id.");
            return;
        }

        $surat = $disposisi->surat; // pastikan ada relasi disposisi -> surat()
        if (!$surat) {
            Log::warning("Disposisi tidak punya surat.");
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

        // Gunakan Queue untuk async processing
        SendTelegramNotification::dispatch($telegramUser->chat_id, $message);

        // Invalidate cache notifikasi API agar polling dapat data terbaru
        Cache::forget('surat_notif_' . $penerimaNik);

        // Broadcast ke frontend (React/mobile) agar bisa tampil notifikasi real-time
        $user = User::where('username', $penerimaNik)->first();
        if ($user) {
            event(new SuratMasukNotifikasi(
                $user->id,
                'disposisi',
                'Disposisi baru: ' . $perihal,
                $surat->id_surat,
                $surat->kode_surat
            ));
        }
    }
}