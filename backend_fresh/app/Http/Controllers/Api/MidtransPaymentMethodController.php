<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\MidtransPaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransPaymentMethodController extends Controller
{
    /**
     * List semua payment method Midtrans dengan biaya admin & status aktif.
     */
    public function index(Request $request): JsonResponse
    {
        $methods = MidtransPaymentMethodService::listWithStatus();

        // Group by category untuk display lebih rapi
        $grouped = [];
        foreach ($methods as $m) {
            $cat = $m['category'];
            if (!isset($grouped[$cat])) $grouped[$cat] = [];
            $grouped[$cat][] = $m;
        }

        return response()->json([
            'data' => $methods,
            'grouped' => $grouped,
            'total' => count($methods),
            'enabled_count' => count(array_filter($methods, fn($m) => $m['enabled'])),
            'reference_url' => 'https://midtrans.com/id/biaya-transaksi',
            'disclaimer' => 'Tarif biaya admin di atas adalah tarif standar Midtrans. Tarif aktual dapat berubah sewaktu-waktu sesuai kebijakan PT Midtrans. Untuk tarif terkini, cek midtrans.com/id/biaya-transaksi.',
        ]);
    }

    /**
     * Toggle status aktif/nonaktif untuk 1 payment method.
     * Body: { code: 'gopay', enabled: true }
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        $methods = MidtransPaymentMethodService::masterList();
        $found = collect($methods)->firstWhere('code', $request->code);
        if (!$found) {
            return response()->json(['message' => 'Payment method tidak dikenali.'], 422);
        }

        $enabled = MidtransPaymentMethodService::toggle($request->code, $request->enabled);

        AuditLogger::log(
            action: $request->enabled ? 'payment_method_enabled' : 'payment_method_disabled',
            description: "Payment method {$found['name']} ({$request->code}) di-" . ($request->enabled ? 'aktifkan' : 'nonaktifkan'),
            newValues: ['code' => $request->code, 'enabled' => $request->enabled],
        );

        return response()->json([
            'message' => "Payment method {$found['name']} berhasil di-" . ($request->enabled ? 'aktifkan' : 'nonaktifkan') . '.',
            'enabled_codes' => $enabled,
        ]);
    }

    /**
     * Bulk update — set semua enabled codes sekaligus.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'codes' => 'required|array',
            'codes.*' => 'string',
        ]);

        MidtransPaymentMethodService::setEnabledCodes($request->codes);

        AuditLogger::log(
            action: 'payment_methods_bulk_update',
            description: 'Bulk update payment methods: ' . count($request->codes) . ' aktif',
            newValues: ['codes' => $request->codes],
        );

        return response()->json([
            'message' => count($request->codes) . ' payment method aktif.',
            'enabled_codes' => $request->codes,
        ]);
    }
}
