<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Tanggal 1 jam 08:00 → Generate tagihan IPL bulanan + kedukaan (warga baru)
        $schedule->command('ipl:generate-tagihan')->monthlyOn(1, '08:00');

        // Setiap hari jam 08:00 → kirim reminder ke warga yg belum bayar
        // (logikanya ada di command: hanya jalan di tgl 10, 12, 14, 16, dst)
        $schedule->command('ipl:reminder-tagihan')->dailyAt('08:00');

        // Tiap hari jam 09:00 → tandai tagihan terlambat (lewat jatuh tempo)
        $schedule->command('ipl:check-terlambat')->dailyAt('09:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
