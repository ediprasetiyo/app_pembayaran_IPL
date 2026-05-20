<?php
/**
 * Jalankan Laravel migrations dari browser (shared hosting tanpa SSH).
 *
 * USAGE:
 *   ?token=ipl-deploy-2026                 → jalankan migrate (pending only)
 *   ?token=ipl-deploy-2026&fresh=1         → DANGEROUS: drop semua tabel + migrate ulang
 *   ?token=ipl-deploy-2026&rollback=1      → rollback migration terakhir
 *   ?token=ipl-deploy-2026&status=1        → cek status migration
 */

if (($_GET['token'] ?? '') !== 'ipl-deploy-2026') {
    http_response_code(404);
    exit('Not Found');
}

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(120);

$basePath = '/home/dszgofcr/ipl-backend';
require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

try {
    if (!empty($_GET['fresh'])) {
        echo "=== ⚠️ MIGRATE:FRESH (drop all + migrate ulang) ===\n";
        echo "Ini akan hapus SEMUA data! Tekan back kalau salah klik.\n\n";
        if (($_GET['confirm'] ?? '') !== 'YES-I-WANT-TO-DROP-ALL-DATA') {
            echo "❌ Aborted. Tambahkan &confirm=YES-I-WANT-TO-DROP-ALL-DATA kalau yakin.\n";
            exit;
        }
        $exit = $kernel->call('migrate:fresh', ['--force' => true]);
    } elseif (!empty($_GET['rollback'])) {
        echo "=== Rollback migration terakhir ===\n\n";
        $exit = $kernel->call('migrate:rollback', ['--force' => true]);
    } elseif (!empty($_GET['status'])) {
        echo "=== Status migration ===\n\n";
        $exit = $kernel->call('migrate:status');
    } else {
        echo "=== Jalankan migration (pending only) ===\n\n";
        $exit = $kernel->call('migrate', ['--force' => true]);
    }

    echo $kernel->output();
    echo "\nExit code: $exit\n";

    // Clear cache supaya schema baru ke-detect
    $kernel->call('cache:clear');
    $kernel->call('config:clear');
    echo "\n✓ Cache cleared.\n";

    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "✓ OPcache reset.\n";
    }

    echo "\n=== SELESAI ===\n";
} catch (\Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    echo $e->getTraceAsString();
}
