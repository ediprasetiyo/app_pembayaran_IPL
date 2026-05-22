<?php

namespace App\Services;

use App\Models\IplTagihan;
use App\Models\Notifikasi;
use App\Models\Pembayaran;
use App\Models\Pengaduan;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Support\Collection;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;

class NotifikasiService
{
    /**
     * Default templates (kalau admin belum customize di backoffice).
     * Placeholder: {nama}, {bulan}, {tahun}, {nominal}, {tanggal}, {denda}, {judul}, {status}
     */
    public const DEFAULT_TEMPLATES = [
        'pembayaran_sukses' => [
            'judul' => 'Pembayaran IPL Berhasil',
            'pesan' => 'Pembayaran IPL {bulan} {tahun} sebesar Rp {nominal} telah berhasil.',
        ],
        'reminder_tagihan' => [
            'judul' => 'Reminder Pembayaran IPL',
            'pesan' => 'IPL {bulan} {tahun} belum dibayar. Jatuh tempo: {tanggal}.',
        ],
        'tagihan_terlambat' => [
            'judul' => 'Peringatan: IPL Terlambat',
            'pesan' => 'IPL {bulan} {tahun} terlambat dibayar. Denda sebesar Rp {denda} telah ditambahkan.',
        ],
        'pengaduan_baru' => [
            'judul' => 'Pengaduan Baru',
            'pesan' => 'Pengaduan baru dari warga: {judul}',
        ],
        'pengaduan_update' => [
            'judul' => 'Update Pengaduan',
            'pesan' => 'Pengaduan "{judul}" {status}.',
        ],
        'berita_baru' => [
            'judul' => '📢 Pengumuman Baru',
            'pesan' => '{judul_berita}',
        ],
        'berita_update' => [
            'judul' => '✏️ Pengumuman Diperbarui',
            'pesan' => '{judul_berita}',
        ],
    ];

    /**
     * Get template (judul + pesan) untuk event tertentu.
     * Cek Settings 'notif_templates' dulu (JSON), fallback ke default.
     */
    public static function getTemplate(string $event): array
    {
        $raw = Setting::get('notif_templates');
        $custom = $raw ? json_decode($raw, true) : [];
        $custom = is_array($custom) ? $custom : [];

        $default = self::DEFAULT_TEMPLATES[$event] ?? ['judul' => 'Notifikasi', 'pesan' => ''];
        $userTemplate = $custom[$event] ?? [];

        return [
            'judul' => $userTemplate['judul'] ?? $default['judul'],
            'pesan' => $userTemplate['pesan'] ?? $default['pesan'],
        ];
    }

    /**
     * Render template dengan replace placeholder.
     */
    public static function render(string $event, array $vars): array
    {
        $template = self::getTemplate($event);
        $judul = $template['judul'];
        $pesan = $template['pesan'];
        foreach ($vars as $key => $value) {
            $judul = str_replace('{' . $key . '}', (string) $value, $judul);
            $pesan = str_replace('{' . $key . '}', (string) $value, $pesan);
        }
        return ['judul' => $judul, 'pesan' => $pesan];
    }

    public function kirimNotifikasiPembayaranBerhasil(Pembayaran $pembayaran): void
    {
        $warga = $pembayaran->warga->load('user');
        $tagihan = $pembayaran->tagihan;

        $rendered = self::render('pembayaran_sukses', [
            'nama' => $warga->user->name,
            'bulan' => $tagihan->nama_bulan,
            'tahun' => $tagihan->tahun,
            'nominal' => number_format($pembayaran->nominal, 0, ',', '.'),
            'tanggal' => now()->format('d/m/Y'),
        ]);

        $this->simpanNotifikasi($warga->user_id, $rendered['judul'], $rendered['pesan'], 'pembayaran', [
            'pembayaran_id' => $pembayaran->id,
            'tagihan_id' => $tagihan->id,
        ]);

        $this->kirimFCM($warga->user, $rendered['judul'], $rendered['pesan']);
    }

    public function kirimReminderTagihan(Collection $tagihans): void
    {
        foreach ($tagihans as $tagihan) {
            $user = $tagihan->warga->user;
            $rendered = self::render('reminder_tagihan', [
                'nama' => $user->name,
                'bulan' => $tagihan->nama_bulan,
                'tahun' => $tagihan->tahun,
                'nominal' => number_format($tagihan->total_tagihan, 0, ',', '.'),
                'tanggal' => $tagihan->jatuh_tempo->format('d/m/Y'),
            ]);

            $this->simpanNotifikasi($user->id, $rendered['judul'], $rendered['pesan'], 'tagihan', [
                'tagihan_id' => $tagihan->id,
            ]);

            $this->kirimFCM($user, $rendered['judul'], $rendered['pesan']);
        }
    }

    public function kirimNotifikasiTerlambat(Collection $tagihans): void
    {
        foreach ($tagihans as $tagihan) {
            $user = $tagihan->warga->user;
            $rendered = self::render('tagihan_terlambat', [
                'nama' => $user->name,
                'bulan' => $tagihan->nama_bulan,
                'tahun' => $tagihan->tahun,
                'denda' => number_format($tagihan->denda, 0, ',', '.'),
                'tanggal' => $tagihan->jatuh_tempo->format('d/m/Y'),
            ]);

            $this->simpanNotifikasi($user->id, $rendered['judul'], $rendered['pesan'], 'peringatan', [
                'tagihan_id' => $tagihan->id,
            ]);

            $this->kirimFCM($user, $rendered['judul'], $rendered['pesan']);
        }
    }

    public function notifikasiAdminPengaduanBaru(Pengaduan $pengaduan): void
    {
        $admins = User::where('role', 'admin')->get();
        $warga = $pengaduan->warga->load('user');

        foreach ($admins as $admin) {
            $rendered = self::render('pengaduan_baru', [
                'nama' => $warga->user->name ?? 'Warga',
                'judul' => $pengaduan->judul,
                'kategori' => $pengaduan->kategori,
            ]);

            $this->simpanNotifikasi($admin->id, $rendered['judul'], $rendered['pesan'], 'pengaduan', [
                'pengaduan_id' => $pengaduan->id,
            ]);

            $this->kirimFCM($admin, $rendered['judul'], $rendered['pesan']);
        }
    }

    /**
     * Kirim notifikasi berita ke SEMUA user aktif (warga + admin).
     * @param mixed $news Model News
     * @param string $event 'berita_baru' (default) atau 'berita_update'
     */
    public function kirimNotifikasiBeritaBaru($news, string $event = 'berita_baru'): void
    {
        // Ambil semua user aktif yang punya fcm_token
        $users = User::where('is_active', true)
            ->whereNotNull('fcm_token')
            ->get();

        $ringkasan = $news->ringkasan ?? strip_tags($news->konten ?? '');
        if (strlen($ringkasan) > 100) $ringkasan = substr($ringkasan, 0, 97) . '...';

        foreach ($users as $user) {
            $rendered = self::render($event, [
                'nama' => $user->name,
                'judul_berita' => $news->judul,
                'ringkasan' => $ringkasan,
            ]);

            // Pakai tipe 'info' karena enum tabel notifikasi tidak punya 'berita'
            $this->simpanNotifikasi($user->id, $rendered['judul'], $rendered['pesan'], 'info', [
                'news_id' => $news->id,
                'slug' => $news->slug ?? null,
                'type' => 'berita',
                'event' => $event,
            ]);

            $this->kirimFCM($user, $rendered['judul'], $rendered['pesan']);
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

        $rendered = self::render('pengaduan_update', [
            'nama' => $user->name,
            'judul' => $pengaduan->judul,
            'status' => $statusLabel,
        ]);

        $this->simpanNotifikasi($user->id, $rendered['judul'], $rendered['pesan'], 'pengaduan', [
            'pengaduan_id' => $pengaduan->id,
        ]);

        $this->kirimFCM($user, $rendered['judul'], $rendered['pesan']);
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

        // PENTING: pakai config() bukan env() — env() return null setelah `config:cache`
        // (Laravel cache compiles config sekali, env() di runtime tidak kebaca).
        $creds = config('firebase.projects.app.credentials') ?: env('FIREBASE_CREDENTIALS');
        if (!$creds) {
            logger()->info('FCM skip: credentials belum di-setup untuk user ' . $user->id);
            return;
        }

        try {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create($judul, $pesan))
                ->withData([
                    'title' => $judul,
                    'body' => $pesan,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]);

            Firebase::messaging()->send($message);
        } catch (\Throwable $e) {
            // Log saja, jangan crash request user
            logger()->warning('FCM kirim gagal untuk user ' . $user->id . ': ' . $e->getMessage());
        }
    }
}
