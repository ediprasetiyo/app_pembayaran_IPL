<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Warga;
use App\Models\IplTagihan;

$tarifKedukaan = (int) env('IPL_KEDUKAAN_AMOUNT', 20000);
$jatuhTempo = now()->setDay(10);

$wargaList = Warga::where('uang_kedukaan_dibayar', false)
    ->where('is_active', true)
    ->get();

echo "Cek " . $wargaList->count() . " warga yang belum bayar kedukaan...\n";

$created = 0;
$skipped = 0;

foreach ($wargaList as $warga) {
    $exists = IplTagihan::where('warga_id', $warga->id)
        ->where('jenis', 'kedukaan')
        ->exists();

    if ($exists) {
        echo "- Warga ID {$warga->id}: sudah ada tagihan kedukaan, skip\n";
        $skipped++;
        continue;
    }

    IplTagihan::create([
        'warga_id' => $warga->id,
        'jenis' => 'kedukaan',
        'bulan' => now()->month,
        'tahun' => now()->year,
        'nominal' => $tarifKedukaan,
        'jatuh_tempo' => $jatuhTempo,
        'status' => 'belum_bayar',
        'keterangan' => 'Uang kedukaan (sekali bayar untuk warga baru)',
    ]);

    echo "+ Warga ID {$warga->id}: dibuatkan tagihan kedukaan Rp 20.000\n";
    $created++;
}

echo "\nSelesai: {$created} dibuat, {$skipped} dilewati.\n";
