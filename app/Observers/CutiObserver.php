<?php

namespace App\Observers;

use App\Models\PengajuanLibur;
use App\Models\TelegramUser;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

class CutiObserver
{
    public function created(PengajuanLibur $cuti)
    {
        Log::info("Observer Cuti jalan, id={$cuti->id_pengajuan_libur}");

        $atasanNik = $cuti->nik_atasan_langsung;

        $telegramUser = TelegramUser::where('nik', $atasanNik)->first();
        if ($telegramUser) {
            $pegawai       = optional($cuti->pegawai)->nama ?: '-';
            $atasan        = optional($cuti->pegawai2)->nama ?: '-';
            $jenis         = $cuti->jenis_pengajuan_libur ?: '-';
            $tglAwal       = $cuti->tanggal_awal ?: '-';
            $tglAkhir      = $cuti->tanggal_akhir ?: '-';
            $jmlHari       = $cuti->jumlah_hari ?: '0';
            $keterangan    = $cuti->keterangan ?: '-';

            // generate URL detail cuti (lihat pengajuan)
            $url = route('pengajuan_libur.show', encrypt($cuti->kode_pengajuan_libur));

            $message = "📅 <b>Pengajuan Cuti Baru</b>\n" .
                       "Pegawai: {$pegawai}\n" .
                       "Atasan: {$atasan}\n" .
                       "Jenis: {$jenis}\n" .
                       "Tanggal: {$tglAwal} s/d {$tglAkhir} ({$jmlHari} hari)\n" .
                       "Keterangan: {$keterangan}\n\n" .
                       "👉 <a href=\"{$url}\">Lihat Detail</a>";

            Log::info("Notif cuti dikirim ke NIK {$atasanNik}, chat_id={$telegramUser->chat_id}, url={$url}");

            (new TelegramService())->sendMessage($telegramUser->chat_id, $message);
        } else {
            Log::warning("Tidak ditemukan TelegramUser untuk NIK {$atasanNik}");
        }
    }
}