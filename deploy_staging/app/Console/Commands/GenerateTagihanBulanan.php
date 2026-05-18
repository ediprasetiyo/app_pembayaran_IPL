<?php

namespace App\Console\Commands;

use App\Models\IplTagihan;
use App\Models\Warga;
use App\Services\NotifikasiService;
use Illuminate\Console\Command;

class GenerateTagihanBulanan extends Command
{
    protected $signature = 'ipl:generate-tagihan {--bulan=} {--tahun=}';
    protected $description = 'Generate tagihan IPL bulanan + tagihan kedukaan untuk warga baru';

    public function __construct(private NotifikasiService $notifikasiService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $bulan = (int) ($this->option('bulan') ?? now()->month);
        $tahun = (int) ($this->option('tahun') ?? now()->year);
        $tarifIpl = (int) env('IPL_MONTHLY_AMOUNT', 65000);
        $tarifKedukaan = (int) env('IPL_KEDUKAAN_AMOUNT', 20000);

        $wargaAktif = Warga::where('is_active', true)
            ->where('blok', 'E')
            ->with('user')
            ->get();

        $generatedIpl = 0;
        $generatedKedukaan = 0;
        $skipped = 0;

        // Jatuh tempo IPL bulanan: tanggal 10 bulan berjalan
        $jatuhTempo = now()->setMonth($bulan)->setYear($tahun)->setDay(10);

        foreach ($wargaAktif as $warga) {
            // ===== Tagihan IPL bulanan (65.000) =====
            $exists = IplTagihan::where('warga_id', $warga->id)
                ->where('jenis', 'ipl_bulanan')
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->exists();

            if ($exists) {
                $skipped++;
            } else {
                IplTagihan::create([
                    'warga_id' => $warga->id,
                    'jenis' => 'ipl_bulanan',
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'nominal' => $tarifIpl,
                    'jatuh_tempo' => $jatuhTempo,
                    'status' => 'belum_bayar',
                ]);
                $generatedIpl++;
            }

            // ===== Tagihan Kedukaan (20.000) — hanya 1x untuk warga baru =====
            if (!$warga->uang_kedukaan_dibayar) {
                $existsKedukaan = IplTagihan::where('warga_id', $warga->id)
                    ->where('jenis', 'kedukaan')
                    ->where('status', 'belum_bayar')
                    ->exists();

                if (!$existsKedukaan) {
                    IplTagihan::create([
                        'warga_id' => $warga->id,
                        'jenis' => 'kedukaan',
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'nominal' => $tarifKedukaan,
                        'jatuh_tempo' => $jatuhTempo,
                        'status' => 'belum_bayar',
                        'keterangan' => 'Uang kedukaan (sekali bayar untuk warga baru)',
                    ]);
                    $generatedKedukaan++;
                }
            }
        }

        // Kirim notifikasi ke semua warga yang punya tagihan baru
        $tagihans = IplTagihan::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status', 'belum_bayar')
            ->with(['warga.user'])
            ->get();

        $this->notifikasiService->kirimReminderTagihan($tagihans);

        $this->info("Bulan {$bulan}/{$tahun}: IPL {$generatedIpl} dibuat, Kedukaan {$generatedKedukaan} dibuat, {$skipped} IPL dilewati.");

        return Command::SUCCESS;
    }
}
