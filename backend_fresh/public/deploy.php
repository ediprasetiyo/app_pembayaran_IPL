<?php
/**
 * Auto-deploy script: pull file terbaru dari GitHub
 *
 * Akses: https://yourdomain.com/deploy.php?token=ipl-deploy-2026
 *
 * Cara kerja: ambil file via raw.githubusercontent.com,
 * tulis ke /home/dszgofcr/ipl-backend/...
 *
 * SECURITY: Pakai token sebagai proteksi minimal.
 * Hapus / nonaktifkan setelah project stabil.
 */

if (($_GET['token'] ?? '') !== 'ipl-deploy-2026') {
    http_response_code(404);
    exit('Not Found');
}

set_time_limit(120);
header('Content-Type: text/plain; charset=utf-8');

echo "=== IPL Auto-Deploy from GitHub ===\n\n";

// Konfigurasi
$repo = 'ediprasetiyo/app_pembayaran_IPL';
$branch = 'main';
$basePath = '/home/dszgofcr/ipl-backend';

// Daftar file yang di-deploy (path di repo → path di server)
// Bisa ditambah sesuai kebutuhan
$files = [
    'backend_fresh/app/Http/Controllers/Api/WargaController.php'
        => $basePath . '/app/Http/Controllers/Api/WargaController.php',
    'backend_fresh/app/Http/Controllers/Api/AuthController.php'
        => $basePath . '/app/Http/Controllers/Api/AuthController.php',
    'backend_fresh/app/Http/Controllers/Api/NewsController.php'
        => $basePath . '/app/Http/Controllers/Api/NewsController.php',
    'backend_fresh/app/Http/Controllers/Api/PengaduanController.php'
        => $basePath . '/app/Http/Controllers/Api/PengaduanController.php',
    'backend_fresh/app/Http/Controllers/Api/IplController.php'
        => $basePath . '/app/Http/Controllers/Api/IplController.php',
    'backend_fresh/app/Http/Controllers/Api/SettingsController.php'
        => $basePath . '/app/Http/Controllers/Api/SettingsController.php',
    'backend_fresh/app/Http/Controllers/Api/UserController.php'
        => $basePath . '/app/Http/Controllers/Api/UserController.php',
    'backend_fresh/app/Models/IplTagihan.php'
        => $basePath . '/app/Models/IplTagihan.php',
    'backend_fresh/app/Models/Pembayaran.php'
        => $basePath . '/app/Models/Pembayaran.php',
    'backend_fresh/app/Models/User.php'
        => $basePath . '/app/Models/User.php',
    'backend_fresh/app/Models/Warga.php'
        => $basePath . '/app/Models/Warga.php',
    'backend_fresh/app/Models/AnggotaKeluarga.php'
        => $basePath . '/app/Models/AnggotaKeluarga.php',
    'backend_fresh/app/Models/Pengaduan.php'
        => $basePath . '/app/Models/Pengaduan.php',
    'backend_fresh/app/Models/Setting.php'
        => $basePath . '/app/Models/Setting.php',
    'backend_fresh/app/Models/Notifikasi.php'
        => $basePath . '/app/Models/Notifikasi.php',
    'backend_fresh/app/Services/MidtransService.php'
        => $basePath . '/app/Services/MidtransService.php',
    'backend_fresh/app/Services/NotifikasiService.php'
        => $basePath . '/app/Services/NotifikasiService.php',
    'backend_fresh/app/Console/Commands/ReminderTagihan.php'
        => $basePath . '/app/Console/Commands/ReminderTagihan.php',
    'backend_fresh/app/Console/Commands/CheckTagihanTerlambat.php'
        => $basePath . '/app/Console/Commands/CheckTagihanTerlambat.php',
    'backend_fresh/app/Console/Commands/GenerateTagihanBulanan.php'
        => $basePath . '/app/Console/Commands/GenerateTagihanBulanan.php',
    'backend_fresh/app/Console/Kernel.php'
        => $basePath . '/app/Console/Kernel.php',
    'backend_fresh/app/Http/Controllers/Api/NotifikasiController.php'
        => $basePath . '/app/Http/Controllers/Api/NotifikasiController.php',
    'backend_fresh/app/Http/Controllers/Api/BlokController.php'
        => $basePath . '/app/Http/Controllers/Api/BlokController.php',
    'backend_fresh/app/Http/Controllers/Api/AuditLogController.php'
        => $basePath . '/app/Http/Controllers/Api/AuditLogController.php',
    'backend_fresh/app/Models/Blok.php'
        => $basePath . '/app/Models/Blok.php',
    'backend_fresh/app/Models/AuditLog.php'
        => $basePath . '/app/Models/AuditLog.php',
    'backend_fresh/app/Services/AuditLogger.php'
        => $basePath . '/app/Services/AuditLogger.php',
    'backend_fresh/database/migrations/2026_05_20_000001_create_bloks_table.php'
        => $basePath . '/database/migrations/2026_05_20_000001_create_bloks_table.php',
    'backend_fresh/database/migrations/2026_05_20_000002_create_audit_logs_table.php'
        => $basePath . '/database/migrations/2026_05_20_000002_create_audit_logs_table.php',
    'backend_fresh/app/Services/CloudinaryService.php'
        => $basePath . '/app/Services/CloudinaryService.php',
    'backend_fresh/config/services.php'
        => $basePath . '/config/services.php',
    'backend_fresh/config/firebase.php'
        => $basePath . '/config/firebase.php',
    'backend_fresh/routes/api.php'
        => $basePath . '/routes/api.php',

    // Self-update: deploy.php update dirinya sendiri biar file list selalu sinkron
    'backend_fresh/public/deploy.php'
        => '/home/dszgofcr/public_html/deploy.php',
    'backend_fresh/public/run-migration.php'
        => '/home/dszgofcr/public_html/run-migration.php',
];

$success = 0;
$failed = 0;

foreach ($files as $repoPath => $serverPath) {
    $url = "https://raw.githubusercontent.com/{$repo}/{$branch}/{$repoPath}";
    echo "↓ {$repoPath}\n";

    // Pakai cURL (lebih reliable di shared hosting yg allow_url_fopen=Off)
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'IPL-Deploy/1.0',
        CURLOPT_SSL_VERIFYPEER => false,  // shared hosting kadang cert chain bermasalah
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            'Accept: text/plain',
        ],
    ]);
    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($content === false || $httpCode !== 200 || strlen($content) < 50) {
        echo "  ✗ GAGAL fetch (HTTP $httpCode" . ($curlErr ? " | $curlErr" : "") . ")\n\n";
        $failed++;
        continue;
    }

    // Pastikan folder ada
    $dir = dirname($serverPath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    // Backup file lama (kalau ada)
    if (file_exists($serverPath)) {
        @copy($serverPath, $serverPath . '.bak');
    }

    // Tulis file baru
    $written = @file_put_contents($serverPath, $content);
    if ($written === false) {
        echo "  ✗ GAGAL write ke {$serverPath}\n\n";
        $failed++;
        continue;
    }

    @chmod($serverPath, 0644);
    echo "  ✓ Updated ({$written} bytes)\n\n";
    $success++;
}

echo "=== HASIL ===\n";
echo "✓ Sukses: {$success}\n";
echo "✗ Gagal:  {$failed}\n\n";

// Clear & rebuild cache Laravel
echo "Clear & rebuild Laravel cache...\n";
try {
    chdir($basePath);
    require $basePath . '/vendor/autoload.php';
    $app = require_once $basePath . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->call('config:clear');
    $kernel->call('cache:clear');
    $kernel->call('view:clear');
    $kernel->call('route:clear');
    $kernel->call('config:cache');
    $kernel->call('route:cache');
    echo "✓ Cache cleared & re-cached\n";

    // Clear OPcache jika ada (penting agar PHP load file baru)
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "✓ OPcache reset\n";
    }
} catch (\Throwable $e) {
    echo "✗ Cache error: " . $e->getMessage() . "\n";
}

echo "\n=== SELESAI ===\n";
echo "Backend live di: https://ipl-griya-pesona-madani.my.id\n";
echo "Test: /api/v1/health\n";
