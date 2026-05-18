<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->text('ringkasan')->nullable();
            $table->longText('konten');
            $table->string('gambar')->nullable();
            $table->enum('kategori', ['pengumuman', 'kegiatan', 'informasi', 'darurat'])->default('informasi');
            $table->boolean('is_published')->default(true);
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('is_published');
            $table->index('kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
