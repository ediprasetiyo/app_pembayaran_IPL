<?php

namespace App\Services;

use App\Models\IplTagihan;
use App\Models\Pembayaran;
use App\Models\Pengeluaran;
use App\Models\Setting;
use App\Models\Warga;

/**
 * Service untuk menghitung saldo kas (IPL & Uang Kedukaan).
 *
 * Saldo IPL aktif = SUM pembayaran IPL sukses - SUM pengeluaran sumber=ipl + adjustment_ipl
 * Saldo Kedukaan aktif = SUM pembayaran kedukaan sukses - SUM pengeluaran sumber=kedukaan + adjustment_kedukaan
 *
 * Super admin bisa adjust manual via Settings keys:
 *   saldo_ipl_adjustment, saldo_kedukaan_adjustment (signed int)
 */
class KasService
{
    public static function totalPemasukanIpl(): int
    {
        return (int) IplTagihan::where('jenis', 'ipl_bulanan')
            ->where('status', 'sudah_bayar')
            ->join('pembayaran', 'ipl_tagihan.id', '=', 'pembayaran.tagihan_id')
            ->where('pembayaran.status', 'success')
            ->sum('pembayaran.nominal');
    }

    public static function totalPengeluaranIpl(): int
    {
        return (int) Pengeluaran::where('sumber_dana', 'ipl')->sum('nominal');
    }

    public static function totalPemasukanKedukaan(): int
    {
        return (int) IplTagihan::where('jenis', 'kedukaan')
            ->where('status', 'sudah_bayar')
            ->join('pembayaran', 'ipl_tagihan.id', '=', 'pembayaran.tagihan_id')
            ->where('pembayaran.status', 'success')
            ->sum('pembayaran.nominal');
    }

    public static function totalPengeluaranKedukaan(): int
    {
        return (int) Pengeluaran::where('sumber_dana', 'kedukaan')->sum('nominal');
    }

    public static function adjustmentIpl(): int
    {
        return (int) (Setting::get('saldo_ipl_adjustment') ?? 0);
    }

    public static function adjustmentKedukaan(): int
    {
        return (int) (Setting::get('saldo_kedukaan_adjustment') ?? 0);
    }

    public static function saldoIpl(): int
    {
        return self::totalPemasukanIpl() - self::totalPengeluaranIpl() + self::adjustmentIpl();
    }

    public static function saldoKedukaan(): int
    {
        return self::totalPemasukanKedukaan() - self::totalPengeluaranKedukaan() + self::adjustmentKedukaan();
    }

    /**
     * Cek apakah saldo kedukaan habis (≤ 0). Kalau iya, reset semua warga
     * jadi belum bayar kedukaan supaya tagihan kedukaan muncul lagi bulan depan.
     *
     * Return: true kalau dilakukan reset, false kalau saldo masih ada.
     */
    public static function autoResetKedukaanKalauHabis(): bool
    {
        if (self::saldoKedukaan() > 0) {
            return false;
        }

        // Saldo habis → reset semua warga
        Warga::where('is_active', true)->update([
            'uang_kedukaan_dibayar' => false,
            'tanggal_bayar_kedukaan' => null,
        ]);

        // Reset adjustment juga supaya tidak double-count
        Setting::set('saldo_kedukaan_adjustment', '0');

        AuditLogger::log(
            action: 'kedukaan_auto_reset',
            description: 'Saldo uang kedukaan habis. Auto-reset: semua warga akan dapat tagihan kedukaan baru bulan depan.',
            severity: 'warning',
        );

        return true;
    }

    /**
     * Summary untuk dashboard.
     */
    public static function summary(): array
    {
        return [
            'ipl' => [
                'pemasukan' => self::totalPemasukanIpl(),
                'pengeluaran' => self::totalPengeluaranIpl(),
                'adjustment' => self::adjustmentIpl(),
                'saldo' => self::saldoIpl(),
            ],
            'kedukaan' => [
                'pemasukan' => self::totalPemasukanKedukaan(),
                'pengeluaran' => self::totalPengeluaranKedukaan(),
                'adjustment' => self::adjustmentKedukaan(),
                'saldo' => self::saldoKedukaan(),
            ],
        ];
    }
}
