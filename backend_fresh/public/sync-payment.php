<?php
/**
 * Manual trigger sync status pembayaran dari Midtrans
 *
 * Pakai ini kalau Midtrans webhook tidak sampai ke server
 * (notification URL belum di-set di Midtrans dashboard).
 *
 * USAGE:
 *   ?token=ipl-deploy-2026                → sync SEMUA pembayaran pending
 *   ?token=ipl-deploy-2026&id=1           → sync pembayaran spesifik (id)
 */

if (($_GET['token'] ?? '') !== 'ipl-deploy-2026') {
    http_response_code(404);
    exit('Not Found');
}

header('Content-Type: text/plain; charset=utf-8');

$basePath = '/home/dszgofcr/ipl-backend';
require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$midtrans = $app->make(\App\Services\MidtransService::class);

// === DIAGNOSTIC: cek versi MidtransService yang ke-load ===
$reflection = new \ReflectionClass($midtrans);
$filePath = $reflection->getFileName();
$fileSize = file_exists($filePath) ? filesize($filePath) : 0;
$fileMtime = file_exists($filePath) ? date('Y-m-d H:i:s', filemtime($filePath)) : 'N/A';
$hasGetStatus = method_exists($midtrans, 'getStatus');
$methods = array_map(fn($m) => $m->getName(), $reflection->getMethods(\ReflectionMethod::IS_PUBLIC));

echo "=== DIAGNOSTIC MidtransService ===\n";
echo "File path : $filePath\n";
echo "File size : $fileSize bytes\n";
echo "Modified  : $fileMtime\n";
echo "Has getStatus(): " . ($hasGetStatus ? 'YES ✓' : 'NO ✗') . "\n";
echo "Public methods: " . implode(', ', $methods) . "\n";
echo "OPcache enabled: " . (function_exists('opcache_get_status') && opcache_get_status() !== false ? 'YES' : 'NO') . "\n";
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache: RESET\n";
}
echo "\n";

if (!$hasGetStatus) {
    echo "❌ getStatus() method TIDAK ADA di file " . $filePath . "\n";
    echo "Solusi: jalankan deploy.php lagi, atau edit manual via cPanel File Manager.\n";
    echo "Hapus juga file: " . $filePath . ".bak (kalau ada, untuk paksa fresh write)\n";
    exit;
}

$id = $_GET['id'] ?? null;
$query = \App\Models\Pembayaran::query();
if ($id) {
    $query->where('id', (int) $id);
} else {
    $query->where('status', 'pending');
}

$payments = $query->get();
echo "Found {$payments->count()} pembayaran to sync\n\n";

foreach ($payments as $p) {
    echo "=== Pembayaran ID {$p->id} (order: {$p->order_id}) ===\n";
    echo "Current status: {$p->status}\n";

    $midStatus = $midtrans->getStatus($p->order_id);
    if (!$midStatus) {
        echo "❌ Failed to fetch Midtrans status\n\n";
        continue;
    }

    $tx = $midStatus['transaction_status'] ?? null;
    $fraud = $midStatus['fraud_status'] ?? null;
    echo "Midtrans transaction_status: $tx\n";
    echo "Midtrans fraud_status: $fraud\n";

    $newStatus = match (true) {
        $tx === 'capture' && $fraud === 'accept' => 'success',
        $tx === 'settlement' => 'success',
        in_array($tx, ['cancel', 'deny', 'expire'], true) => 'failed',
        $tx === 'pending' => 'pending',
        default => null,
    };

    if (!$newStatus) {
        echo "⚠️  Status tidak dikenali, skip\n\n";
        continue;
    }

    if ($newStatus === $p->status) {
        echo "✓ Status sudah benar ($newStatus), skip update\n\n";
        continue;
    }

    $p->update([
        'status' => $newStatus,
        'midtrans_transaction_id' => $midStatus['transaction_id'] ?? null,
        'midtrans_payment_type' => $midStatus['payment_type'] ?? null,
        'midtrans_response' => $midStatus,
    ]);

    if ($newStatus === 'success') {
        $p->tagihan?->update([
            'status' => 'sudah_bayar',
            'tanggal_bayar' => now(),
        ]);
        echo "✅ UPDATED → status=$newStatus, tagihan dilunasi!\n";
    } else {
        echo "✅ UPDATED → status=$newStatus\n";
    }
    echo "\n";
}

echo "=== SELESAI ===\n";
