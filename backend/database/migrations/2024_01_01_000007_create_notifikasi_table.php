<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('judul');
            $table->text('pesan');
            $table->enum('tipe', ['pembayaran', 'tagihan', 'pengaduan', 'info', 'peringatan'])->default('info');
            $table->boolean('is_read')->default(false);
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tarif_ipl', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->decimal('tarif_bulanan', 12, 2);
            $table->decimal('denda_persen', 5, 2)->default(5);
            $table->unsignedTinyInteger('batas_hari_denda')->default(10);
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
        Schema::dropIfExists('tarif_ipl');
    }
};
