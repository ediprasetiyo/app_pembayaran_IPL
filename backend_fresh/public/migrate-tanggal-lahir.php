<?php
/**
 * One-time migration script: add tanggal_lahir & tempat_lahir to users table
 *
 * Usage:
 *   1. Upload file ini ke public_html/
 *   2. Akses: https://yourdomain.com/migrate-tanggal-lahir.php?token=fix-ipl-2026
 *   3. Setelah sukses, DELETE file ini!
 */

if (($_GET['token'] ?? '') !== 'fix-ipl-2026') {
    http_response_code(404);
    exit('Not Found');
}

echo "<pre>";

$basePath = '/home/dszgofcr/ipl-backend';
require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $schema = \Illuminate\Support\Facades\Schema::connection('mysql');

    // Cek apakah kolom sudah ada
    if (!$schema->hasColumn('users', 'tanggal_lahir')) {
        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE users ADD COLUMN tanggal_lahir DATE NULL AFTER phone"
        );
        echo "✅ Kolom 'tanggal_lahir' ditambahkan\n";
    } else {
        echo "ℹ️  Kolom 'tanggal_lahir' sudah ada\n";
    }

    if (!$schema->hasColumn('users', 'tempat_lahir')) {
        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE users ADD COLUMN tempat_lahir VARCHAR(255) NULL AFTER tanggal_lahir"
        );
        echo "✅ Kolom 'tempat_lahir' ditambahkan\n";
    } else {
        echo "ℹ️  Kolom 'tempat_lahir' sudah ada\n";
    }

    // Clear caches agar perubahan terbaca
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->call('config:clear');
    $kernel->call('cache:clear');
    $kernel->call('config:cache');
    echo "\n✅ Cache cleared & re-cached\n";

    echo "\n=== SELESAI ===\n";
    echo "PENTING: Hapus file ini setelah berhasil!\n";
    echo "Path: /home/dszgofcr/public_html/migrate-tanggal-lahir.php\n";

} catch (\Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
