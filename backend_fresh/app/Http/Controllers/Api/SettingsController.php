<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
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
            $path = $request->file('logo')->store('settings', 'public');
            $url = Storage::url($path);

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
