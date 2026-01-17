<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalBudayaKerja;
use App\Models\TelegramUser;
use App\Jobs\SendTelegramNotification;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendJadwalBudayaKerjaNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jadwal:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi Telegram untuk jadwal budaya kerja 6 jam sebelum jadwal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai proses pengiriman notifikasi jadwal budaya kerja...');
        
        $now = Carbon::now();
        $successCount = 0;
        $failCount = 0;
        $skipCount = 0;

        // Ambil semua jadwal yang akan datang (hari ini dan besok)
        $jadwalList = JadwalBudayaKerja::with('petugas')
            ->whereDate('tanggal_bertugas', '>=', $now->toDateString())
            ->whereDate('tanggal_bertugas', '<=', $now->copy()->addDay()->toDateString())
            ->get();

        foreach ($jadwalList as $jadwal) {
            try {
                // Validasi data
                if (!$jadwal->petugas) {
                    $this->warn("Jadwal ID {$jadwal->id_jadwal_budaya_kerja}: Petugas tidak ditemukan");
                    $skipCount++;
                    continue;
                }

                // Ambil NIK dari petugas (petugas->nip = pegawai->nik)
                // Dari relasi: Petugas->pegawai (via nip->nik)
                $petugas = $jadwal->petugas;
                $pegawai = \App\Models\Pegawai::where('nik', $petugas->nip)->first();
                
                if (!$pegawai) {
                    $this->warn("Jadwal ID {$jadwal->id_jadwal_budaya_kerja}: Pegawai tidak ditemukan untuk petugas {$petugas->nama} (NIP: {$petugas->nip})");
                    $skipCount++;
                    continue;
                }

                // Ambil NIK dari pegawai
                $nik = $pegawai->nik;
                if (!$nik) {
                    $this->warn("Jadwal ID {$jadwal->id_jadwal_budaya_kerja}: NIK tidak ditemukan");
                    $skipCount++;
                    continue;
                }

                // Cari TelegramUser berdasarkan NIK
                $telegramUser = TelegramUser::where('nik', $nik)->first();
                if (!$telegramUser || !$telegramUser->chat_id) {
                    $this->warn("Jadwal ID {$jadwal->id_jadwal_budaya_kerja}: TelegramUser tidak ditemukan untuk NIK {$nik}");
                    $skipCount++;
                    continue;
                }

                // Hitung waktu jadwal dan waktu notifikasi
                $tanggalBertugas = Carbon::parse($jadwal->tanggal_bertugas);
                $shift = strtolower(trim($jadwal->shift));
                
                // Tentukan jam shift
                if ($shift === 'pagi') {
                    $jamShift = '06:30';
                    $emojiShift = '🌅';
                } elseif ($shift === 'sore') {
                    $jamShift = '13:30';
                    $emojiShift = '🌇';
                } else {
                    $this->warn("Jadwal ID {$jadwal->id_jadwal_budaya_kerja}: Shift tidak dikenal: {$jadwal->shift}");
                    $skipCount++;
                    continue;
                }

                // Buat datetime lengkap untuk jadwal
                $waktuJadwal = $tanggalBertugas->copy()->setTimeFromTimeString($jamShift);
                
                // Waktu notifikasi = 6 jam sebelum jadwal
                $waktuNotifikasi = $waktuJadwal->copy()->subHours(6);
                
                // Cek apakah sudah waktunya kirim notifikasi
                // Kirim jika: waktu sekarang >= waktu notifikasi DAN waktu sekarang <= waktu jadwal
                // Ini memastikan notifikasi dikirim setelah waktu notifikasi tapi sebelum jadwal dimulai
                $isTimeToNotify = $now->greaterThanOrEqualTo($waktuNotifikasi) && 
                                  $now->lessThanOrEqualTo($waktuJadwal);
                
                if ($isTimeToNotify) {
                    // Format tanggal untuk notifikasi
                    $hariTanggal = $tanggalBertugas->translatedFormat('l, d F Y');
                    
                    // Buat pesan notifikasi
                    $message = "📢 <b>Pengingat Jadwal Jaga Budaya Kerja</b>\n\n" .
                               "Halo, <b>{$jadwal->petugas->nama}</b> 👋\n\n" .
                               "Kami ingin mengingatkan jadwal jaga budaya kerja Anda:\n\n" .
                               "📅 <b>Hari/Tanggal:</b> {$hariTanggal}\n" .
                               "⏰ <b>Shift:</b> {$jadwal->shift} {$emojiShift}\n" .
                               "🕒 <b>Jam Masuk:</b> {$jamShift}\n\n" .
                               "Mohon datang tepat waktu dan tetap semangat dalam bekerja! 💪😊\n\n" .
                               "Terima kasih. 🙏";

                    // Kirim notifikasi via queue
                    SendTelegramNotification::dispatch($telegramUser->chat_id, $message);
                    
                    $successCount++;
                    $this->info("✅ Notifikasi dikirim ke {$jadwal->petugas->nama} (NIK: {$nik}) - Jadwal: {$hariTanggal}, Shift: {$jadwal->shift}");
                    
                    Log::info("Jadwal budaya kerja notifikasi dikirim", [
                        'jadwal_id' => $jadwal->id_jadwal_budaya_kerja,
                        'nik' => $nik,
                        'nama' => $jadwal->petugas->nama,
                        'tanggal_bertugas' => $tanggalBertugas->toDateString(),
                        'shift' => $jadwal->shift,
                        'waktu_notifikasi' => $waktuNotifikasi->toDateTimeString(),
                    ]);
                } else {
                    // Belum waktunya atau sudah lewat
                    $diffInHours = $now->diffInHours($waktuNotifikasi, false);
                    if ($diffInHours < 0) {
                        $this->line("⏭️  Jadwal ID {$jadwal->id_jadwal_budaya_kerja}: Waktu notifikasi sudah lewat (selisih: " . abs($diffInHours) . " jam yang lalu)");
                    } else {
                        $this->line("⏳ Jadwal ID {$jadwal->id_jadwal_budaya_kerja}: Belum waktunya (masih " . round($diffInHours, 1) . " jam lagi)");
                    }
                    $skipCount++;
                }
            } catch (\Exception $e) {
                $failCount++;
                $this->error("❌ Error pada jadwal ID {$jadwal->id_jadwal_budaya_kerja}: " . $e->getMessage());
                Log::error("Error kirim notifikasi jadwal budaya kerja", [
                    'jadwal_id' => $jadwal->id_jadwal_budaya_kerja,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("\n📊 Ringkasan:");
        $this->info("✅ Berhasil: {$successCount}");
        $this->info("⏭️  Dilewati: {$skipCount}");
        $this->info("❌ Gagal: {$failCount}");
        $this->info("Total jadwal dicek: " . $jadwalList->count());
        
        return Command::SUCCESS;
    }
}
