<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use App\Services\AuditLogger;
use App\Services\CloudinaryService;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PengaduanController extends Controller
{
    public function __construct(private NotifikasiService $notifikasiService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Pengaduan::with('warga.user')->orderByDesc('created_at');

        // Admin & Super Admin lihat semua, warga hanya pengaduan sendiri
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            if (!$user->warga) {
                return response()->json(['data' => [], 'total' => 0]);
            }
            $query->where('warga_id', $user->warga->id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(10));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'judul' => 'required|string|max:200',
            'deskripsi' => 'required|string',
            'kategori' => 'required|in:infrastruktur,kebersihan,keamanan,fasilitas,sosial,lainnya',
            'foto' => 'nullable|array|max:1',
            'foto.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $warga = $request->user()->warga;

        if (! $warga) {
            return response()->json(['message' => 'Data warga tidak ditemukan.'], 404);
        }

        $fotos = [];
        if ($request->hasFile('foto')) {
            $cloudinary = app(CloudinaryService::class);
            foreach ($request->file('foto') as $file) {
                $url = null;
                // Try Cloudinary dulu (wrap try/catch supaya tidak crash kalau gagal)
                if ($cloudinary->isConfigured()) {
                    try {
                        $url = $cloudinary->upload($file, 'ipl/pengaduan');
                    } catch (\Throwable $e) {
                        \Log::warning('Cloudinary upload pengaduan gagal: ' . $e->getMessage());
                        $url = null;
                    }
                }
                // Fallback ke local storage kalau Cloudinary gagal / belum configured
                if (!$url) {
                    try {
                        $path = $file->store('pengaduan', 'public');
                        $url = Storage::url($path);
                    } catch (\Throwable $e) {
                        \Log::error('Local storage upload pengaduan gagal: ' . $e->getMessage());
                        return response()->json([
                            'message' => 'Gagal upload foto: ' . $e->getMessage(),
                        ], 500);
                    }
                }
                $fotos[] = $url;
            }
        }

        $pengaduan = Pengaduan::create([
            'warga_id' => $warga->id,
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'kategori' => $request->kategori,
            'foto' => $fotos ?: null,
            'status' => 'baru',
        ]);

        $this->notifikasiService->notifikasiAdminPengaduanBaru($pengaduan);

        return response()->json([
            'message' => 'Pengaduan berhasil dikirim.',
            'pengaduan' => $pengaduan,
        ], 201);
    }

    public function show(Request $request, Pengaduan $pengaduan): JsonResponse
    {
        $warga = $request->user()->warga;

        if ((int) $pengaduan->warga_id !== (int) ($warga?->id ?? 0) && ! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        return response()->json(['pengaduan' => $pengaduan->load('handler')]);
    }

    public function update(Request $request, Pengaduan $pengaduan): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $request->validate([
            'status' => 'required|in:baru,diproses,selesai,ditolak',
            'keterangan_admin' => 'nullable|string',
            'prioritas' => 'nullable|in:rendah,sedang,tinggi',
        ]);

        $updateData = [
            'status' => $request->status,
            'keterangan_admin' => $request->keterangan_admin,
            'ditangani_oleh' => $request->user()->id,
        ];

        if ($request->status === 'selesai') {
            $updateData['tanggal_selesai'] = now();
        }

        if ($request->prioritas) {
            $updateData['prioritas'] = $request->prioritas;
        }

        $pengaduan->update($updateData);

        $this->notifikasiService->notifikasiWargaStatusPengaduan($pengaduan);

        return response()->json([
            'message' => 'Status pengaduan diperbarui.',
            'pengaduan' => $pengaduan->fresh(),
        ]);
    }

    /**
     * Hapus pengaduan (admin/super_admin only) + hapus foto terkait.
     */
    public function destroy(Request $request, Pengaduan $pengaduan): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'Hanya admin yang bisa hapus pengaduan.'], 403);
        }

        // Hapus foto terkait dari Cloudinary atau local storage
        if (is_array($pengaduan->foto) && count($pengaduan->foto) > 0) {
            $cloudinary = app(CloudinaryService::class);
            foreach ($pengaduan->foto as $fotoUrl) {
                try {
                    if (str_contains($fotoUrl, 'cloudinary.com')) {
                        $cloudinary->deleteByUrl($fotoUrl);
                    } elseif (str_starts_with($fotoUrl, '/storage/')) {
                        $path = str_replace('/storage/', '', $fotoUrl);
                        Storage::disk('public')->delete($path);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Gagal hapus foto pengaduan: ' . $e->getMessage());
                }
            }
        }

        $oldData = $pengaduan->toArray();
        $pengaduan->delete();

        AuditLogger::log(
            action: 'pengaduan_deleted',
            description: "Pengaduan dihapus: '{$oldData['judul']}' oleh {$user->name}",
            oldValues: $oldData,
            severity: 'warning',
        );

        return response()->json([
            'message' => 'Pengaduan berhasil dihapus.',
        ]);
    }
}
