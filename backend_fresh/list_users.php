<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = \App\Models\User::with('warga')->get();

echo str_repeat('=', 80) . PHP_EOL;
echo "DAFTAR USER & WARGA" . PHP_EOL;
echo str_repeat('=', 80) . PHP_EOL;
printf("%-3s | %-20s | %-15s | %-12s | %s\n", 'ID', 'Nama', 'Phone', 'Role', 'Alamat');
echo str_repeat('-', 80) . PHP_EOL;

foreach ($users as $u) {
    $alamat = $u->warga
        ? "Blok {$u->warga->blok}-{$u->warga->nomor_rumah}"
        : '-';
    printf("%-3d | %-20s | %-15s | %-12s | %s\n",
        $u->id, $u->name, $u->phone, $u->role, $alamat);
}

echo str_repeat('=', 80) . PHP_EOL;
echo "Total: " . $users->count() . " user" . PHP_EOL;
