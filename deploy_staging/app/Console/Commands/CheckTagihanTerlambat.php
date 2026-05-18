<?php

namespace App\Console\Commands;

use App\Models\IplTagihan;
use Illuminate\Console\Command;

class CheckTagihanTerlambat extends Command
{
    protected $signature = 'ipl:check-terlambat';
    protected $description = 'Tandai tagihan IPL yang sudah lewat jatuh tempo sebagai terlambat';

    public function handle(): int
    {
        $terlambat = IplTagihan::where('status', 'belum_bayar')
            ->whereDate('jatuh_tempo', '<', now())
            ->get();

        if ($terlambat->isEmpty()) {
            $this->info('Tidak ada tagihan yang terlambat.');
            return Command::SUCCESS;
        }

        foreach ($terlambat as $tagihan) {
            $tagihan->update(['status' => 'terlambat']);
        }

        $this->info("Ditemukan {$terlambat->count()} tagihan ditandai terlambat.");

        return Command::SUCCESS;
    }
}
