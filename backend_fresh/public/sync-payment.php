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
