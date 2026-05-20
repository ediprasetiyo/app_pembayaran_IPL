<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Get all public settings (no auth needed) — for branding load di awal
     */
    public function publicSettings(): JsonResponse
    {
        $settings = Setting::getPublicAsArray();
        return response()->json([
            'settings' => $settings,
        ]);
    }

    /**
     * Get all settings (grouped) — admin only
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Akses ditolak. Super Admin only.'], 403);
        }

        $settings = Setting::orderBy('group')->orderBy('id')->get();
        $grouped = $settings->groupBy('group')->map(function ($items) {
            return $items->values();
        });

        return response()->json([
            'data' => $grouped,
            'flat' => $settings,
        ]);
    }

    /**
     * Update single setting
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Akses ditolak. Super Admin only.'], 403);
        }

        $request->validate([
            'value' => 'nullable|string',
        ]);

        Setting::set($key, $request->value);

        return response()->json([
            'message' => 'Setting berhasil diperbarui.',
            'key' => $key,
            'value' => $request->value,
        ]);
    }

    /**
     * Update bulk settings
     */
    public function updateBulk(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Akses ditolak. Super Admin only.'], 403);
        }

        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->settings as $key => $value) {
            Setting::set($key, $value);
        }

        AuditLogger::log(
            action: 'settings_updated',
            description: "Settings updated: " . implode(', ', array_keys($request->settings)),
            newValues: $request->settings,
            severity: 'info',
        );

        return response()->json([
            'message' => count($request->settings) . ' setting berhasil diperbarui.',
        ]);
    }

    /**
     * Generate signature untuk Cloudinary direct upload dari browser.
     * Browser upload langsung ke Cloudinary → bypass ModSecurity di shared hosting.
     */
    public function cloudinarySignature(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Akses ditolak. Super Admin only.'], 403);
        }

        $folder = $request->input('folder', 'ipl/logos');
        $cloudName = config('services.cloudinary.cloud_name', env('CLOUDINARY_CLOUD_NAME'));
        $apiKey = config('services.cloudinary.api_key', env('CLOUDINARY_API_KEY'));
        $apiSecret = config('services.cloudinary.api_secret', env('CLOUDINARY_API_SECRET'));

        if (!$cloudName || !$apiKey || !$apiSecret) {
            return response()->json([
                'message' => 'Cloudinary belum di-setup. Set CLOUDINARY_* di .env server.',
            ], 422);
        }

        $timestamp = time();
        // Params yang ikut signature, alphabetical order
        $paramsToSign = "folder={$folder}&timestamp={$timestamp}";
        $signature = sha1($paramsToSign . $apiSecret);

        return response()->json([
            'cloud_name' => $cloudName,
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'folder' => $folder,
        ]);
    }

    /**
     * Simpan URL logo yang sudah ke-upload via direct Cloudinary upload.
     * Body: { url: string }
     */
    public function saveLogoUrl(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Akses ditolak. Super Admin only.'], 403);
        }

        $request->validate([
            'url' => 'required|url|max:500',
        ]);

        Setting::set('logo_url', $request->url);

        AuditLogger::log(
            action: 'logo_updated',
            description: "Logo aplikasi diubah ke: " . $request->url,
            newValues: ['logo_url' => $request->url],
        );

        return response()->json([
            'message' => 'Logo berhasil diupdate.',
            'url' => $request->url,
        ]);
    }

    /**
     * Get notification templates (default + custom).
     */
    public function getNotifTemplates(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $defaults = \App\Services\NotifikasiService::DEFAULT_TEMPLATES;
        $raw = Setting::get('notif_templates');
        $custom = $raw ? json_decode($raw, true) : [];
        $custom = is_array($custom) ? $custom : [];

        $merged = [];
        foreach ($defaults as $key => $tpl) {
            $merged[$key] = [
                'default' => $tpl,
                'custom' => $custom[$key] ?? null,
                'effective' => [
                    'judul' => $custom[$key]['judul'] ?? $tpl['judul'],
                    'pesan' => $custom[$key]['pesan'] ?? $tpl['pesan'],
                ],
            ];
        }

        return response()->json([
            'templates' => $merged,
            'placeholders' => [
                'nama' => 'Nama warga / admin',
                'bulan' => 'Nama bulan tagihan (Januari, Februari, dll)',
                'tahun' => 'Tahun tagihan',
                'nominal' => 'Nominal tagihan (sudah diformat Rp X.XXX)',
                'tanggal' => 'Tanggal jatuh tempo / tanggal terkini',
                'denda' => 'Nominal denda keterlambatan',
                'judul' => 'Judul pengaduan',
                'status' => 'Status pengaduan (sedang diproses, telah diselesaikan, ditolak)',
                'kategori' => 'Kategori pengaduan',
            ],
        ]);
    }

    /**
     * Save notification templates (super_admin only).
     * Body: { templates: { pembayaran_sukses: { judul, pesan }, ... } }
     */
    public function saveNotifTemplates(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'templates' => 'required|array',
        ]);

        $clean = [];
        $defaults = \App\Services\NotifikasiService::DEFAULT_TEMPLATES;
        foreach ($request->templates as $key => $tpl) {
            if (!isset($defaults[$key])) continue;
            $judul = trim($tpl['judul'] ?? '');
            $pesan = trim($tpl['pesan'] ?? '');
            // Skip kalau sama persis dengan default → biar reset ke default
            if ($judul === $defaults[$key]['judul'] && $pesan === $defaults[$key]['pesan']) continue;
            $clean[$key] = ['judul' => $judul, 'pesan' => $pesan];
        }

        Setting::set('notif_templates', json_encode($clean));

        AuditLogger::log(
            action: 'notif_templates_updated',
            description: 'Template notifikasi di-update: ' . count($clean) . ' custom templates',
            newValues: ['count' => count($clean)],
        );

        return response()->json([
            'message' => 'Template notifikasi berhasil disimpan.',
            'custom_count' => count($clean),
        ]);
    }

    /**
     * Update nominal di semua tagihan yang belum_bayar dengan tarif terbaru
     * dari Settings. Berguna setelah admin ubah ipl_amount / kedukaan_amount.
     */
    public function updateTagihanNominal(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Akses ditolak. Super Admin only.'], 403);
        }

        $tarifIpl = (int) (Setting::get('ipl_amount') ?? 65000);
        $tarifKedukaan = (int) (Setting::get('kedukaan_amount') ?? 20000);

        $iplUpdated = \App\Models\IplTagihan::where('jenis', 'ipl_bulanan')
            ->where('status', 'belum_bayar')
            ->update(['nominal' => $tarifIpl]);

        $kedukaanUpdated = \App\Models\IplTagihan::where('jenis', 'kedukaan')
            ->where('status', 'belum_bayar')
            ->update(['nominal' => $tarifKedukaan]);

        return response()->json([
            'message' => "Berhasil update {$iplUpdated} tagihan IPL & {$kedukaanUpdated} tagihan Kedukaan dengan nominal terbaru.",
            'tarif_ipl' => $tarifIpl,
            'tarif_kedukaan' => $tarifKedukaan,
            'ipl_updated' => $iplUpdated,
            'kedukaan_updated' => $kedukaanUpdated,
        ]);
    }

    /**
     * Upload logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Akses ditolak. Super Admin only.'], 403);
        }

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ]);

        $file = $request->file('logo');
        $finalUrl = null;
        $storageType = 'local';

        // Strategi: Cloudinary DULU (kalau configured). Kalau gagal, fallback ke local.
        // Urutan ini penting karena $file->store() akan PINDAH file dari temp →
        // setelahnya $file->getRealPath() tidak valid lagi untuk Cloudinary.
        try {
            $cloudinary = app(CloudinaryService::class);
            if ($cloudinary->isConfigured()) {
                $cloudUrl = $cloudinary->upload($file, 'ipl/logos');
                if ($cloudUrl) {
                    $finalUrl = $cloudUrl;
                    $storageType = 'cloudinary';
                    \Log::info('Logo uploaded ke Cloudinary: ' . $cloudUrl);
                } else {
                    \Log::warning('Cloudinary upload return null, fallback ke local');
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Cloudinary logo upload gagal (fallback ke local): ' . $e->getMessage());
        }

        // Fallback ke local kalau Cloudinary gagal / belum configured
        if (!$finalUrl) {
            try {
                $localPath = $file->store('settings', 'public');
                $finalUrl = Storage::url($localPath);
                $storageType = 'local';
                \Log::info('Logo uploaded ke local: ' . $finalUrl);
            } catch (\Throwable $e) {
                \Log::error('Local logo upload gagal: ' . $e->getMessage());
                return response()->json([
                    'message' => 'Gagal simpan logo: ' . $e->getMessage(),
                ], 500);
            }
        }

        // Save to settings
        Setting::set('logo_url', $finalUrl);

        return response()->json([
            'message' => 'Logo berhasil diupload.',
            'url' => $finalUrl,
            'storage' => $storageType,
        ]);
    }
}
