<?php
/**
 * Manual trigger Laravel scheduler / commands
 *
 * USAGE:
 *   ?token=ipl-deploy-2026                     → run scheduler (all scheduled tasks)
 *   ?token=ipl-deploy-2026&cmd=reminder        → run ReminderTagihan langsung
 *   ?token=ipl-deploy-2026&cmd=terlambat       → run CheckTagihanTerlambat
 *
 * Untuk Cron Job di cPanel:
 *   * * * * * curl -s "https://ipl-griya-pesona-madani.my.id/run-scheduler.php?token=ipl-deploy-2026" > /dev/null
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
$cmd = $_GET['cmd'] ?? 'schedule';

try {
    switch ($cmd) {
        case 'reminder':
            echo "=== Run ReminderTagihan ===\n";
            $exit = $kernel->call('ipl:reminder-tagihan');
            echo $kernel->output();
            echo "Exit: $exit\n";
            break;
        case 'terlambat':
            echo "=== Run CheckTagihanTerlambat ===\n";
            $exit = $kernel->call('ipl:check-terlambat');
            echo $kernel->output();
            echo "Exit: $exit\n";
            break;
        case 'generate':
            echo "=== Run GenerateTagihanBulanan ===\n";
            $exit = $kernel->call('ipl:generate-tagihan');
            echo $kernel->output();
            echo "Exit: $exit\n";
            break;
        default:
            echo "=== Run schedule:run ===\n";
            $exit = $kernel->call('schedule:run');
            echo $kernel->output();
            echo "Exit: $exit\n";
            break;
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
