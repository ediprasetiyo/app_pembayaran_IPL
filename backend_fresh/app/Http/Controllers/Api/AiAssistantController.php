<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IplTagihan;
use App\Models\Pengaduan;
use App\Models\Pembayaran;
use App\Models\Warga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function ask(Request $request): JsonResponse
    {
        $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $q = strtolower(trim($request->question));

        // Routing intent berdasarkan keyword
        $answer = $this->detectIntent($q);

        return response()->json([
            'question' => $request->question,
            'answer' => $answer,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function detectIntent(string $q): array
    {
        // === WARGA BELUM BAYAR ===
        if ($this->matchKeywords($q, ['belum bayar', 'tunggakan', 'menunggak', 'belum lunas'])) {
            return $this->wargaBelumBayar();
        }

        // === WARGA SUDAH BAYAR ===
        if ($this->matchKeywords($q, ['sudah bayar', 'sudah lunas', 'lunas bulan ini'])) {
            return $this->wargaSudahBayar();
        }

        // === TOTAL PENDAPATAN ===
        if ($this->matchKeywords($q, ['total pendapatan', 'total pemasukan', 'berapa pendapatan', 'jumlah pendapatan', 'total uang'])) {
            return $this->totalPendapatan();
        }

        // === DANA KEDUKAAN ===
        if ($this->matchKeywords($q, ['dana kedukaan', 'uang kedukaan', 'total kedukaan'])) {
            return $this->danaKedukaan();
        }

        // === PENGADUAN BARU / BELUM DIPROSES ===
        if ($this->matchKeywords($q, ['pengaduan baru', 'pengaduan belum', 'laporan baru', 'keluhan baru', 'pengaduan menunggu'])) {
            return $this->pengaduanBaru();
        }

        // === SARAN SOLUSI PENGADUAN ===
        if ($this->matchKeywords($q, ['solusi pengaduan', 'solusi keluhan', 'cara menangani', 'bagaimana mengatasi', 'tanggapi'])) {
            return $this->saranSolusi($q);
        }

        // === STATISTIK / RINGKASAN ===
        if ($this->matchKeywords($q, ['ringkasan', 'statistik', 'overview', 'rekap', 'rangkuman'])) {
            return $this->ringkasanUmum();
        }

        // === JUMLAH WARGA ===
        if ($this->matchKeywords($q, ['jumlah warga', 'berapa warga', 'total warga'])) {
            return $this->jumlahWarga();
        }

        // === BANTUAN / HELP ===
        if ($this->matchKeywords($q, ['help', 'bantu', 'apa yang bisa', 'menu', 'fitur'])) {
            return $this->help();
        }

        // Default: tidak paham
        return [
            'type' => 'text',
            'text' => "Maaf, saya belum memahami pertanyaan tersebut. Coba tanyakan hal seperti:\n\n• Siapa yang belum bayar bulan ini?\n• Berapa total pendapatan?\n• Pengaduan baru apa saja?\n• Bagaimana solusi untuk pengaduan kebersihan?\n• Ringkasan data\n\nKetik 'bantu' untuk lihat semua perintah.",
        ];
    }

    private function matchKeywords(string $text, array $keywords): bool
    {
        foreach ($keywords as $k) {
            if (str_contains($text, $k)) return true;
        }
        return false;
    }

    // ============ INTENT HANDLERS ============

    private function wargaBelumBayar(): array
    {
        $tagihan = IplTagihan::with('warga.user')
            ->where('jenis', 'ipl_bulanan')
            ->where('bulan', now()->month)
            ->where('tahun', now()->year)
            ->whereIn('status', ['belum_bayar', 'terlambat'])
            ->orderBy('jatuh_tempo')
            ->get();

        if ($tagihan->isEmpty()) {
            return [
                'type' => 'success',
                'text' => '✅ Semua warga sudah lunas IPL bulan ini! Tidak ada tunggakan.',
            ];
        }

        $list = $tagihan->map(fn($t) => [
            'nama' => $t->warga?->user?->name ?? '-',
            'alamat' => 'Blok ' . ($t->warga?->blok ?? '-') . '-' . ($t->warga?->nomor_rumah ?? '-'),
            'phone' => $t->warga?->user?->phone ?? '-',
            'nominal' => $t->total_tagihan,
        ])->values();

        return [
            'type' => 'list_warga',
            'title' => "📋 Warga yang Belum Bayar IPL Bulan Ini ({$tagihan->count()} orang)",
            'data' => $list,
            'total_nominal' => $tagihan->sum('total_tagihan'),
            'text' => "Ada {$tagihan->count()} warga yang belum bayar IPL bulan " . now()->locale('id')->translatedFormat('F Y') . ".",
        ];
    }

    private function wargaSudahBayar(): array
    {
        $tagihan = IplTagihan::with('warga.user')
            ->where('jenis', 'ipl_bulanan')
            ->where('bulan', now()->month)
            ->where('tahun', now()->year)
            ->where('status', 'sudah_bayar')
            ->get();

        $list = $tagihan->map(fn($t) => [
            'nama' => $t->warga?->user?->name ?? '-',
            'alamat' => 'Blok ' . ($t->warga?->blok ?? '-') . '-' . ($t->warga?->nomor_rumah ?? '-'),
            'nominal' => $t->total_tagihan,
        ])->values();

        return [
            'type' => 'list_warga',
            'title' => "✅ Warga yang Sudah Lunas IPL Bulan Ini ({$tagihan->count()} orang)",
            'data' => $list,
            'total_nominal' => $tagihan->sum('total_tagihan'),
            'text' => "Ada {$tagihan->count()} warga yang sudah lunas IPL bulan ini.",
        ];
    }

    private function totalPendapatan(): array
    {
        $totalIpl = Pembayaran::where('status', 'success')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('nominal');

        $totalAllTime = Pembayaran::where('status', 'success')->sum('nominal');

        $totalIplFmt = 'Rp ' . number_format($totalIpl, 0, ',', '.');
        $totalAllFmt = 'Rp ' . number_format($totalAllTime, 0, ',', '.');

        return [
            'type' => 'stats',
            'text' => "💰 **Pendapatan Bulan Ini ({" . now()->locale('id')->translatedFormat('F Y') . "}):** {$totalIplFmt}\n\n📊 **Total Akumulasi (semua waktu):** {$totalAllFmt}",
        ];
    }

    private function danaKedukaan(): array
    {
        $sudahBayar = Warga::where('uang_kedukaan_dibayar', true)->count();
        $belumBayar = Warga::where('uang_kedukaan_dibayar', false)->where('is_active', true)->count();
        $tarif = (int) env('IPL_KEDUKAAN_AMOUNT', 20000);
        $totalDana = $sudahBayar * $tarif;

        return [
            'type' => 'stats',
            'text' => "🤝 **Dana Uang Kedukaan**\n\n" .
                "• Total dana terkumpul: **Rp " . number_format($totalDana, 0, ',', '.') . "**\n" .
                "• Warga sudah bayar: **{$sudahBayar} orang**\n" .
                "• Warga belum bayar: **{$belumBayar} orang**\n" .
                "• Tarif per warga: Rp " . number_format($tarif, 0, ',', '.') . " (sekali bayar)",
        ];
    }

    private function pengaduanBaru(): array
    {
        $pengaduan = Pengaduan::with('warga.user')
            ->whereIn('status', ['baru', 'diproses'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        if ($pengaduan->isEmpty()) {
            return [
                'type' => 'success',
                'text' => '✅ Tidak ada pengaduan baru yang perlu ditangani.',
            ];
        }

        $list = $pengaduan->map(fn($p) => [
            'judul' => $p->judul,
            'kategori' => $p->kategori,
            'status' => $p->status,
            'warga' => $p->warga?->user?->name ?? '-',
            'tanggal' => $p->created_at->format('d M Y'),
        ])->values();

        return [
            'type' => 'list_pengaduan',
            'title' => "📢 Pengaduan yang Perlu Ditangani ({$pengaduan->count()} pengaduan)",
            'data' => $list,
            'text' => "Ada {$pengaduan->count()} pengaduan yang belum selesai diproses.",
        ];
    }

    private function saranSolusi(string $q): array
    {
        // Deteksi kategori pengaduan
        $kategori = 'umum';
        if (str_contains($q, 'kebersihan') || str_contains($q, 'sampah')) $kategori = 'kebersihan';
        elseif (str_contains($q, 'keamanan') || str_contains($q, 'maling') || str_contains($q, 'pencuri')) $kategori = 'keamanan';
        elseif (str_contains($q, 'infrastruktur') || str_contains($q, 'jalan') || str_contains($q, 'lampu')) $kategori = 'infrastruktur';
        elseif (str_contains($q, 'fasilitas')) $kategori = 'fasilitas';
        elseif (str_contains($q, 'sosial') || str_contains($q, 'tetangga')) $kategori = 'sosial';

        $solusi = [
            'kebersihan' => [
                "🧹 **Saran Solusi untuk Pengaduan Kebersihan:**",
                "",
                "1. **Verifikasi lokasi:** Datangi langsung untuk melihat kondisi.",
                "2. **Koordinasi petugas kebersihan:** Hubungi petugas RT/RW untuk pembersihan extra.",
                "3. **Edukasi warga:** Ingatkan jadwal buang sampah dan pemilahan organik/anorganik.",
                "4. **Sosialisasi:** Buat pengumuman di grup WA / pasang pengumuman.",
                "5. **Sanksi (jika berulang):** Sesuai aturan RT, terapkan denda sosial.",
                "",
                "📌 *Tanggapi dalam 1-2 hari kerja.*",
            ],
            'keamanan' => [
                "🚨 **Saran Solusi untuk Pengaduan Keamanan:**",
                "",
                "1. **Respons cepat (URGENT):** Tanggapi maksimal 4 jam.",
                "2. **Koordinasi Satpam/Hansip:** Tingkatkan patroli area pelaporan.",
                "3. **Lapor pihak berwajib:** Jika ada indikasi kriminal, lapor Polsek terdekat.",
                "4. **Cek CCTV:** Periksa rekaman CCTV jika ada di area sekitar.",
                "5. **Sosialisasi siskamling:** Aktifkan siskamling jam-jam rawan.",
                "",
                "📌 *Prioritas TINGGI - tanggapi segera.*",
            ],
            'infrastruktur' => [
                "🛠️ **Saran Solusi untuk Pengaduan Infrastruktur:**",
                "",
                "1. **Survei kerusakan:** Datangi lokasi untuk asesmen tingkat kerusakan.",
                "2. **Estimasi biaya:** Hitung kebutuhan perbaikan (bahan, jasa).",
                "3. **Anggaran:** Cek ketersediaan dana IPL untuk perbaikan kecil/sedang.",
                "4. **Koordinasi pengurus:** Konsultasi dengan RT/RW dan pengurus lain.",
                "5. **Jika besar:** Buat proposal swadaya warga atau ajukan ke kelurahan.",
                "",
                "📌 *Update progres ke pelapor secara berkala.*",
            ],
            'fasilitas' => [
                "🏘️ **Saran Solusi untuk Pengaduan Fasilitas:**",
                "",
                "1. **Inventaris kerusakan:** Cek kondisi fasilitas yang dilaporkan.",
                "2. **Prioritaskan:** Dahulukan fasilitas yang dipakai banyak warga.",
                "3. **Perbaikan:** Koordinasi dengan tim teknis atau vendor.",
                "4. **Backup:** Sediakan alternatif sementara jika perlu.",
                "",
                "📌 *Sesuaikan respons dengan urgensi.*",
            ],
            'sosial' => [
                "🤝 **Saran Solusi untuk Pengaduan Sosial:**",
                "",
                "1. **Dengarkan kedua pihak:** Jangan langsung memihak.",
                "2. **Mediasi:** Adakan pertemuan netral dengan RT/RW.",
                "3. **Cari solusi win-win:** Diskusikan keberatan masing-masing.",
                "4. **Tindak lanjut:** Pastikan kesepakatan dijalankan.",
                "5. **Dokumentasi:** Catat hasil mediasi untuk referensi.",
                "",
                "📌 *Tangani dengan empati dan kerahasiaan.*",
            ],
            'umum' => [
                "💡 **Saran Umum Menangani Pengaduan:**",
                "",
                "1. **Respons cepat:** Tanggapi dalam 1-2 hari kerja.",
                "2. **Verifikasi:** Cek langsung ke lokasi/situasi.",
                "3. **Komunikasi:** Update progres ke pelapor.",
                "4. **Solutif:** Berikan solusi konkret, bukan janji.",
                "5. **Dokumentasi:** Simpan catatan untuk evaluasi.",
                "",
                "Tanyakan kategori spesifik (kebersihan, keamanan, dll) untuk saran lebih detail.",
            ],
        ];

        return [
            'type' => 'suggestion',
            'text' => implode("\n", $solusi[$kategori]),
        ];
    }

    private function ringkasanUmum(): array
    {
        $totalWarga = Warga::where('is_active', true)->count();
        $tagihanBulanIni = IplTagihan::where('bulan', now()->month)->where('tahun', now()->year);
        $totalTagihan = (clone $tagihanBulanIni)->count();
        $lunas = (clone $tagihanBulanIni)->where('status', 'sudah_bayar')->count();
        $belumBayar = (clone $tagihanBulanIni)->where('status', 'belum_bayar')->count();
        $pengaduanBaru = Pengaduan::whereIn('status', ['baru', 'diproses'])->count();
        $pendapatan = Pembayaran::where('status', 'success')
            ->whereMonth('created_at', now()->month)->sum('nominal');

        return [
            'type' => 'stats',
            'text' => "📊 **Ringkasan " . now()->locale('id')->translatedFormat('F Y') . "**\n\n" .
                "👥 Total warga aktif: **{$totalWarga} orang**\n" .
                "📋 Tagihan bulan ini: **{$totalTagihan}**\n" .
                "  ✅ Lunas: {$lunas}\n" .
                "  ⏳ Belum bayar: {$belumBayar}\n" .
                "💰 Pendapatan: **Rp " . number_format($pendapatan, 0, ',', '.') . "**\n" .
                "📢 Pengaduan aktif: **{$pengaduanBaru}**",
        ];
    }

    private function jumlahWarga(): array
    {
        $aktif = Warga::where('is_active', true)->count();
        $nonaktif = Warga::where('is_active', false)->count();
        $total = $aktif + $nonaktif;

        return [
            'type' => 'stats',
            'text' => "👥 **Data Warga**\n\n" .
                "Total: **{$total} KK**\n" .
                "  Aktif: {$aktif}\n" .
                "  Non-aktif: {$nonaktif}",
        ];
    }

    private function help(): array
    {
        return [
            'type' => 'text',
            'text' => "🤖 **Yang bisa saya bantu:**\n\n" .
                "📋 **Data Tagihan:**\n" .
                "• \"Siapa yang belum bayar bulan ini?\"\n" .
                "• \"Warga yang sudah lunas\"\n" .
                "• \"Total tunggakan\"\n\n" .
                "💰 **Keuangan:**\n" .
                "• \"Total pendapatan\"\n" .
                "• \"Dana kedukaan\"\n\n" .
                "📢 **Pengaduan:**\n" .
                "• \"Pengaduan baru\"\n" .
                "• \"Solusi pengaduan kebersihan\"\n" .
                "• \"Bagaimana mengatasi pengaduan keamanan?\"\n\n" .
                "📊 **Statistik:**\n" .
                "• \"Ringkasan bulan ini\"\n" .
                "• \"Jumlah warga\"",
        ];
    }
}
