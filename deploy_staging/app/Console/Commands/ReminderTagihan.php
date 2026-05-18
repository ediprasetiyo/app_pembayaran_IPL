<?php

namespace App\Console\Commands;

use App\Models\IplTagihan;
use App\Services\NotifikasiService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ReminderTagihan extends Command
{
    protected $signature = 'ipl:reminder-tagihan';
    protected $description = 'Kirim reminder tagihan: pada tgl 10, lalu tiap 2 hari sekali sampai dibayar';

    public function __construct(private NotifikasiService $notifikasiService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $hariIni = Carbon::today();
        $tanggal = $hariIni->day;

        // Hanya kirim reminder mulai tanggal 10 ke atas
        if ($tanggal < 10) {
            $this->info("Belum tanggal 10. Tidak mengirim reminder.");
            return Command::SUCCESS;
        }

        // Logika: kirim reminder pada tanggal 10, 12, 14, 16, 18, ... (genap setelah 10)
        $hariSetelah10 = $tanggal - 10;
        if ($hariSetelah10 % 2 !== 0) {
            $this->info("Bukan jadwal reminder (tanggal {$tanggal}). Skip.");
            return Command::SUCCESS;
        }

        $belumBayar = IplTagihan::whereIn('status', ['belum_bayar', 'terlambat'])
            ->where('bulan', $hariIni->month)
            ->where('tahun', $hariIni->year)
            ->with(['warga.user'])
            ->get();

        if ($belumBayar->isEmpty()) {
            $this->info('Semua warga sudah bayar. Tidak ada reminder dikirim.');
            return Command::SUCCESS;
        }

        $this->notifikasiService->kirimReminderTagihan($belumBayar);

        foreach ($belumBayar as $tagihan) {
            $tagihan->update(['reminder_terakhir' => $hariIni]);
        }

        $this->info("Reminder tanggal {$tanggal} terkirim ke {$belumBayar->count()} warga.");

        return Command::SUCCESS;
    }
}
