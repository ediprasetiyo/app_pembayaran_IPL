<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\KasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengeluaranController extends Controller
{
    private const KATEGORI = [
        'sampah' => '🗑️ Iuran Sampah',
        'keamanan' => '🛡️ Keamanan / Satpam',
        'kebersihan' => '🧹 Kebersihan Lingkungan',
        'perawatan' => '🔧 Perawatan & Perbaikan',
        'listrik' => '💡 Listrik Fasum',
        'air' => '💧 Air',
        'acara' => '🎉 Acara / Kegiatan RT',
        'kedukaan_warga' => '🕊️ Santunan Warga Berduka',
        'admin' => '📋 Administrasi',
        'lainnya' => '📦 Lainnya',
    ];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin', 'admin', 'bendahara'])) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $query = Pengeluaran::with(['pencatat', 'warga.user'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        if ($request->sumber_dana) {
            $query->where('sumber_dana', $request->sumber_dana);
        }

        if ($request->kategori) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->from_date) {
            $query->whereDate('tanggal', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('tanggal', '<=', $request->to_date);
        }

        return response()->json([
            'data' => $query->paginate(20),
            'kategori_list' => self::KATEGORI,
            'summary' => KasService::summary(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin', 'admin', 'bendahara'])) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $validated = $request->validate([
            'kategori' => 'required|string|max:50',
            'keterangan' => 'nullable|string|max:500',
            'nominal' => 'required|numeric|min:1',
            'sumber_dana' => 'required|in:ipl,kedukaan',
            'tanggal' => 'required|date',
            'bukti_url' => 'nullable|url|max:500',
            'warga_id' => 'nullable|exists:warga,id',
            'catatan' => 'nullable|string|max:500',
        ]);

        $validated['created_by'] = $user->id;
        $pengeluaran = Pengeluaran::create($validated);

        AuditLogger::log(
            action: 'pengeluaran_created',
            description: "Pengeluaran {$pengeluaran->sumber_dana}: {$pengeluaran->kategori} Rp " . number_format($pengeluaran->nominal, 0, ',', '.'),
            model: $pengeluaran,
            newValues: $pengeluaran->toArray(),
        );

        // Auto-reset kedukaan kalau saldo habis
        $didReset = false;
        if ($pengeluaran->sumber_dana === 'kedukaan') {
            $didReset = KasService::autoResetKedukaanKalauHabis();
        }

        return response()->json([
            'message' => 'Pengeluaran berhasil dicatat.' . ($didReset ? ' Saldo kedukaan habis — warga akan dapat tagihan baru bulan depan.' : ''),
            'data' => $pengeluaran->load(['pencatat', 'warga.user']),
            'kedukaan_reset' => $didReset,
            'summary' => KasService::summary(),
        ], 201);
    }

    public function update(Request $request, Pengeluaran $pengeluaran): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role, ['super_admin', 'admin', 'bendahara'])) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $validated = $request->validate([
            'kategori' => 'sometimes|string|max:50',
            'keterangan' => 'nullable|string|max:500',
            'nominal' => 'sometimes|numeric|min:1',
            'sumber_dana' => 'sometimes|in:ipl,kedukaan',
            'tanggal' => 'sometimes|date',
            'bukti_url' => 'nullable|url|max:500',
            'warga_id' => 'nullable|exists:warga,id',
            'catatan' => 'nullable|string|max:500',
        ]);

        $old = $pengeluaran->toArray();
        $pengeluaran->update($validated);

        AuditLogger::log(
            action: 'pengeluaran_updated',
            description: "Pengeluaran diubah: {$pengeluaran->kategori} Rp " . number_format($pengeluaran->nominal, 0, ',', '.'),
            model: $pengeluaran,
            oldValues: $old,
            newValues: $pengeluaran->toArray(),
        );

        return response()->json([
            'message' => 'Pengeluaran berhasil diperbarui.',
            'data' => $pengeluaran->fresh(['pencatat', 'warga.user']),
            'summary' => KasService::summary(),
        ]);
    }

    public function destroy(Request $request, Pengeluaran $pengeluaran): JsonResponse
    {
        $user = $request->user();
        // Hapus pengeluaran: hanya super_admin (audit trail penting)
        if ($user->role !== 'super_admin') {
            return response()->json(['message' => 'Hanya Super Admin yang bisa hapus pengeluaran.'], 403);
        }

        $old = $pengeluaran->toArray();
        $pengeluaran->delete();

        AuditLogger::log(
            action: 'pengeluaran_deleted',
            description: "Pengeluaran dihapus: {$old['kategori']} Rp " . number_format($old['nominal'], 0, ',', '.'),
            oldValues: $old,
            severity: 'warning',
        );

        return response()->json([
            'message' => 'Pengeluaran dihapus.',
            'summary' => KasService::summary(),
        ]);
    }

    /**
     * Super admin only: adjust saldo manual (untuk koreksi).
     * Body: { sumber_dana: 'ipl'|'kedukaan', adjustment: int (signed), catatan }
     */
    public function adjustSaldo(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'super_admin') {
            return response()->json(['message' => 'Hanya Super Admin yang bisa edit saldo manual.'], 403);
        }

        $request->validate([
            'sumber_dana' => 'required|in:ipl,kedukaan',
            'adjustment' => 'required|integer',
            'catatan' => 'nullable|string|max:500',
        ]);

        $key = $request->sumber_dana === 'ipl' ? 'saldo_ipl_adjustment' : 'saldo_kedukaan_adjustment';
        Setting::set($key, (string) $request->adjustment);

        AuditLogger::log(
            action: 'saldo_adjusted',
            description: "Adjust saldo {$request->sumber_dana}: " . ($request->adjustment >= 0 ? '+' : '') . "Rp " . number_format($request->adjustment, 0, ',', '.') . ($request->catatan ? " | {$request->catatan}" : ''),
            newValues: ['key' => $key, 'value' => $request->adjustment],
            severity: 'warning',
        );

        return response()->json([
            'message' => 'Saldo berhasil di-adjust.',
            'summary' => KasService::summary(),
        ]);
    }

    /**
     * Cek summary kas saja (untuk dashboard).
     */
    public function summary(Request $request): JsonResponse
    {
        return response()->json(KasService::summary());
    }
}
