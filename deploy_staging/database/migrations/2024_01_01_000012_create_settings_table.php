<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('text'); // text, color, image, json
            $table->string('group', 50)->default('general'); // general, theme, branding
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // dapat diakses tanpa auth
            $table->timestamps();
        });

        // Seed default settings
        $now = now();
        DB::table('settings')->insert([
            // === Branding ===
            ['key' => 'app_name', 'value' => 'IPL Griya Pesona Madani', 'type' => 'text', 'group' => 'branding', 'label' => 'Nama Aplikasi', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'brand_title', 'value' => 'Griya Pesona', 'type' => 'text', 'group' => 'branding', 'label' => 'Judul Sidebar (Baris 1)', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'brand_subtitle', 'value' => 'Madani Tenjo', 'type' => 'text', 'group' => 'branding', 'label' => 'Sub-judul Sidebar (Baris 2)', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'logo_url', 'value' => '/logo.png', 'type' => 'image', 'group' => 'branding', 'label' => 'Logo Aplikasi', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'login_subtitle', 'value' => 'Back Office Admin Panel', 'type' => 'text', 'group' => 'branding', 'label' => 'Subtitle di Login', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'footer_text', 'value' => 'Perumahan Griya Pesona Madani Tenjo - Blok E', 'type' => 'text', 'group' => 'branding', 'label' => 'Footer Login', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],

            // === Theme Colors ===
            ['key' => 'theme_primary', 'value' => '#388E3C', 'type' => 'color', 'group' => 'theme', 'label' => 'Warna Utama (Primary)', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'theme_primary_dark', 'value' => '#1B5E20', 'type' => 'color', 'group' => 'theme', 'label' => 'Warna Primary Gelap (Sidebar)', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'theme_primary_light', 'value' => '#E8F5E9', 'type' => 'color', 'group' => 'theme', 'label' => 'Warna Primary Terang', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'theme_accent', 'value' => '#FFC107', 'type' => 'color', 'group' => 'theme', 'label' => 'Warna Aksen', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],

            // === Page Activity / Info ===
            ['key' => 'page_dashboard_title', 'value' => 'Dashboard', 'type' => 'text', 'group' => 'pages', 'label' => 'Judul Halaman Dashboard', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'page_warga_title', 'value' => 'Data Warga', 'type' => 'text', 'group' => 'pages', 'label' => 'Judul Halaman Warga', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'page_tagihan_title', 'value' => 'Tagihan IPL', 'type' => 'text', 'group' => 'pages', 'label' => 'Judul Halaman Tagihan', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'page_pembayaran_title', 'value' => 'Pembayaran', 'type' => 'text', 'group' => 'pages', 'label' => 'Judul Halaman Pembayaran', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'page_pengaduan_title', 'value' => 'Pengaduan', 'type' => 'text', 'group' => 'pages', 'label' => 'Judul Halaman Pengaduan', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'page_berita_title', 'value' => 'Berita', 'type' => 'text', 'group' => 'pages', 'label' => 'Judul Halaman Berita', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'page_laporan_title', 'value' => 'Laporan', 'type' => 'text', 'group' => 'pages', 'label' => 'Judul Halaman Laporan', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],

            // === Bisnis ===
            ['key' => 'ipl_amount', 'value' => '65000', 'type' => 'text', 'group' => 'general', 'label' => 'Nominal IPL Bulanan (Rp)', 'is_public' => false, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'kedukaan_amount', 'value' => '20000', 'type' => 'text', 'group' => 'general', 'label' => 'Nominal Uang Kedukaan (Rp)', 'is_public' => false, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'admin_whatsapp', 'value' => '082115525327', 'type' => 'text', 'group' => 'general', 'label' => 'Nomor WhatsApp Admin', 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
