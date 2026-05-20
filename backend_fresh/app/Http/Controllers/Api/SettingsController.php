<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
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

        return response()->json([
            'message' => count($request->settings) . ' setting berhasil diperbarui.',
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

        try {
            $cloudinary = app(CloudinaryService::class);
            $url = null;
            if ($cloudinary->isConfigured()) {
                $url = $cloudinary->upload($request->file('logo'), 'ipl/logos');
            }
            if (!$url) {
                $path = $request->file('logo')->store('settings', 'public');
                $url = Storage::url($path);
            }

            // Save to settings
            Setting::set('logo_url', $url);

            return response()->json([
                'message' => 'Logo berhasil diupload.',
                'url' => $url,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal upload: ' . $e->getMessage(),
            ], 500);
        }
    }
}
