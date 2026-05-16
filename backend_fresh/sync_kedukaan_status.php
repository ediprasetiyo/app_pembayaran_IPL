<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Warga;
use App\Models\IplTagihan;

echo "Sync status kedukaan dari data tagihan...\n\n";

$updated = 0;
$wargaList = Warga::where('is_active', true)->get();

foreach ($wargaList as $warga) {
    // Cek apakah ada tagihan kedukaan yang sudah dibayar
    $tagihanKedukaanLunas = IplTagihan::where('warga_id', $warga->id)
        ->where('jenis', 'kedukaan')
        ->where('status', 'sudah_bayar')
        ->latest('tanggal_bayar')
        ->first();

    if ($tagihanKedukaanLunas && !$warga->uang_kedukaan_dibayar) {
        $warga->update([
            'uang_kedukaan_dibayar' => true,
            'tanggal_bayar_kedukaan' => $tagihanKedukaanLunas->tanggal_bayar ?? now(),
        ]);
        echo "✓ {$warga->user?->name}: ditandai sudah bayar kedukaan\n";
        $updated++;
    }
}

echo "\nSelesai: {$updated} warga di-update.\n";
