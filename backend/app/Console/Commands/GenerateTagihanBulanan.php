<?php

namespace App\Console\Commands;

use App\Models\IplTagihan;
use App\Models\Warga;
use App\Services\NotifikasiService;
use Illuminate\Console\Command;

class GenerateTagihanBulanan extends Command
{
    protected $signature = 'ipl:generate-tagihan {--bulan=} {--tahun=}';
    protected $description = 'Generate tagihan IPL bulanan untuk semua warga aktif';

    public function __construct(private NotifikasiService $notifikasiService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $bulan = (int) ($this->option('bulan') ?? now()->month);
        $tahun = (int) ($this->option('tahun') ?? now()->year);
        $tarifIpl = config('app.ipl_monthly_amount', 150000);

        $wargaAktif = Warga::where('is_active', true)
            ->where('blok', 'E')
            ->with('user')
            ->get();

        $generated = 0;
        $skipped = 0;

        foreach ($wargaAktif as $warga) {
            $exists = IplTagihan::where('warga_id', $warga->id)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            IplTagihan::create([
                'warga_id' => $warga->id,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'nominal' => $tarifIpl,
                'jatuh_tempo' => now()->setMonth($bulan)->setYear($tahun)->endOfMonth(),
                'status' => 'belum_bayar',
            ]);

            $generated++;
        }

        $tagihans = IplTagihan::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status', 'belum_bayar')
            ->with(['warga.user'])
            ->get();

        $this->notifikasiService->kirimReminderTagihan($tagihans);

        $this->info("Tagihan bulan {$bulan}/{$tahun}: {$generated} dibuat, {$skipped} dilewati.");

        return Command::SUCCESS;
    }
}
