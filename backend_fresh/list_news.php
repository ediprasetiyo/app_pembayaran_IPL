<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$news = \App\Models\News::all();

echo str_repeat('=', 80) . PHP_EOL;
echo "DAFTAR BERITA DI DATABASE" . PHP_EOL;
echo str_repeat('=', 80) . PHP_EOL;

if ($news->isEmpty()) {
    echo "BELUM ADA BERITA SAMA SEKALI." . PHP_EOL;
} else {
    foreach ($news as $n) {
        echo "ID: {$n->id}" . PHP_EOL;
        echo "Judul: {$n->judul}" . PHP_EOL;
        echo "Kategori: {$n->kategori}" . PHP_EOL;
        echo "is_published: " . ($n->is_published ? 'YES' : 'NO') . PHP_EOL;
        echo "is_pinned: " . ($n->is_pinned ? 'YES' : 'NO') . PHP_EOL;
        echo "published_at: " . ($n->published_at ?? 'NULL') . PHP_EOL;
        echo "created_at: {$n->created_at}" . PHP_EOL;
        echo str_repeat('-', 80) . PHP_EOL;
    }
}

echo "Total: " . $news->count() . " berita" . PHP_EOL;
