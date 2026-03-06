<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Console\Command;

class FcmTestCommand extends Command
{
    protected $signature = 'fcm:test 
                            {user_id : ID user (users.id) yang punya device terdaftar} 
                            {--title= : Judul notifikasi (opsional)} 
                            {--body= : Isi notifikasi (opsional)}';

    protected $description = 'Kirim notifikasi uji FCM ke semua device user (untuk testing di dev)';

    public function handle()
    {
        $userId = (int) $this->argument('user_id');
        $user = User::find($userId);
        if (!$user) {
            $this->error("User dengan ID {$userId} tidak ditemukan.");
            return 1;
        }

        $title = $this->option('title') ?: 'Test dari SIKAT (CLI)';
        $body = $this->option('body') ?: 'Ini notifikasi uji dari artisan. FCM berjalan dengan baik.';

        $this->info("Mengirim notifikasi uji ke user: {$user->name} (ID: {$userId})...");

        $fcm = app(FcmService::class);
        $result = $fcm->sendToUser($userId, $title, $body, ['type' => 'test', 'env' => app()->environment()]);

        if ($result['sent'] === 0 && $result['failed'] === 0) {
            $this->warn('User ini belum punya device terdaftar. Daftarkan dulu dari app via POST /api/notifications/register-device.');
            return 1;
        }

        $this->info("Terikirim: {$result['sent']}, Gagal: {$result['failed']}");
        return 0;
    }
}
