<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearTempSurat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:temp_surat:clear {--hours=2 : Hapus hanya file yang lebih lama dari jam ini}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghapus file lama di storage/app/public/temp_surat (default: lebih dari 2 jam)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Default disk 'local' root = storage_path('app/public'), jadi path relatif = temp_surat
        $directory = 'temp_surat';
        $hours = (int) $this->option('hours');
        $minAge = $hours * 3600; // detik
        $now = time();

        $disk = Storage::disk('local');
        if (!$disk->exists($directory)) {
            $this->warn('Direktori temp_surat tidak ditemukan.');
            return 0;
        }

        $deleted = 0;
        $files = $disk->allFiles($directory);

        foreach ($files as $file) {
            $path = $disk->path($file);
            if (!is_file($path)) {
                continue;
            }
            $age = $now - filemtime($path);
            if ($age >= $minAge) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("File di temp_surat yang lebih lama dari {$hours} jam telah dihapus: {$deleted} file.");
        return 0;
    }
}