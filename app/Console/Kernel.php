<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
         $schedule->command('storage:temp_surat:clear')->hourly();
         
         // Kirim notifikasi jadwal budaya kerja setiap jam
         // Command akan cek jadwal yang akan datang dan kirim notifikasi 6 jam sebelum jadwal
         $schedule->command('jadwal:notify')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected $commands = [
        \App\Console\Commands\ClearTempSurat::class,
    ];
    
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
