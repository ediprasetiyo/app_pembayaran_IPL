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
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Bank_Central_Asia.svg/200px-Bank_Central_Asia.svg.png',
                'fee_type' => 'fixed',
                'fee_value' => 4000,
                'fee_label' => 'Rp 4.000 / transaksi',
            ],
            [
                'code' => 'bni_va',
                'name' => 'BNI Virtual Account',
                'category' => 'Virtual Account',
                'icon' => '🏦',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/BNI_logo.svg/200px-BNI_logo.svg.png',
                'fee_type' => 'fixed',
                'fee_value' => 4000,
                'fee_label' => 'Rp 4.000 / transaksi',
            ],
            [
                'code' => 'bri_va',
                'name' => 'BRI Virtual Account',
                'category' => 'Virtual Account',
                'icon' => '🏦',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f5/BRI_2020.svg/200px-BRI_2020.svg.png',
                'fee_type' => 'fixed',
                'fee_value' => 4000,
                'fee_label' => 'Rp 4.000 / transaksi',
            ],
            [
                'code' => 'permata_va',
                'name' => 'Permata Virtual Account',
                'category' => 'Virtual Account',
                'icon' => '🏦',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3d/Logo_Bank_Permata.svg/200px-Logo_Bank_Permata.svg.png',
                'fee_type' => 'fixed',
                'fee_value' => 4000,
                'fee_label' => 'Rp 4.000 / transaksi',
            ],
            [
                'code' => 'other_va',
                'name' => 'Bank Lainnya (Mandiri, dll)',
                'category' => 'Virtual Account',
                'icon' => '🏦',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ad/Bank_Mandiri_logo_2016.svg/200px-Bank_Mandiri_logo_2016.svg.png',
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
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/86/Gopay_logo.svg/200px-Gopay_logo.svg.png',
                'fee_type' => 'percent',
                'fee_value' => 2.0,
                'fee_label' => '2% per transaksi',
            ],
            [
                'code' => 'shopeepay',
                'name' => 'ShopeePay',
                'category' => 'E-Wallet',
                'icon' => '🟧',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0e/ShopeePay-Logo.png/200px-ShopeePay-Logo.png',
                'fee_type' => 'percent',
                'fee_value' => 2.0,
                'fee_label' => '2% per transaksi',
            ],
            [
                'code' => 'dana',
                'name' => 'DANA',
                'category' => 'E-Wallet',
                'icon' => '🔵',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/72/Logo_dana_blue.svg/200px-Logo_dana_blue.svg.png',
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
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/89/QRIS_logo.svg/200px-QRIS_logo.svg.png',
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
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/200px-Visa_Inc._logo.svg.png',
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
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Indomaret_logo.svg/200px-Indomaret_logo.svg.png',
                'fee_type' => 'fixed',
                'fee_value' => 5000,
                'fee_label' => 'Rp 5.000 / transaksi',
            ],
            [
                'code' => 'alfamart',
                'name' => 'Alfamart',
                'category' => 'Gerai Retail',
                'icon' => '🏪',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3e/Alfamart_logo.svg/200px-Alfamart_logo.svg.png',
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
                'logo_url' => '',
                'fee_type' => 'percent',
                'fee_value' => 3.0,
                'fee_label' => '3% per transaksi',
            ],
            [
                'code' => 'kredivo',
                'name' => 'Kredivo',
                'category' => 'PayLater',
                'icon' => '💰',
                'logo_url' => '',
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
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Bank_Central_Asia.svg/200px-Bank_Central_Asia.svg.png',
                'fee_type' => 'fixed',
                'fee_value' => 2000,
                'fee_label' => 'Rp 2.000 / transaksi',
            ],
            [
                'code' => 'cimb_clicks',
                'name' => 'CIMB Clicks',
                'category' => 'Direct Banking',
                'icon' => '🏧',
                'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/95/CIMB_Niaga_logo.svg/200px-CIMB_Niaga_logo.svg.png',
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
     * Get custom logo URLs dari Settings (yang di-override admin via upload).
     * Return: array code => url
     */
    public static function getCustomLogos(): array
    {
        $raw = Setting::get('midtrans_method_logos');
        if (empty($raw)) return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set custom logo untuk 1 method. URL = null/empty → reset ke default.
     */
    public static function setCustomLogo(string $code, ?string $url): void
    {
        $logos = self::getCustomLogos();
        if (empty($url)) {
            unset($logos[$code]);
        } else {
            $logos[$code] = $url;
        }
        Setting::set('midtrans_method_logos', json_encode($logos));
    }

    /**
     * Get full list dengan status enabled + custom logo URL (jika ada).
     */
    public static function listWithStatus(): array
    {
        $enabled = self::getEnabledCodes();
        $customLogos = self::getCustomLogos();

        return array_map(function ($m) use ($enabled, $customLogos) {
            $m['enabled'] = in_array($m['code'], $enabled);
            // Override logo_url kalau admin sudah upload custom
            if (!empty($customLogos[$m['code']])) {
                $m['logo_url'] = $customLogos[$m['code']];
                $m['logo_is_custom'] = true;
            } else {
                $m['logo_is_custom'] = false;
            }
            return $m;
        }, self::masterList());
    }

    /**
     * Hitung biaya admin (fee Midtrans) untuk 1 method dengan amount tertentu.
     * Return integer rupiah (rounded up).
     *
     * @param int $amount Nominal dasar (sebelum fee)
     * @param string $code Method code (e.g. 'gopay', 'bca_va')
     * @return int Biaya admin yang harus ditambahkan
     */
    public static function calculateFee(int $amount, string $code): int
    {
        $method = collect(self::masterList())->firstWhere('code', $code);
        if (!$method) return 0;

        $type = $method['fee_type'] ?? 'fixed';
        $value = $method['fee_value'] ?? 0;
        $extra = $method['fee_extra'] ?? 0;

        return match ($type) {
            'fixed' => (int) $value,
            'percent' => (int) ceil($amount * $value / 100),
            'percent_plus' => (int) (ceil($amount * $value / 100) + $extra),
            default => 0,
        };
    }

    /**
     * Get enabled methods + fee dihitung untuk amount tertentu.
     * Untuk ditampilkan di mobile sebelum user pilih method.
     */
    public static function getEnabledForAmount(int $amount): array
    {
        $enabled = self::getEnabledCodes();
        $customLogos = self::getCustomLogos();
        $all = self::masterList();
        $result = [];

        foreach ($all as $m) {
            if (!in_array($m['code'], $enabled)) continue;
            $fee = self::calculateFee($amount, $m['code']);
            $m['fee_amount'] = $fee;
            $m['total_amount'] = $amount + $fee;
            // Override dengan custom logo kalau ada
            if (!empty($customLogos[$m['code']])) {
                $m['logo_url'] = $customLogos[$m['code']];
            }
            $result[] = $m;
        }

        // Sort: fee terendah dulu (paling hemat untuk user)
        usort($result, fn($a, $b) => $a['fee_amount'] <=> $b['fee_amount']);
        return $result;
    }
}
