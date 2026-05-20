<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloks', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique(); // 'A', 'B', 'C', 'E', dst
            $table->string('nama', 100); // 'Blok A', 'Blok E - Tahap 1'
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('jumlah_rumah')->nullable(); // optional, info kapasitas
            $table->timestamps();
        });

        // Seed dengan Blok E (sesuai existing setup user)
        DB::table('bloks')->insert([
            'kode' => 'E',
            'nama' => 'Blok E',
            'deskripsi' => 'Blok awal — perumahan Griya Pesona Madani Tenjo',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tambah blok_id ke users (untuk admin scoping per-blok di masa depan)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'blok_id')) {
                $table->unsignedBigInteger('blok_id')->nullable()->after('role');
                $table->foreign('blok_id')->references('id')->on('bloks')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'blok_id')) {
                $table->dropForeign(['blok_id']);
                $table->dropColumn('blok_id');
            }
        });
        Schema::dropIfExists('bloks');
    }
};
