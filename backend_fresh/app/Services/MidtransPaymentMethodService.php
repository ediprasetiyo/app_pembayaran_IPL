<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Service untuk manage payment methods yang tersedia di Midtrans Snap.
 *
 * - Daftar method dengan biaya admin (MDR) sesuai tarif resmi Midtrans
 *   (Reference: https://midtrans.com/id/biaya-transaksi)
 * - Admin bisa enable/disable method dari backoffice → setting tersimpan
 *   di tabel `settings` dengan key 'midtrans_enabled_methods'
 * - Saat warga bayar, Snap dibuat dengan `enabled_payments` filter dari
 *   daftar yang aktif saja.
 *
 * Catatan:
 * - Biaya admin (fee/MDR) di sini adalah tarif STANDAR Midtrans per Mei 2026.
 *   Tarif aktual dapat berubah sewaktu-waktu sesuai kebijakan PT Midtrans.
 *   Untuk update terkini cek midtrans.com/id/biaya-transaksi.
 */
class MidtransPaymentMethodService
{
    /**
     * Master list payment method Midtrans yang didukung Snap.
     * `code` adalah kode resmi Midtrans untuk parameter enabled_payments.
     */
    public static function masterList(): array
    {
        return [
            // === Virtual Account ===
            [
                'code' => 'bca_va',
                'name' => 'BCA Virtual Account',
                'category' => 'Virtual Account',
                'icon' => '🏦',
                'fee_type' => 'fixed',
                'fee_value' => 4000,
                'fee_label' => 'Rp 4.000 / transaksi',
            ],
            [
                'code' => 'bni_va',
                'name' => 'BNI Virtual Account',
                'category' => 'Virtual Account',
                'icon' => '🏦',
                'fee_type' => 'fixed',
                'fee_value' => 4000,
                'fee_label' => 'Rp 4.000 / transaksi',
            ],
            [
                'code' => 'bri_va',
                'name' => 'BRI Virtual Account',
                'category' => 'Virtual Account',
                'icon' => '🏦',
                'fee_type' => 'fixed',
                'fee_value' => 4000,
                'fee_label' => 'Rp 4.000 / transaksi',
            ],
            [
                'code' => 'permata_va',
                'name' => 'Permata Virtual Account',
                'category' => 'Virtual Account',
                'icon' => '🏦',
                'fee_type' => 'fixed',
                'fee_value' => 4000,
                'fee_label' => 'Rp 4.000 / transaksi',
            ],
            [
                'code' => 'other_va',
                'name' => 'Bank Lainnya (Mandiri, dll)',
                'category' => 'Virtual Account',
                'icon' => '🏦',
                'fee_type' => 'fixed',
                'fee_value' => 4000,
                'fee_label' => 'Rp 4.000 / transaksi',
            ],

            // === E-Wallet ===
            [
                'code' => 'gopay',
                'name' => 'GoPay',
                'category' => 'E-Wallet',
                'icon' => '🟢',
                'fee_type' => 'percent',
                'fee_value' => 2.0,
                'fee_label' => '2% per transaksi',
            ],
            [
                'code' => 'shopeepay',
                'name' => 'ShopeePay',
                'category' => 'E-Wallet',
                'icon' => '🟧',
                'fee_type' => 'percent',
                'fee_value' => 2.0,
                'fee_label' => '2% per transaksi',
            ],
            [
                'code' => 'dana',
                'name' => 'DANA',
                'category' => 'E-Wallet',
                'icon' => '🔵',
                'fee_type' => 'percent',
                'fee_value' => 1.5,
                'fee_label' => '1.5% per transaksi',
            ],

            // === QRIS ===
            [
                'code' => 'qris',
                'name' => 'QRIS (semua e-wallet & m-banking)',
                'category' => 'QRIS',
                'icon' => '📱',
                'fee_type' => 'percent',
                'fee_value' => 0.7,
                'fee_label' => '0.7% per transaksi (MDR)',
            ],

            // === Credit Card ===
            [
                'code' => 'credit_card',
                'name' => 'Kartu Kredit (Visa/Master/JCB)',
                'category' => 'Kartu Kredit',
                'icon' => '💳',
                'fee_type' => 'percent_plus',
                'fee_value' => 2.9,
                'fee_extra' => 2000,
                'fee_label' => '2.9% + Rp 2.000',
            ],

            // === Convenience Store ===
            [
                'code' => 'indomaret',
                'name' => 'Indomaret',
                'category' => 'Gerai Retail',
                'icon' => '🏪',
                'fee_type' => 'fixed',
                'fee_value' => 5000,
                'fee_label' => 'Rp 5.000 / transaksi',
            ],
            [
                'code' => 'alfamart',
                'name' => 'Alfamart',
                'category' => 'Gerai Retail',
                'icon' => '🏪',
                'fee_type' => 'fixed',
                'fee_value' => 5000,
                'fee_label' => 'Rp 5.000 / transaksi',
            ],

            // === PayLater ===
            [
                'code' => 'akulaku',
                'name' => 'Akulaku PayLater',
                'category' => 'PayLater',
                'icon' => '💰',
                'fee_type' => 'percent',
                'fee_value' => 3.0,
                'fee_label' => '3% per transaksi',
            ],
            [
                'code' => 'kredivo',
                'name' => 'Kredivo',
                'category' => 'PayLater',
                'icon' => '💰',
                'fee_type' => 'percent',
                'fee_value' => 3.0,
                'fee_label' => '3% per transaksi',
            ],

            // === Direct Banking ===
            [
                'code' => 'bca_klikpay',
                'name' => 'BCA KlikPay',
                'category' => 'Direct Banking',
                'icon' => '🏧',
                'fee_type' => 'fixed',
                'fee_value' => 2000,
                'fee_label' => 'Rp 2.000 / transaksi',
            ],
            [
                'code' => 'cimb_clicks',
                'name' => 'CIMB Clicks',
                'category' => 'Direct Banking',
                'icon' => '🏧',
                'fee_type' => 'fixed',
                'fee_value' => 2000,
                'fee_label' => 'Rp 2.000 / transaksi',
            ],
        ];
    }

    /**
     * Get enabled method codes dari Settings. Kalau belum ada setting,
     * return semua method (default semua aktif).
     */
    public static function getEnabledCodes(): array
    {
        $raw = Setting::get('midtrans_enabled_methods');
        if (empty($raw)) {
            // Default: aktifkan semua yang paling umum
            return ['bca_va', 'bni_va', 'bri_va', 'permata_va', 'other_va', 'gopay', 'shopeepay', 'qris', 'credit_card'];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Save enabled codes ke Settings.
     */
    public static function setEnabledCodes(array $codes): void
    {
        $valid = array_column(self::masterList(), 'code');
        $filtered = array_values(array_intersect($codes, $valid));
        Setting::set('midtrans_enabled_methods', json_encode($filtered));
    }

    /**
     * Toggle 1 method aktif/nonaktif.
     */
    public static function toggle(string $code, bool $enabled): array
    {
        $current = self::getEnabledCodes();
        if ($enabled) {
            if (!in_array($code, $current)) $current[] = $code;
        } else {
            $current = array_values(array_filter($current, fn($c) => $c !== $code));
        }
        self::setEnabledCodes($current);
        return $current;
    }

    /**
     * Get full list dengan status enabled untuk display di backoffice.
     */
    public static function listWithStatus(): array
    {
        $enabled = self::getEnabledCodes();
        return array_map(function ($m) use ($enabled) {
            $m['enabled'] = in_array($m['code'], $enabled);
            return $m;
        }, self::masterList());
    }
}
