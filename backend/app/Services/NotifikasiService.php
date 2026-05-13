<?php

namespace App\Services;

use App\Models\IplTagihan;
use App\Models\Notifikasi;
use App\Models\Pembayaran;
use App\Models\Pengaduan;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Support\Collection;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class NotifikasiService
{
    public function kirimNotifikasiPembayaranBerhasil(Pembayaran $pembayaran): void
    {
        $warga = $pembayaran->warga->load('user');
        $tagihan = $pembayaran->tagihan;

        $judul = 'Pembayaran IPL Berhasil';
        $pesan = "Pembayaran IPL {$tagihan->nama_bulan} {$tagihan->tahun} sebesar Rp " .
            number_format($pembayaran->nominal, 0, ',', '.') . " telah berhasil.";

        $this->simpanNotifikasi($warga->user_id, $judul, $pesan, 'pembayaran', [
            'pembayaran_id' => $pembayaran->id,
            'tagihan_id' => $tagihan->id,
        ]);

        $this->kirimFCM($warga->user, $judul, $pesan);
    }

    public function kirimReminderTagihan(Collection $tagihans): void
    {
        foreach ($tagihans as $tagihan) {
            $user = $tagihan->warga->user;
            $judul = 'Reminder Pembayaran IPL';
            $pesan = "IPL {$tagihan->nama_bulan} {$tagihan->tahun} belum dibayar. " .
                "Jatuh tempo: {$tagihan->jatuh_tempo->format('d/m/Y')}.";

            $this->simpanNotifikasi($user->id, $judul, $pesan, 'tagihan', [
                'tagihan_id' => $tagihan->id,
            ]);

            $this->kirimFCM($user, $judul, $pesan);
        }
    }

    public function kirimNotifikasiTerlambat(Collection $tagihans): void
    {
        foreach ($tagihans as $tagihan) {
            $user = $tagihan->warga->user;
            $denda = $tagihan->denda;
            $judul = 'Peringatan: IPL Terlambat';
            $pesan = "IPL {$tagihan->nama_bulan} {$tagihan->tahun} terlambat dibayar. " .
                "Denda sebesar Rp " . number_format($denda, 0, ',', '.') . " telah ditambahkan.";

            $this->simpanNotifikasi($user->id, $judul, $pesan, 'peringatan', [
                'tagihan_id' => $tagihan->id,
            ]);

            $this->kirimFCM($user, $judul, $pesan);
        }
    }

    public function notifikasiAdminPengaduanBaru(Pengaduan $pengaduan): void
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            $judul = 'Pengaduan Baru';
            $pesan = "Pengaduan baru dari warga: {$pengaduan->judul}";

            $this->simpanNotifikasi($admin->id, $judul, $pesan, 'pengaduan', [
                'pengaduan_id' => $pengaduan->id,
            ]);

            $this->kirimFCM($admin, $judul, $pesan);
        }
    }

    public function notifikasiWargaStatusPengaduan(Pengaduan $pengaduan): void
    {
        $user = $pengaduan->warga->user;
        $statusLabel = match ($pengaduan->status) {
            'diproses' => 'sedang diproses',
            'selesai' => 'telah diselesaikan',
            'ditolak' => 'ditolak',
            default => $pengaduan->status,
        };

        $judul = 'Update Pengaduan';
        $pesan = "Pengaduan \"{$pengaduan->judul}\" {$statusLabel}.";

        $this->simpanNotifikasi($user->id, $judul, $pesan, 'pengaduan', [
            'pengaduan_id' => $pengaduan->id,
        ]);

        $this->kirimFCM($user, $judul, $pesan);
    }

    private function simpanNotifikasi(int $userId, string $judul, string $pesan, string $tipe, array $data = []): void
    {
        Notifikasi::create([
            'user_id' => $userId,
            'judul' => $judul,
            'pesan' => $pesan,
            'tipe' => $tipe,
            'data' => $data,
        ]);
    }

    private function kirimFCM(User $user, string $judul, string $pesan): void
    {
        if (! $user->fcm_token) {
            return;
        }

        try {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create($judul, $pesan));

            Firebase::messaging()->send($message);
        } catch (\Exception $e) {
            logger()->error('FCM Error: ' . $e->getMessage());
        }
    }
}
