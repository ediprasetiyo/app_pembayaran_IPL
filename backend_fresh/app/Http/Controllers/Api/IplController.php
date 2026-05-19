<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IplTagihan;
use App\Models\Notifikasi;
use App\Models\Pembayaran;
use App\Models\TarifIpl;
use App\Services\MidtransService;
use App\Services\NotifikasiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IplController extends Controller
{
    public function __construct(
        private MidtransService $midtrans,
        private NotifikasiService $notifikasiService
    ) {}

    public function tagihan(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = IplTagihan::with('warga.user')
            ->orderByDesc('tahun')
            ->orderByDesc('bulan');

        // Admin lihat semua, warga hanya tagihan sendiri
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            if (!$user->warga) {
                return response()->json(['message' => 'Data warga tidak ditemukan.'], 404);
            }
            $query->where('warga_id', $user->warga->id);
        }

        // Filter optional dari query string
        if ($request->bulan) {
            $query->where('bulan', $request->bulan);
        }
        if ($request->tahun) {
            $query->where('tahun', $request->tahun);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->jenis) {
            $query->where('jenis', $request->jenis);
        }

        return response()->json($query->paginate(20));
    }

    public function tagihanBulanIni(Request $request): JsonResponse
    {
        $warga = $request->user()->warga;

        if (! $warga) {
            return response()->json(['message' => 'Data warga tidak ditemukan.'], 404);
        }

        // Ambil SEMUA tagihan bulan ini (ipl_bulanan + kedukaan kalau ada)
        $tagihans = IplTagihan::where('warga_id', $warga->id)
            ->bulanIni()
            ->with('pembayaran')
            ->orderBy('jenis')
            ->get();

        // Hitung total semua tagihan + ambil yang utama (ipl_bulanan)
        $tagihanUtama = $tagihans->firstWhere('jenis', 'ipl_bulanan') ?? $tagihans->first();
        $totalKeseluruhan = $tagihans->sum(fn($t) => $t->total_tagihan);
        $totalBelumBayar = $tagihans
            ->whereIn('status', ['belum_bayar', 'terlambat'])
            ->sum(fn($t) => $t->total_tagihan);

        return response()->json([
            'tagihan' => $tagihanUtama, // backward compatibility
            'tagihans' => $tagihans,
            'total_keseluruhan' => $totalKeseluruhan,
            'total_belum_bayar' => $totalBelumBayar,
            'jumlah_tagihan' => $tagihans->count(),
            'jumlah_belum_bayar' => $tagihans->whereIn('status', ['belum_bayar', 'terlambat'])->count(),
        ]);
    }

    public function tunggakan(Request $request): JsonResponse
    {
        $warga = $request->user()->warga;

        if (! $warga) {
            return response()->json(['message' => 'Data warga tidak ditemukan.'], 404);
        }

        $tunggakan = IplTagihan::where('warga_id', $warga->id)
            ->belumBayar()
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get();

        return response()->json([
            'tunggakan' => $tunggakan,
            'total' => $tunggakan->sum('total_tagihan'),
            'jumlah' => $tunggakan->count(),
        ]);
    }

    public function bayar(Request $request, IplTagihan $tagihan): JsonResponse
    {
        $warga = $request->user()->warga;

        if (!$warga) {
            return response()->json(['message' => 'User tidak terdaftar sebagai warga.'], 403);
        }

        // Compare as integer to avoid string-int mismatch
        if ((int) $tagihan->warga_id !== (int) $warga->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        if ($tagihan->status === 'sudah_bayar') {
            return response()->json(['message' => 'Tagihan ini sudah dibayar.'], 422);
        }

        $existingPembayaran = Pembayaran::where('tagihan_id', $tagihan->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPembayaran) {
            return response()->json([
                'pembayaran' => $existingPembayaran,
                'snap_token' => $existingPembayaran->midtrans_snap_token,
                'redirect_url' => $existingPembayaran->midtrans_redirect_url,
            ]);
        }

        $orderId = 'IPL-' . $warga->id . '-' . $tagihan->bulan . $tagihan->tahun . '-' . Str::random(6);

        $snapData = $this->midtrans->createTransaction([
            'order_id' => $orderId,
            'gross_amount' => $tagihan->total_tagihan,
            'customer_details' => [
                'first_name' => $request->user()->name,
                'phone' => $request->user()->phone,
            ],
            'item_details' => [[
                'id' => 'IPL-' . $tagihan->bulan . '-' . $tagihan->tahun,
                'price' => $tagihan->nominal,
                'quantity' => 1,
                'name' => "IPL {$tagihan->nama_bulan} {$tagihan->tahun}",
            ]],
        ]);

        $pembayaran = Pembayaran::create([
            'tagihan_id' => $tagihan->id,
            'warga_id' => $warga->id,
            'order_id' => $orderId,
            'nominal' => $tagihan->total_tagihan,
            'midtrans_snap_token' => $snapData['token'],
            'midtrans_redirect_url' => $snapData['redirect_url'],
            'status' => 'pending',
        ]);

        return response()->json([
            'pembayaran' => $pembayaran,
            'snap_token' => $snapData['token'],
            'redirect_url' => $snapData['redirect_url'],
        ]);
    }

    public function midtransCallback(Request $request): JsonResponse
    {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        // Validasi payload
        if (empty($orderId) || empty($transactionStatus)) {
            return response()->json([
                'message' => 'Invalid payload. Need order_id & transaction_status.',
            ], 400);
        }

        $pembayaran = Pembayaran::where('order_id', $orderId)->first();
        if (!$pembayaran) {
            return response()->json([
                'message' => "Pembayaran dengan order_id $orderId tidak ditemukan.",
            ], 404);
        }

        $status = match (true) {
            $transactionStatus === 'capture' && $fraudStatus === 'accept' => 'success',
            $transactionStatus === 'settlement' => 'success',
            in_array($transactionStatus, ['cancel', 'deny', 'expire']) => 'failed',
            $transactionStatus === 'pending' => 'pending',
            default => $pembayaran->status,
        };

        $pembayaran->update([
            'status' => $status,
            'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
            'midtrans_payment_type' => $payload['payment_type'] ?? null,
            'midtrans_response' => $payload,
        ]);

        if ($status === 'success') {
            $tagihan = $pembayaran->tagihan;
            $tagihan->update([
                'status' => 'sudah_bayar',
                'tanggal_bayar' => now(),
            ]);

            // Kalau jenis kedukaan → tandai warga sudah bayar kedukaan
            if ($tagihan->jenis === 'kedukaan') {
                $tagihan->warga?->update([
                    'uang_kedukaan_dibayar' => true,
                    'tanggal_bayar_kedukaan' => now(),
                ]);
            }

            $this->notifikasiService->kirimNotifikasiPembayaranBerhasil($pembayaran);
        }

        return response()->json(['message' => 'OK']);
    }

    public function riwayatPembayaran(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Pembayaran::with(['tagihan', 'warga.user'])->orderByDesc('created_at');

        // Admin lihat semua, warga hanya pembayaran sendiri
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

    /**
     * Update status tagihan secara manual (oleh bendahara/super_admin)
     * Untuk catat pembayaran tunai/transfer langsung di luar Midtrans
     */
    /**
     * Generate tagihan untuk semua warga aktif (admin only)
     */
    public function generateTagihan(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!in_array($user->role, ['admin', 'super_admin', 'bendahara'])) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'bulan' => 'nullable|integer|min:1|max:12',
            'tahun' => 'nullable|integer|min:2020|max:2100',
        ]);

        $bulan = (int) ($request->bulan ?? now()->month);
        $tahun = (int) ($request->tahun ?? now()->year);
        $tarifIpl = (int) env('IPL_MONTHLY_AMOUNT', 65000);
        $tarifKedukaan = (int) env('IPL_KEDUKAAN_AMOUNT', 20000);
        $jatuhTempo = now()->setMonth($bulan)->setYear($tahun)->setDay(10);

        $wargaAktif = \App\Models\Warga::where('is_active', true)
            ->where('blok', 'E')
            ->get();

        $generatedIpl = 0;
        $generatedKedukaan = 0;
        $skipped = 0;

        foreach ($wargaAktif as $warga) {
            // === IPL Bulanan ===
            $existsIpl = IplTagihan::where('warga_id', $warga->id)
                ->where('jenis', 'ipl_bulanan')
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->exists();

            if ($existsIpl) {
                $skipped++;
            } else {
                try {
                    IplTagihan::create([
                        'warga_id' => $warga->id,
                        'jenis' => 'ipl_bulanan',
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'nominal' => $tarifIpl,
                        'jatuh_tempo' => $jatuhTempo,
                        'status' => 'belum_bayar',
                    ]);
                    $generatedIpl++;
                } catch (\Throwable $e) {
                    $skipped++; // race condition / duplicate
                }
            }

            // === Kedukaan — skip kalau warga sudah pernah bayar ===
            if ($warga->uang_kedukaan_dibayar) continue;

            // Check ada tidaknya kedukaan record (status apapun, bulan/tahun apapun)
            $existsKedukaan = IplTagihan::where('warga_id', $warga->id)
                ->where('jenis', 'kedukaan')
                ->exists();

            if ($existsKedukaan) continue;

            try {
                IplTagihan::create([
                    'warga_id' => $warga->id,
                    'jenis' => 'kedukaan',
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'nominal' => $tarifKedukaan,
                    'jatuh_tempo' => $jatuhTempo,
                    'status' => 'belum_bayar',
                    'keterangan' => 'Uang kedukaan (sekali bayar untuk warga baru)',
                ]);
                $generatedKedukaan++;
            } catch (\Throwable $e) {
                // skip silently
            }
        }

        return response()->json([
            'message' => "Tagihan {$bulan}/{$tahun}: {$generatedIpl} IPL + {$generatedKedukaan} Kedukaan dibuat, {$skipped} dilewati.",
            'generated_ipl' => $generatedIpl,
            'generated_kedukaan' => $generatedKedukaan,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Export tagihan ke CSV (bisa dibuka Excel)
     */
    public function exportTagihan(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['admin', 'super_admin', 'bendahara'])) {
            abort(403);
        }

        $query = IplTagihan::with('warga.user')
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->orderBy('warga_id');

        if ($request->bulan) $query->where('bulan', $request->bulan);
        if ($request->tahun) $query->where('tahun', $request->tahun);
        if ($request->status) $query->where('status', $request->status);
        if ($request->jenis) $query->where('jenis', $request->jenis);

        $tagihans = $query->get();

        $filename = 'tagihan_ipl_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tagihans) {
            $out = fopen('php://output', 'w');
            // BOM utk Excel detect UTF-8
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            // Header
            fputcsv($out, [
                'No', 'Nama Warga', 'No. HP', 'Alamat',
                'Jenis Tagihan', 'Periode', 'Nominal', 'Denda', 'Total',
                'Jatuh Tempo', 'Status', 'Tgl. Bayar', 'Keterangan',
            ]);
            $no = 1;
            foreach ($tagihans as $t) {
                fputcsv($out, [
                    $no++,
                    $t->warga?->user?->name ?? '-',
                    $t->warga?->user?->phone ?? '-',
                    'Blok ' . ($t->warga?->blok ?? '-') . ' No. ' . ($t->warga?->nomor_rumah ?? '-'),
                    $t->jenis === 'kedukaan' ? 'Uang Kedukaan' : 'IPL Bulanan',
                    $t->nama_bulan . ' ' . $t->tahun,
                    $t->nominal,
                    $t->denda,
                    $t->total_tagihan,
                    optional($t->jatuh_tempo)->format('d/m/Y') ?? '-',
                    match ($t->status) {
                        'sudah_bayar' => 'Lunas',
                        'belum_bayar' => 'Belum Bayar',
                        'terlambat' => 'Terlambat',
                        default => $t->status,
                    },
                    optional($t->tanggal_bayar)->format('d/m/Y') ?? '-',
                    $t->keterangan ?? '-',
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bayarManual(Request $request, IplTagihan $tagihan): JsonResponse
    {
        $user = $request->user();

        if (!$user->canManageTagihan()) {
            return response()->json([
                'message' => 'Hanya Super Admin & Bendahara yang bisa update pembayaran manual.',
            ], 403);
        }

        $request->validate([
            'metode' => 'required|in:tunai,transfer,lainnya',
            'tanggal_bayar' => 'nullable|date',
            'catatan' => 'nullable|string|max:500',
        ]);

        if ($tagihan->status === 'sudah_bayar') {
            return response()->json(['message' => 'Tagihan ini sudah lunas.'], 422);
        }

        \DB::beginTransaction();
        try {
            $orderId = 'MANUAL-' . $tagihan->id . '-' . Str::random(6);
            $tanggalBayar = $request->tanggal_bayar ? \Carbon\Carbon::parse($request->tanggal_bayar) : now();

            // Buat record Pembayaran
            $pembayaran = Pembayaran::create([
                'warga_id' => $tagihan->warga_id,
                'tagihan_id' => $tagihan->id,
                'order_id' => $orderId,
                'nominal' => $tagihan->nominal + $tagihan->denda,
                'status' => 'success',
                'midtrans_payment_type' => $request->metode,
                'catatan' => 'Bayar manual oleh ' . $user->name
                    . ($request->catatan ? '. ' . $request->catatan : '')
                    . '. Tgl: ' . $tanggalBayar->format('Y-m-d'),
            ]);

            // Update tagihan jadi lunas
            $tagihan->update([
                'status' => 'sudah_bayar',
                'tanggal_bayar' => $tanggalBayar,
            ]);

            // Kalau jenis kedukaan → mark warga sebagai sudah_bayar_kedukaan
            if ($tagihan->jenis === 'kedukaan') {
                $tagihan->warga?->update([
                    'uang_kedukaan_dibayar' => true,
                    'tanggal_bayar_kedukaan' => $tanggalBayar,
                ]);
            }

            \DB::commit();

            return response()->json([
                'message' => 'Pembayaran manual berhasil dicatat.',
                'tagihan' => $tagihan->fresh(),
                'pembayaran' => $pembayaran,
            ]);
        } catch (\Throwable $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function statusPembayaran(Request $request, Pembayaran $pembayaran): JsonResponse
    {
        $warga = $request->user()->warga;

        if ((int) $pembayaran->warga_id !== (int) ($warga?->id ?? 0)) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        // Kalau status masih pending, coba sinkron real-time dari Midtrans
        // Ini handle kasus user bayar tapi callback Midtrans belum sampai (atau gagal)
        if ($pembayaran->status === 'pending' && !empty($pembayaran->order_id)) {
            try {
                $midtransStatus = $this->midtrans->getStatus($pembayaran->order_id);
                if ($midtransStatus) {
                    $tx = $midtransStatus['transaction_status'] ?? null;
                    $fraud = $midtransStatus['fraud_status'] ?? null;

                    $newStatus = match (true) {
                        $tx === 'capture' && $fraud === 'accept' => 'success',
                        $tx === 'settlement' => 'success',
                        in_array($tx, ['cancel', 'deny', 'expire'], true) => 'failed',
                        $tx === 'pending' => 'pending',
                        default => null,
                    };

                    if ($newStatus && $newStatus !== $pembayaran->status) {
                        $pembayaran->update([
                            'status' => $newStatus,
                            'midtrans_payment_type' => $midtransStatus['payment_type'] ?? $pembayaran->midtrans_payment_type,
                            'midtrans_transaction_id' => $midtransStatus['transaction_id'] ?? $pembayaran->midtrans_transaction_id,
                        ]);

                        // Update tagihan kalau pembayaran success
                        if ($newStatus === 'success') {
                            $pembayaran->tagihan?->update([
                                'status' => 'sudah_bayar',
                                'tanggal_bayar' => now(),
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('Failed to sync Midtrans status: ' . $e->getMessage());
            }
        }

        return response()->json(['pembayaran' => $pembayaran->fresh()->load('tagihan')]);
    }
}
