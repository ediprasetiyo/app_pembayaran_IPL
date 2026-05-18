<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Nomor telepon atau password salah.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Akun Anda tidak aktif. Hubungi admin.',
            ], 403);
        }

        if ($request->fcm_token) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'warga',
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->formatUser($user),
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('warga.anggotaKeluarga');

        return response()->json(['user' => $this->formatUser($user)]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'language' => 'sometimes|in:id,en',
            'fcm_token' => 'sometimes|string',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // Upload avatar (jika ada)
        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            try {
                // Hapus avatar lama
                if ($user->avatar && str_starts_with($user->avatar, '/storage/')) {
                    $oldPath = str_replace('/storage/', '', $user->avatar);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                }
                $path = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = \Illuminate\Support\Facades\Storage::url($path);
            } catch (\Throwable $e) {
                \Log::warning('Avatar upload failed: ' . $e->getMessage());
            }
        }

        $user->fill($request->only(['name', 'email', 'language', 'fcm_token']));
        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Password lama salah.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|string']);

        $user = User::where('phone', $request->phone)->first();

        if (! $user) {
            return response()->json(['message' => 'Nomor telepon tidak ditemukan.'], 404);
        }

        $adminPhone = config('app.admin_phone', env('ADMIN_PHONE', '628000000000'));
        $waMessage = urlencode("Halo Admin, saya {$user->name} dengan nomor {$user->phone} lupa password aplikasi IPL. Mohon bantuannya.");
        $waUrl = "https://wa.me/{$adminPhone}?text={$waMessage}";

        return response()->json([
            'message' => 'Silakan hubungi admin melalui WhatsApp.',
            'wa_url' => $waUrl,
            'admin_phone' => $adminPhone,
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role,
            'language' => $user->language,
            'avatar' => $user->avatar,
            'warga' => $user->warga,
        ];
    }
}
