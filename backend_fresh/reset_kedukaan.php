<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Warga;
use App\Models\IplTagihan;

$tarifKedukaan = (int) env('IPL_KEDUKAAN_AMOUNT', 20000);
$jatuhTempo = now()->setDay(10);

// Set semua warga uang_kedukaan_dibayar = false
$count = Warga::query()->update([
    'uang_kedukaan_dibayar' => false,
    'tanggal_bayar_kedukaan' => null,
]);

echo "Reset {$count} warga ke status BELUM BAYAR kedukaan.\n\n";

// Buatkan tagihan kedukaan untuk yang belum punya
$wargaList = Warga::where('is_active', true)->get();

$created = 0;
foreach ($wargaList as $warga) {
    $exists = IplTagihan::where('warga_id', $warga->id)
        ->where('jenis', 'kedukaan')
        ->exists();

    if ($exists) {
        echo "- Warga ID {$warga->id} ({$warga->user?->name}): tagihan kedukaan sudah ada\n";
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

    echo "+ Warga ID {$warga->id} ({$warga->user?->name}): tagihan kedukaan Rp 20.000 dibuat\n";
    $created++;
}

echo "\nSelesai: {$created} tagihan kedukaan dibuat.\n";
