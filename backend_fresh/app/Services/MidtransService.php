<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function createTransaction(array $params): array
    {
        $transactionDetails = [
            'order_id' => $params['order_id'],
            'gross_amount' => (int) $params['gross_amount'],
        ];

        $payload = [
            'transaction_details' => $transactionDetails,
            'customer_details' => $params['customer_details'] ?? [],
            'item_details' => $params['item_details'] ?? [],
            'callbacks' => [
                'finish' => config('app.frontend_url') . '/pembayaran/selesai',
                'error' => config('app.frontend_url') . '/pembayaran/gagal',
                'pending' => config('app.frontend_url') . '/pembayaran/pending',
            ],
        ];

        $snapToken = Snap::getSnapToken($payload);

        // URL Snap berbeda untuk sandbox vs production
        $snapHost = config('midtrans.is_production')
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';

        return [
            'token' => $snapToken,
            'redirect_url' => "{$snapHost}/snap/v2/vtweb/{$snapToken}",
        ];
    }

    /**
     * Get transaction status real-time dari Midtrans
     * Return: array with transaction_status, fraud_status, payment_type, dll
     */
    public function getStatus(string $orderId): ?array
    {
        try {
            $status = Transaction::status($orderId);
            return is_object($status) ? json_decode(json_encode($status), true) : (array) $status;
        } catch (\Throwable $e) {
            \Log::warning("Midtrans getStatus error for $orderId: " . $e->getMessage());
            return null;
        }
    }

    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = config('midtrans.server_key');
        $hash = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return $hash === $signatureKey;
    }
}
