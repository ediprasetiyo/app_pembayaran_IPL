<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nomor_rumah', 10);
            $table->string('blok', 5)->default('E');
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->enum('status_hunian', ['milik', 'sewa', 'kontrak'])->default('milik');
            $table->date('tanggal_pindah')->nullable();
            $table->string('nik', 20)->nullable()->unique();
            $table->text('alamat_asal')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warga');
    }
};
