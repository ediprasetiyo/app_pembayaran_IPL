<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // siapa yang melakukan
            $table->string('user_name', 100)->nullable(); // snapshot nama (kalau user di-hapus)
            $table->string('user_role', 30)->nullable(); // snapshot role

            // What happened
            $table->string('action', 50); // 'created', 'updated', 'deleted', 'login', 'login_failed', 'payment_success', 'settings_changed', dll
            $table->string('model_type', 100)->nullable(); // 'App\Models\Warga', etc
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('description', 500)->nullable(); // human-readable description

            // Snapshot before/after (JSON)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request metadata
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // Severity (untuk filter)
            $table->enum('severity', ['info', 'warning', 'error', 'critical'])->default('info');

            $table->timestamp('created_at')->useCurrent();

            // Indexes untuk query cepat
            $table->index('user_id');
            $table->index('action');
            $table->index('model_type');
            $table->index(['model_type', 'model_id']);
            $table->index('severity');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
