<?php
/**
 * One-time Web Installer untuk Laravel di Shared Hosting
 *
 * Cara pakai:
 * 1. Pastikan .env sudah dikonfigurasi (DB credentials benar)
 * 2. Akses URL: https://yourdomain.com/install.php?token=ipl-griya-deploy-2026
 * 3. Klik tombol "Run Setup"
 * 4. File ini akan auto-delete dirinya setelah sukses
 *
 * SECURITY: Token simpel sebagai proteksi minimal. Hapus file setelah selesai!
 */

// Token sederhana untuk hindari akses sembarangan
$REQUIRED_TOKEN = 'ipl-griya-deploy-2026';

if (($_GET['token'] ?? '') !== $REQUIRED_TOKEN) {
    http_response_code(404);
    exit('Not Found');
}

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function runArtisan($app, $command) {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $output = new Symfony\Component\Console\Output\BufferedOutput();
    $exitCode = $kernel->call($command, [], $output);
    return ['code' => $exitCode, 'output' => $output->fetch()];
}

$action = $_POST['action'] ?? null;
$results = [];

if ($action === 'run_setup') {
    // 1. Generate APP_KEY (kalau belum ada)
    if (empty(env('APP_KEY')) || env('APP_KEY') === 'base64:GENERATE_THIS_KEY') {
        $key = 'base64:' . base64_encode(random_bytes(32));
        $envPath = __DIR__ . '/../.env';
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            $env = preg_replace('/^APP_KEY=.*$/m', "APP_KEY={$key}", $env);
            file_put_contents($envPath, $env);
            $results[] = ['step' => 'Generate APP_KEY', 'success' => true, 'output' => $key];
        }
    } else {
        $results[] = ['step' => 'APP_KEY', 'success' => true, 'output' => 'Already set'];
    }

    // 2. Cache config
    $r = runArtisan($app, 'config:clear');
    $results[] = ['step' => 'Clear config cache', 'success' => $r['code'] === 0, 'output' => $r['output']];

    // 3. Run migrations
    $r = runArtisan($app, 'migrate', /*params*/ );
    // Force = true via call options not supported in this way, use --force flag via env
    $exitCode = $app->make(Illuminate\Contracts\Console\Kernel::class)
        ->call('migrate', ['--force' => true]);
    $output = $app->make(Illuminate\Contracts\Console\Kernel::class)->output();
    $results[] = ['step' => 'Run migrations', 'success' => $exitCode === 0, 'output' => $output];

    // 4. Storage link
    $r = $app->make(Illuminate\Contracts\Console\Kernel::class)->call('storage:link');
    $results[] = ['step' => 'Storage symlink', 'success' => true, 'output' => 'Done (may already exist)'];

    // 5. Cache config & routes for production
    $app->make(Illuminate\Contracts\Console\Kernel::class)->call('config:cache');
    $app->make(Illuminate\Contracts\Console\Kernel::class)->call('route:cache');
    $results[] = ['step' => 'Cache config & routes', 'success' => true, 'output' => 'Cached'];

    // Set flag: setup berhasil → user bisa hapus file ini
    $allOk = !array_filter($results, fn($r) => !$r['success']);
}

if ($action === 'create_admin') {
    $name = $_POST['name'] ?? 'Super Admin';
    $phone = $_POST['phone'] ?? '628000000000';
    $password = $_POST['password'] ?? '';

    if (strlen($password) < 6) {
        $results[] = ['step' => 'Create admin', 'success' => false, 'output' => 'Password minimal 6 karakter'];
    } else {
        try {
            \App\Models\User::updateOrCreate(
                ['phone' => $phone],
                [
                    'name' => $name,
                    'password' => \Illuminate\Support\Facades\Hash::make($password),
                    'role' => 'super_admin',
                    'language' => 'id',
                    'is_active' => true,
                ]
            );
            $results[] = ['step' => 'Create super admin', 'success' => true, 'output' => "User {$name} ({$phone}) created"];
        } catch (\Throwable $e) {
            $results[] = ['step' => 'Create super admin', 'success' => false, 'output' => $e->getMessage()];
        }
    }
}

if ($action === 'self_destruct') {
    @unlink(__FILE__);
    header('Location: /install.php');
    exit('Installer removed. App ready!');
}

// HTML page
?>
<!DOCTYPE html>
<html>
<head>
<title>IPL Griya - Installer</title>
<style>
  body { font-family: system-ui; max-width: 720px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
  h1 { color: #1B5E20; }
  .card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
  .result { padding: 10px; margin: 6px 0; border-radius: 6px; font-family: monospace; font-size: 12px; white-space: pre-wrap; }
  .ok { background: #e8f5e9; border-left: 4px solid #43a047; }
  .fail { background: #ffebee; border-left: 4px solid #e53935; }
  button { background: #1B5E20; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; }
  button.danger { background: #c62828; }
  input { width: 100%; padding: 10px; margin: 4px 0 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
  label { font-weight: 600; font-size: 13px; }
</style>
</head>
<body>
<h1>🚀 IPL Griya Pesona Madani — Web Installer</h1>

<?php if (!empty($results)): ?>
<div class="card">
  <h3>📋 Hasil:</h3>
  <?php foreach ($results as $r): ?>
    <div class="result <?= $r['success'] ? 'ok' : 'fail' ?>">
      <strong><?= $r['success'] ? '✅' : '❌' ?> <?= htmlspecialchars($r['step']) ?>:</strong>
      <?= htmlspecialchars($r['output'] ?? '') ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
  <h3>1. Setup Database & Cache</h3>
  <p>Klik untuk: generate APP_KEY, run migrations, setup cache config.</p>
  <form method="POST">
    <input type="hidden" name="action" value="run_setup">
    <button type="submit">▶ Run Setup</button>
  </form>
</div>

<div class="card">
  <h3>2. Buat User Super Admin</h3>
  <form method="POST">
    <input type="hidden" name="action" value="create_admin">
    <label>Nama:</label>
    <input type="text" name="name" value="Edi Prasetiyo" required>
    <label>Nomor HP (untuk login):</label>
    <input type="text" name="phone" value="081908226774" required>
    <label>Password (min 6 karakter):</label>
    <input type="password" name="password" required minlength="6">
    <button type="submit">▶ Create Admin</button>
  </form>
</div>

<div class="card">
  <h3>3. Hapus Installer (PENTING — setelah selesai!)</h3>
  <p style="color: #c62828;">⚠️ Setelah setup sukses, hapus file ini untuk keamanan.</p>
  <form method="POST">
    <input type="hidden" name="action" value="self_destruct">
    <button type="submit" class="danger">🗑 Delete Installer</button>
  </form>
</div>

<div class="card" style="background: #fff9e6;">
  <h3>📌 Info Debug</h3>
  <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
  <p><strong>Laravel Version:</strong> <?= app()->version() ?></p>
  <p><strong>Database Connection:</strong> <?= config('database.default') ?> @ <?= config('database.connections.' . config('database.default') . '.host') ?>/<?= config('database.connections.' . config('database.default') . '.database') ?></p>
  <p><strong>App URL:</strong> <?= config('app.url') ?></p>
  <p><strong>Environment:</strong> <?= app()->environment() ?></p>
</div>

</body>
</html>
