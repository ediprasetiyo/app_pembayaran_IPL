<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'device_type')) {
                $table->string('device_type', 30)->nullable()->after('user_agent');
            }
            if (!Schema::hasColumn('audit_logs', 'browser')) {
                $table->string('browser', 50)->nullable()->after('device_type');
            }
            if (!Schema::hasColumn('audit_logs', 'os')) {
                $table->string('os', 50)->nullable()->after('browser');
            }
            if (!Schema::hasColumn('audit_logs', 'country')) {
                $table->string('country', 60)->nullable()->after('os');
            }
            if (!Schema::hasColumn('audit_logs', 'city')) {
                $table->string('city', 100)->nullable()->after('country');
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            foreach (['device_type', 'browser', 'os', 'country', 'city'] as $col) {
                if (Schema::hasColumn('audit_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
