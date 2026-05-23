<?php

namespace App\Observers;

use App\Models\Agenda;
use App\Models\TelegramUser;
use App\Jobs\SendTelegramNotification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AgendaObserver
{
    /**
     * Handle event ketika agenda dibuat
     */
    public function created(Agenda $agenda): void
    {
        Log::info("Observer Agenda jalan, id_agenda={$agenda->id}, judul={$agenda->judul}");

        // Ambil array NIK yang terundang
        $yangTerundang = $agenda->yang_terundang;

        if (!$yangTerundang || !is_array($yangTerundang) || empty($yangTerundang)) {
            Log::warning("Agenda {$agenda->id} tidak punya yang_terundang atau kosong.");
            return;
        }

        // Format tanggal untuk notifikasi
        $tanggalMulai = $agenda->mulai 
            ? Carbon::parse($agenda->mulai)->translatedFormat('d F Y, H:i') 
            : '-';
        
        $tanggalAkhir = $agenda->akhir 
            ? Carbon::parse($agenda->akhir)->translatedFormat('d F Y, H:i') 
            : null;

        $tempat = $agenda->tempat ?: '-';
        $pimpinan = $agenda->pimpinan ? $agenda->pimpinan->nama : '-';

        // Generate full URL detail agenda (dengan domain lengkap)
        $url = url(route('acara_show', $agenda->id, false));

        // Buat pesan notifikasi
        $message = "📅 <b>Undangan Agenda Baru</b>\n" .
                   "Judul: {$agenda->judul}\n" .
                   "Tanggal: {$tanggalMulai}" . 
                   ($tanggalAkhir ? " - {$tanggalAkhir}" : "") . "\n" .
                   "Tempat: {$tempat}\n" .
                   "Pimpinan: {$pimpinan}\n\n" .
                   "👉 <a href=\"{$url}\">Lihat Detail Agenda</a>";

        // Kirim notifikasi ke semua pegawai yang terundang
        $successCount = 0;
        $failCount = 0;

        foreach ($yangTerundang as $nik) {
            if (!$nik) {
                continue;
            }

            $telegramUser = TelegramUser::where('nik', $nik)->first();

            if (!$telegramUser) {
                Log::warning("Tidak ditemukan TelegramUser untuk NIK {$nik} (Agenda: {$agenda->id})");
                $failCount++;
                continue;
            }

            // Validasi chat_id
            if (!$telegramUser->chat_id) {
                Log::warning("TelegramUser untuk NIK {$nik} tidak punya chat_id (Agenda: {$agenda->id})");
                $failCount++;
                continue;
            }

            // Kirim notifikasi via queue
            SendTelegramNotification::dispatch($telegramUser->chat_id, $message);
            $successCount++;

            Log::info("Notif agenda dikirim ke NIK {$nik}, chat_id={$telegramUser->chat_id}, agenda_id={$agenda->id}");
        }

        Log::info("Agenda observer selesai: {$successCount} berhasil, {$failCount} gagal (Agenda: {$agenda->id})");
    }

    /**
     * Handle event ketika agenda diupdate
     * Hanya kirim notifikasi jika yang_terundang berubah
     */
    public function updated(Agenda $agenda): void
    {
        // Cek apakah yang_terundang berubah
        if (!$agenda->wasChanged('yang_terundang')) {
            return;
        }

        Log::info("Observer Agenda updated, id_agenda={$agenda->id}, yang_terundang berubah");

        // Ambil array NIK yang terundang (yang baru)
        $yangTerundang = $agenda->yang_terundang;

        if (!$yangTerundang || !is_array($yangTerundang) || empty($yangTerundang)) {
            Log::warning("Agenda {$agenda->id} tidak punya yang_terundang atau kosong setelah update.");
            return;
        }

        // Format tanggal untuk notifikasi
        $tanggalMulai = $agenda->mulai 
            ? Carbon::parse($agenda->mulai)->translatedFormat('d F Y, H:i') 
            : '-';
        
        $tanggalAkhir = $agenda->akhir 
            ? Carbon::parse($agenda->akhir)->translatedFormat('d F Y, H:i') 
            : null;

        $tempat = $agenda->tempat ?: '-';
        $pimpinan = $agenda->pimpinan ? $agenda->pimpinan->nama : '-';

        // Generate full URL detail agenda (dengan domain lengkap)
        $url = url(route('acara_show', $agenda->id, false));

        // Buat pesan notifikasi (beda dengan created, ini update)
        $message = "📅 <b>Update Undangan Agenda</b>\n" .
                   "Judul: {$agenda->judul}\n" .
                   "Tanggal: {$tanggalMulai}" . 
                   ($tanggalAkhir ? " - {$tanggalAkhir}" : "") . "\n" .
                   "Tempat: {$tempat}\n" .
                   "Pimpinan: {$pimpinan}\n\n" .
                   "👉 <a href=\"{$url}\">Lihat Detail Agenda</a>";

        // Kirim notifikasi ke semua pegawai yang terundang
        $successCount = 0;
        $failCount = 0;

        foreach ($yangTerundang as $nik) {
            if (!$nik) {
                continue;
            }

            $telegramUser = TelegramUser::where('nik', $nik)->first();

            if (!$telegramUser) {
                Log::warning("Tidak ditemukan TelegramUser untuk NIK {$nik} (Agenda: {$agenda->id})");
                $failCount++;
                continue;
            }

            // Validasi chat_id
            if (!$telegramUser->chat_id) {
                Log::warning("TelegramUser untuk NIK {$nik} tidak punya chat_id (Agenda: {$agenda->id})");
                $failCount++;
                continue;
            }

            // Kirim notifikasi via queue
            SendTelegramNotification::dispatch($telegramUser->chat_id, $message);
            $successCount++;

            Log::info("Notif agenda update dikirim ke NIK {$nik}, chat_id={$telegramUser->chat_id}, agenda_id={$agenda->id}");
        }

        Log::info("Agenda observer update selesai: {$successCount} berhasil, {$failCount} gagal (Agenda: {$agenda->id})");
    }
}
