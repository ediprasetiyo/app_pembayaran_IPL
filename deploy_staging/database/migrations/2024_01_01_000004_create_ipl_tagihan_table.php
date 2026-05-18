<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipl_tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warga_id')->constrained('warga')->onDelete('cascade');
            $table->enum('jenis', ['ipl_bulanan', 'kedukaan'])->default('ipl_bulanan');
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->decimal('nominal', 12, 2);
            $table->decimal('denda', 12, 2)->default(0);
            $table->enum('status', ['belum_bayar', 'sudah_bayar', 'terlambat'])->default('belum_bayar');
            $table->date('jatuh_tempo');
            $table->date('tanggal_bayar')->nullable();
            $table->date('reminder_terakhir')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['warga_id', 'jenis', 'bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipl_tagihan');
    }
};
