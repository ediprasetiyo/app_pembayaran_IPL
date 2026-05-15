<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

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

        return [
            'token' => $snapToken,
            'redirect_url' => 'https://app.midtrans.com/snap/v2/vtweb/' . $snapToken,
        ];
    }

    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = config('midtrans.server_key');
        $hash = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return $hash === $signatureKey;
    }
}
