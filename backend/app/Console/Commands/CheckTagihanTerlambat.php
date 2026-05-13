<?php

namespace App\Console\Commands;

use App\Models\IplTagihan;
use App\Services\NotifikasiService;
use Illuminate\Console\Command;

class CheckTagihanTerlambat extends Command
{
    protected $signature = 'ipl:check-terlambat';
    protected $description = 'Cek tagihan IPL yang terlambat dan kirim notifikasi';

    public function __construct(private NotifikasiService $notifikasiService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $terlambat = IplTagihan::where('status', 'belum_bayar')
            ->where('jatuh_tempo', '<', now())
            ->with(['warga.user'])
            ->get();

        if ($terlambat->isEmpty()) {
            $this->info('Tidak ada tagihan yang terlambat.');
            return Command::SUCCESS;
        }

        foreach ($terlambat as $tagihan) {
            $denda = $tagihan->nominal * 0.05;
            $tagihan->update([
                'status' => 'terlambat',
                'denda' => $denda,
            ]);
        }

        $this->notifikasiService->kirimNotifikasiTerlambat($terlambat);

        $this->info("Ditemukan {$terlambat->count()} tagihan terlambat. Notifikasi dikirim.");

        return Command::SUCCESS;
    }
}
