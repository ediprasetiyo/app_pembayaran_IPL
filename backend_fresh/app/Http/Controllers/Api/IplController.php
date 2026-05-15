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
        if ($user->role !== 'admin') {
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

        return response()->json($query->paginate(20));
    }

    public function tagihanBulanIni(Request $request): JsonResponse
    {
        $warga = $request->user()->warga;

        if (! $warga) {
            return response()->json(['message' => 'Data warga tidak ditemukan.'], 404);
        }

        $tagihan = IplTagihan::where('warga_id', $warga->id)
            ->bulanIni()
            ->with('pembayaran')
            ->first();

        return response()->json(['tagihan' => $tagihan]);
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

        if ($tagihan->warga_id !== $warga->id) {
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
        $orderId = $payload['order_id'];
        $transactionStatus = $payload['transaction_status'];
        $fraudStatus = $payload['fraud_status'] ?? null;

        $pembayaran = Pembayaran::where('order_id', $orderId)->firstOrFail();

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
            $pembayaran->tagihan->update([
                'status' => 'sudah_bayar',
                'tanggal_bayar' => now(),
            ]);

            $this->notifikasiService->kirimNotifikasiPembayaranBerhasil($pembayaran);
        }

        return response()->json(['message' => 'OK']);
    }

    public function riwayatPembayaran(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Pembayaran::with(['tagihan', 'warga.user'])->orderByDesc('created_at');

        // Admin lihat semua, warga hanya pembayaran sendiri
        if ($user->role !== 'admin') {
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

    public function statusPembayaran(Request $request, Pembayaran $pembayaran): JsonResponse
    {
        $warga = $request->user()->warga;

        if ($pembayaran->warga_id !== $warga->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        return response()->json(['pembayaran' => $pembayaran->load('tagihan')]);
    }
}
