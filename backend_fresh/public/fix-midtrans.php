<?php
/**
 * One-shot script: update MidtransService.php DAN deploy.php dari GitHub.
 * Pakai ini sekali, lalu deploy.php yang baru akan handle file lain.
 */
if (($_GET['token'] ?? '') !== 'ipl-deploy-2026') {
    http_response_code(404);
    exit('Not Found');
}
header('Content-Type: text/plain; charset=utf-8');

$repo = 'ediprasetiyo/app_pembayaran_IPL';
$branch = 'main';
$basePath = '/home/dszgofcr/ipl-backend';
$publicHtml = '/home/dszgofcr/public_html';

$files = [
    'backend_fresh/app/Services/MidtransService.php'  => $basePath . '/app/Services/MidtransService.php',
    'backend_fresh/app/Services/NotifikasiService.php' => $basePath . '/app/Services/NotifikasiService.php',
    'backend_fresh/app/Models/Pembayaran.php'          => $basePath . '/app/Models/Pembayaran.php',
    'backend_fresh/app/Models/IplTagihan.php'          => $basePath . '/app/Models/IplTagihan.php',
    'backend_fresh/app/Models/Notifikasi.php'          => $basePath . '/app/Models/Notifikasi.php',
    'backend_fresh/app/Console/Kernel.php'             => $basePath . '/app/Console/Kernel.php',
    'backend_fresh/app/Console/Commands/ReminderTagihan.php'        => $basePath . '/app/Console/Commands/ReminderTagihan.php',
    'backend_fresh/app/Console/Commands/CheckTagihanTerlambat.php'  => $basePath . '/app/Console/Commands/CheckTagihanTerlambat.php',
    'backend_fresh/app/Console/Commands/GenerateTagihanBulanan.php' => $basePath . '/app/Console/Commands/GenerateTagihanBulanan.php',
    'backend_fresh/app/Http/Controllers/Api/NotifikasiController.php' => $basePath . '/app/Http/Controllers/Api/NotifikasiController.php',
    // Update deploy.php juga supaya next time daftar lengkap
    'backend_fresh/public/deploy.php'        => $publicHtml . '/deploy.php',
    'backend_fresh/public/sync-payment.php'  => $publicHtml . '/sync-payment.php',
    'backend_fresh/public/run-scheduler.php' => $publicHtml . '/run-scheduler.php',
];

echo "=== FIX: Update file yang missing dari deploy.php lama ===\n\n";
$ok = 0; $fail = 0;
foreach ($files as $repoPath => $serverPath) {
    $url = "https://raw.githubusercontent.com/{$repo}/{$branch}/{$repoPath}";
    echo "↓ {$repoPath}\n";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'IPL-Fix/1.0',
    ]);
    $content = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($content === false || $code !== 200 || strlen($content) < 50) {
        echo "  ✗ GAGAL fetch (HTTP $code)\n\n";
        $fail++;
        continue;
    }
    $dir = dirname($serverPath);
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    if (file_exists($serverPath)) { @copy($serverPath, $serverPath . '.bak'); }
    $w = @file_put_contents($serverPath, $content);
    if ($w === false) {
        echo "  ✗ GAGAL write $serverPath\n\n";
        $fail++;
        continue;
    }
    @chmod($serverPath, 0644);
    echo "  ✓ Updated ({$w} bytes)\n\n";
    $ok++;
}

echo "=== HASIL: $ok sukses, $fail gagal ===\n\n";

// Clear cache + OPcache reset
try {
    require $basePath . '/vendor/autoload.php';
    $app = require_once $basePath . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    foreach (['config:clear','cache:clear','view:clear','route:clear','config:cache','route:cache'] as $cmd) {
        $kernel->call($cmd);
    }
    echo "✓ Laravel cache cleared & re-cached\n";
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "✓ OPcache reset\n";
    }
} catch (\Throwable $e) {
    echo "✗ Cache error: " . $e->getMessage() . "\n";
}

echo "\n=== SELESAI ===\n";
echo "Next: buka sync-payment.php?token=ipl-deploy-2026&id=1\n";
