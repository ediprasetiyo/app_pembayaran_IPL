<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->string('kategori', 50); // 'sampah', 'keamanan', 'kebersihan', 'perawatan', 'kedukaan_warga', 'lainnya'
            $table->text('keterangan')->nullable();
            $table->decimal('nominal', 15, 2);
            $table->enum('sumber_dana', ['ipl', 'kedukaan'])->default('ipl');
            $table->date('tanggal');
            $table->string('bukti_url', 500)->nullable(); // optional foto/dokumen bukti
            $table->unsignedBigInteger('created_by')->nullable(); // user_id bendahara/admin yang catat
            $table->unsignedBigInteger('warga_id')->nullable(); // kalau pengeluaran untuk warga tertentu (misal kedukaan)
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('sumber_dana');
            $table->index('tanggal');
            $table->index('warga_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran');
    }
};
