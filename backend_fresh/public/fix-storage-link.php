<?php
/**
 * Fix storage symlink di public_html agar /storage/* accessible
 *
 * Setup user: public_html/ point ke ipl-backend/public/* (di-copy)
 * Tapi symlink storage tidak ikut → /storage/ 404.
 *
 * Solusi: bikin symlink public_html/storage → ipl-backend/storage/app/public
 *
 * Akses: https://yourdomain.com/fix-storage-link.php?token=ipl-deploy-2026
 */

if (($_GET['token'] ?? '') !== 'ipl-deploy-2026') {
    http_response_code(404);
    exit('Not Found');
}

header('Content-Type: text/plain; charset=utf-8');

$publicHtml = '/home/dszgofcr/public_html';
$storageTarget = '/home/dszgofcr/ipl-backend/storage/app/public';
$symlinkPath = $publicHtml . '/storage';

echo "=== Fix Storage Symlink ===\n\n";
echo "Target: $storageTarget\n";
echo "Link:   $symlinkPath\n\n";

// Cek target exists
if (!is_dir($storageTarget)) {
    echo "❌ Target tidak ada. Bikin directory...\n";
    @mkdir($storageTarget, 0755, true);
    @mkdir($storageTarget . '/avatars', 0755, true);
    @mkdir($storageTarget . '/news', 0755, true);
    @mkdir($storageTarget . '/pengaduan', 0755, true);
    @mkdir($storageTarget . '/settings', 0755, true);
    echo "✓ Dibuat\n\n";
}

// Cek symlink existing
if (file_exists($symlinkPath) || is_link($symlinkPath)) {
    if (is_link($symlinkPath)) {
        $current = readlink($symlinkPath);
        echo "Existing symlink: $current\n";
        if ($current === $storageTarget) {
            echo "✓ Symlink sudah benar — tidak perlu apa-apa.\n";
        } else {
            echo "⚠️  Symlink salah arah. Menghapus...\n";
            @unlink($symlinkPath);
        }
    } else {
        echo "⚠️  Path /storage/ adalah FOLDER, bukan symlink. Renaming ke storage-backup...\n";
        @rename($symlinkPath, $publicHtml . '/storage-backup-' . time());
    }
}

if (!file_exists($symlinkPath)) {
    if (@symlink($storageTarget, $symlinkPath)) {
        echo "✓ Symlink dibuat: $symlinkPath → $storageTarget\n";
    } else {
        echo "❌ Symlink gagal. Coba pakai relative copy strategy...\n";
        // Fallback: kalau symlink tidak diizinkan, kita pakai .htaccess rewrite
        $htaccess = $publicHtml . '/storage.htaccess-rewrite';
        echo "  Cek alternative: pakai .htaccess RewriteRule\n";
    }
}

echo "\n=== Test Akses ===\n";
echo "Test URL: https://ipl-griya-pesona-madani.my.id/storage/avatars/\n";
echo "Buka browser & cek apakah folder listing muncul atau gambar bisa di-load.\n\n";

echo "=== File Permission ===\n";
chmod($storageTarget, 0755);
foreach (['avatars', 'news', 'pengaduan', 'settings'] as $sub) {
    $dir = $storageTarget . '/' . $sub;
    if (is_dir($dir)) {
        chmod($dir, 0755);
        echo "✓ chmod 755: $dir\n";
    }
}

echo "\n=== SELESAI ===\n";
echo "Hapus file ini setelah berhasil.\n";
