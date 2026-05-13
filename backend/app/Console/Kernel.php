<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Generate tagihan IPL setiap awal bulan (tanggal 1, jam 08:00)
        $schedule->command('ipl:generate-tagihan')->monthlyOn(1, '08:00');

        // Cek tagihan terlambat setiap hari jam 09:00
        $schedule->command('ipl:check-terlambat')->dailyAt('09:00');

        // Reminder tagihan 3 hari sebelum jatuh tempo (tanggal 28 setiap bulan)
        $schedule->command('ipl:reminder-tagihan')->monthlyOn(28, '08:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
