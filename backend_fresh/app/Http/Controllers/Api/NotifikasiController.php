<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class NotifikasiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifikasi = Notifikasi::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        $unreadCount = Notifikasi::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'notifikasi' => $notifikasi,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(Request $request, Notifikasi $notifikasi): JsonResponse
    {
        if ($notifikasi->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $notifikasi->markAsRead();

        return response()->json(['message' => 'Notifikasi ditandai sudah dibaca.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notifikasi::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }

    public function destroy(Request $request, Notifikasi $notifikasi): JsonResponse
    {
        if ($notifikasi->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $notifikasi->delete();

        return response()->json(['message' => 'Notifikasi dihapus.']);
    }

    /**
     * Test push notification — super_admin only.
     * Body: { user_id?: int, judul?: string, pesan?: string }
     * Kalau user_id kosong → kirim ke diri sendiri.
     */
    public function testPush(Request $request): JsonResponse
    {
        $caller = $request->user();
        if ($caller->role !== 'super_admin') {
            return response()->json(['message' => 'Hanya super_admin.'], 403);
        }

        $targetUserId = (int) ($request->input('user_id') ?? $caller->id);
        $user = User::find($targetUserId);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan.'], 404);
        }
        if (!$user->fcm_token) {
            return response()->json([
                'message' => 'User belum punya fcm_token. Pastikan user login lewat mobile app dulu.',
                'user' => ['id' => $user->id, 'name' => $user->name],
            ], 422);
        }

        $judul = $request->input('judul') ?? '🔔 Test Notifikasi IPL';
        $pesan = $request->input('pesan') ?? 'Halo ' . $user->name . '! Ini test push notification dari backend. Kalau muncul + bunyi, FCM sudah jalan ✅';

        // Kirim FCM dulu (yang penting). Simpan ke tabel notifikasi
        // dilakukan terpisah di try-catch sendiri biar DB error tidak
        // memask FCM yang sebenarnya sukses.
        try {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create($judul, $pesan))
                ->withData([
                    'type' => 'test',
                    'sent_at' => now()->toIso8601String(),
                ]);

            $fcmResponse = Firebase::messaging()->send($message);
        } catch (\Throwable $e) {
            \Log::error('Test FCM error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal kirim FCM: ' . $e->getMessage(),
                'hint' => 'Cek FIREBASE_CREDENTIALS di .env benar path-nya & file ada.',
            ], 500);
        }

        // Optional: simpan log notifikasi (best-effort, tidak fatal kalau gagal).
        // Pakai tipe enum yang valid agar tidak kena truncated warning.
        try {
            Notifikasi::create([
                'user_id' => $user->id,
                'judul' => $judul,
                'pesan' => $pesan,
                'tipe' => 'peringatan', // valid enum: pembayaran, tagihan, peringatan, pengaduan
                'data' => ['from' => $caller->name, 'test' => true],
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Test FCM: simpan log gagal (tidak fatal): ' . $e->getMessage());
        }

        return response()->json([
            'message' => '✅ Push terkirim! Cek HP ' . $user->name . ' — notif harus muncul + bunyi.',
            'target_user' => ['id' => $user->id, 'name' => $user->name],
            'fcm_response' => $fcmResponse,
        ]);
    }
}
