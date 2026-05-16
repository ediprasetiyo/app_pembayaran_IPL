<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tagihans = \App\Models\IplTagihan::with('warga.user')->orderBy('id')->get();

echo str_repeat('=', 100) . PHP_EOL;
echo "DAFTAR TAGIHAN IPL" . PHP_EOL;
echo str_repeat('=', 100) . PHP_EOL;

if ($tagihans->isEmpty()) {
    echo "BELUM ADA TAGIHAN." . PHP_EOL;
} else {
    printf("%-4s | %-20s | %-12s | %-12s | %-12s | %-15s\n",
        'ID', 'Nama Warga', 'Phone', 'Jenis', 'Status', 'Nominal');
    echo str_repeat('-', 100) . PHP_EOL;
    foreach ($tagihans as $t) {
        printf("%-4d | %-20s | %-12s | %-12s | %-12s | Rp %s\n",
            $t->id,
            $t->warga?->user?->name ?? '-',
            $t->warga?->user?->phone ?? '-',
            $t->jenis,
            $t->status,
            number_format($t->nominal, 0, ',', '.')
        );
    }
}
echo str_repeat('=', 100) . PHP_EOL;
echo "Total: " . $tagihans->count() . " tagihan" . PHP_EOL;
