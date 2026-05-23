<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalBudayaKerja;
use App\Models\TelegramUser;
use App\Models\WhatsappLog;
use App\Jobs\SendTelegramNotification;
use App\Jobs\SendWhatsAppNotification;
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
    protected $description = 'Kirim notifikasi Telegram & WhatsApp jadwal budaya kerja sekali per slot waktu (hourly scheduler)';

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
                }

                // Validasi nomor WhatsApp
                if (empty($petugas->no_telp)) {
                    $this->warn("Jadwal ID {$jadwal->id_jadwal_budaya_kerja}: No. WhatsApp tidak ditemukan untuk {$petugas->nama}");
                }

                // Hitung waktu jadwal dan waktu notifikasi
                $tanggalBertugas = Carbon::parse($jadwal->tanggal_bertugas);
                $shift = strtolower(trim($jadwal->shift));
                
                // Tentukan jam shift dan waktu notifikasi
                if ($shift === 'pagi') {
                    $jamShift = '06:30';
                    $emojiShift = '🌅';
                    // Pagi: Kirim jam 21:00 malam sebelumnya
                    $waktuNotifikasi = $tanggalBertugas->copy()->subDay()->setTime(21, 0);
                } elseif ($shift === 'sore') {
                    $jamShift = '13:30';
                    $emojiShift = '🌇';
                    // Sore: Kirim jam 09:00 pagi hari bertugas (satu kali, selaras dengan controller)
                    $waktuNotifikasi = $tanggalBertugas->copy()->setTime(9, 0);
                } else {
                    $this->warn("Jadwal ID {$jadwal->id_jadwal_budaya_kerja}: Shift tidak dikenal: {$jadwal->shift}");
                    $skipCount++;
                    continue;
                }

                // Hanya kirim pada jam notifikasi yang ditentukan (bukan seluruh rentang sampai shift).
                // Scheduler `hourly` → paling banyak satu eksekusi per jam; dengan ini tidak ada spam per jam.
                $slotStart = $waktuNotifikasi->copy()->startOfHour();
                $slotEnd = $waktuNotifikasi->copy()->endOfHour();
                $isTimeToNotify = $now->between($slotStart, $slotEnd, true);

                if ($isTimeToNotify) {
                    // Format tanggal untuk notifikasi
                    $hariTanggal = $tanggalBertugas->translatedFormat('l, d F Y');

                    $templateId = config('services.qontak.jadwal_template_id', 'bbbcc349-de87-4b48-8e30-ebf389d329a6');
                    $waVariables = [
                        $jadwal->petugas->nama,
                        $hariTanggal,
                        $jadwal->shift,
                        $jamShift,
                    ];
                    $waMessageText = implode(' | ', $waVariables);

                    // Pengaman: satu WA sukses per pesan per tanggal slot notifikasi (bukan ulang tiap jam)
                    $alreadySentWa = WhatsappLog::query()
                        ->where('nik', $nik)
                        ->where('template_id', $templateId)
                        ->where('message', $waMessageText)
                        ->whereDate('created_at', $waktuNotifikasi->toDateString())
                        ->where('status', 'success')
                        ->exists();
                    
                    // ===== KIRIM TELEGRAM =====
                    if ($telegramUser && $telegramUser->chat_id) {
                        $message = "📢 <b>Pengingat Jadwal Jaga Budaya Kerja</b>\n\n" .
                                   "Halo, <b>{$jadwal->petugas->nama}</b> 👋\n\n" .
                                   "Kami ingin mengingatkan jadwal jaga budaya kerja Anda:\n\n" .
                                   "📅 <b>Hari/Tanggal:</b> {$hariTanggal}\n" .
                                   "⏰ <b>Shift:</b> {$jadwal->shift} {$emojiShift}\n" .
                                   "🕒 <b>Jam Masuk:</b> {$jamShift}\n\n" .
                                   "Mohon datang tepat waktu dan tetap semangat dalam bekerja! 💪😊\n\n" .
                                   "Terima kasih. 🙏";

                        SendTelegramNotification::dispatch($telegramUser->chat_id, $message);
                        $this->info("📨 Telegram queued: {$jadwal->petugas->nama}");
                    }

                    // ===== KIRIM WHATSAPP =====
                    if (! empty($petugas->no_telp) && ! $alreadySentWa) {
                        SendWhatsAppNotification::dispatch(
                            [
                                'nik' => $nik,
                                'nama' => $jadwal->petugas->nama,
                                'nomor' => $petugas->no_telp,
                            ],
                            $waVariables,
                            $templateId
                        );
                        $this->info("📱 WhatsApp queued: {$jadwal->petugas->nama}");
                    } elseif (! empty($petugas->no_telp) && $alreadySentWa) {
                        $this->line("⏭️  WA sudah terkirim untuk {$jadwal->petugas->nama} ({$waMessageText}), lewati duplikat.");
                    }

                    $successCount++;
                    Log::info("Jadwal budaya kerja notifikasi dikirim", [
                        'jadwal_id' => $jadwal->id_jadwal_budaya_kerja,
                        'nik' => $nik,
                        'nama' => $jadwal->petugas->nama,
                        'tanggal_bertugas' => $tanggalBertugas->toDateString(),
                        'shift' => $jadwal->shift,
                        'waktu_notifikasi' => $waktuNotifikasi->toDateTimeString(),
                        'telegram' => (bool) ($telegramUser && $telegramUser->chat_id),
                        'whatsapp_queued' => ! empty($petugas->no_telp) && ! $alreadySentWa,
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
