import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../services/app_settings.dart';
import '../../utils/app_theme.dart';

class TermsScreen extends StatelessWidget {
  const TermsScreen({super.key});

  static const String _businessContact = 'Edi Prasetiyo';
  static const String _businessPhone = '082115525327';
  static const String _businessWhatsapp = '6282115525327';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Syarat & Ketentuan')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          _header(
            icon: Icons.gavel_rounded,
            title: 'Syarat & Ketentuan',
            subtitle: 'Berlaku efektif 20 Mei 2026',
          ),
          const SizedBox(height: 20),

          _intro(
            'Selamat datang di aplikasi ${AppSettings.appName}. '
            'Aplikasi ini dikelola khusus untuk warga Perumahan ${AppSettings.fullBrandName} '
            'untuk memudahkan pembayaran Iuran Pemeliharaan Lingkungan (IPL), uang kedukaan, '
            'dan kebutuhan administrasi RT/perumahan lainnya. '
            'Dengan menggunakan aplikasi ini, Anda menyetujui syarat dan ketentuan berikut.',
          ),

          _section(
            num: '1',
            title: 'Ketentuan Penggunaan',
            content:
                'Aplikasi ${AppSettings.appName} disediakan kepada Anda, pengguna, dengan syarat Anda menerima '
                'semua syarat, ketentuan, dan pemberitahuan yang tercantum di dalamnya. '
                'Aplikasi ini hanya boleh digunakan oleh warga yang terdaftar resmi sebagai penghuni '
                'Perumahan ${AppSettings.fullBrandName}.',
          ),

          _section(
            num: '2',
            title: 'Gambaran Umum Layanan',
            content:
                'Aplikasi ini menyediakan layanan: pembayaran IPL bulanan, pembayaran uang kedukaan, '
                'riwayat transaksi, pengaduan warga, berita & pengumuman komunitas, dan notifikasi reminder pembayaran. '
                'Semua transaksi pembayaran diproses melalui payment gateway Midtrans yang aman dan terpercaya, '
                'serta menggunakan mata uang Rupiah (IDR).',
          ),

          _section(
            num: '3',
            title: 'Pendaftaran Akun',
            content:
                'Anda harus mendaftar dengan memberikan nomor telepon dan kata sandi yang akurat. '
                'Anda bertanggung jawab penuh menjaga kerahasiaan kata sandi Anda. '
                'Pengelola RT/perumahan tidak bertanggung jawab atas akses tidak sah '
                'akibat kelalaian Anda menjaga kata sandi.',
          ),

          _section(
            num: '4',
            title: 'Pembayaran',
            content:
                'Semua pembayaran melalui aplikasi ini menggunakan mata uang Rupiah (IDR) dan diproses '
                'oleh PT. Midtrans (payment gateway resmi berlisensi Bank Indonesia). '
                'Halaman pembayaran ditampilkan di dalam aplikasi (tidak diarahkan ke website eksternal). '
                'Konfirmasi pembayaran akan dikirim melalui notifikasi aplikasi dan email '
                '(jika alamat email terdaftar).\n\n'
                'Biaya admin transaksi (jika ada) akan ditampilkan secara transparan '
                'sebelum Anda menyelesaikan pembayaran.\n\n'
                'PENTING: Biaya admin / Merchant Discount Rate (MDR) yang dipotong oleh Midtrans '
                'mengikuti tarif resmi yang ditetapkan oleh PT Midtrans selaku payment gateway. '
                'Tarif ini dapat berubah sewaktu-waktu sesuai kebijakan PT Midtrans tanpa pemberitahuan '
                'sebelumnya kepada pengguna. Pengelola aplikasi dan pembuat aplikasi TIDAK bertanggung jawab '
                'atas perubahan tarif tersebut. Untuk tarif terkini, silakan cek '
                'midtrans.com/id/biaya-transaksi.',
          ),

          _section(
            num: '5',
            title: 'Kebijakan Pengembalian Dana (Refund)',
            content:
                'Pengembalian dana hanya berlaku untuk kondisi berikut:\n\n'
                '• Terjadi kesalahan sistem yang menyebabkan double-charge\n'
                '• Pembayaran berhasil tercatat di Midtrans tapi status tagihan tidak ter-update '
                'di sistem dalam 24 jam\n'
                '• Pembayaran ke rekening yang salah karena kesalahan teknis sistem\n\n'
                'Permintaan refund dapat diajukan dalam waktu maksimal 7 hari setelah transaksi '
                'dengan menghubungi admin via WhatsApp. Refund diproses dalam 3-7 hari kerja '
                'ke rekening sumber pembayaran. '
                'Detail lengkap di menu "Kebijakan Pengembalian Dana".',
          ),

          _section(
            num: '6',
            title: 'Komunikasi Elektronik',
            content:
                'Dengan menggunakan aplikasi ini, Anda menyetujui menerima notifikasi push, '
                'email, atau pesan WhatsApp dari pengelola RT/perumahan mengenai pembayaran, '
                'pengumuman komunitas, dan reminder tagihan.',
          ),

          _section(
            num: '7',
            title: 'Kebijakan Privasi',
            content:
                'Data pribadi Anda (nama, nomor telepon, email, alamat hunian, foto profil) '
                'hanya digunakan untuk keperluan administrasi RT/perumahan. '
                'Kami tidak akan menjual, menyewakan, atau membagikan data Anda kepada pihak ketiga '
                'tanpa persetujuan Anda, kecuali diwajibkan oleh hukum yang berlaku di Indonesia. '
                'Data pembayaran ditangani oleh Midtrans sesuai standar PCI-DSS.',
          ),

          _section(
            num: '8',
            title: 'Modifikasi Layanan',
            content:
                'Pengelola berhak mengubah, memperbarui, atau menghentikan fitur aplikasi '
                'sewaktu-waktu tanpa pemberitahuan sebelumnya. '
                'Tarif IPL dan biaya lainnya dapat berubah sesuai keputusan rapat warga / RT.',
          ),

          _section(
            num: '9',
            title: 'Penafian (Disclaimer)',
            content:
                'Aplikasi ini disediakan "sebagaimana adanya". '
                'Pengelola berusaha maksimal menjaga akurasi data dan ketersediaan layanan, '
                'namun tidak menjamin layanan selalu bebas dari kesalahan teknis. '
                'Pengelola tidak bertanggung jawab atas kerugian tidak langsung yang timbul '
                'dari penggunaan aplikasi ini.',
          ),

          _section(
            num: '10',
            title: 'Hukum yang Berlaku',
            content:
                'Syarat & Ketentuan ini diatur dan ditafsirkan berdasarkan hukum '
                'Republik Indonesia. Sengketa yang timbul akan diselesaikan secara musyawarah, '
                'dan jika tidak tercapai mufakat akan diselesaikan melalui pengadilan negeri yang berwenang.',
          ),

          _section(
            num: '11',
            title: 'Kontak Bisnis',
            content:
                'Untuk pertanyaan, keluhan, atau bantuan terkait aplikasi dan transaksi, hubungi:\n\n'
                'Nama : $_businessContact\n'
                'WhatsApp : $_businessPhone\n'
                'Aplikasi : ${AppSettings.appName}\n'
                'Perumahan : ${AppSettings.fullBrandName}\n\n'
                'Kami akan merespons dalam jam operasional 10:00–17:00 WIB.',
          ),

          const SizedBox(height: 24),

          // CTA tombol WA
          ElevatedButton.icon(
            onPressed: _hubungiAdmin,
            icon: const Icon(Icons.chat_bubble_outline),
            label: const Text('Hubungi via WhatsApp'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF25D366),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 12),
          Center(
            child: Text(
              '© 2026 Perumahan ${AppSettings.fullBrandName}\nVersi dokumen: 1.0',
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppTheme.textSecondary, fontSize: 11),
            ),
          ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  Future<void> _hubungiAdmin() async {
    final msg = Uri.encodeComponent(
      'Halo Admin ${AppSettings.appName}, saya ingin bertanya seputar aplikasi.',
    );
    final url = Uri.parse('https://wa.me/$_businessWhatsapp?text=$msg');
    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    }
  }

  Widget _header({required IconData icon, required String title, required String subtitle}) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [AppSettings.primaryDarkColor, AppSettings.primaryColor],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Icon(icon, color: Colors.white, size: 36),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
                const SizedBox(height: 4),
                Text(subtitle, style: const TextStyle(color: Colors.white70, fontSize: 12)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _intro(String text) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppSettings.primaryLightColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppSettings.primaryColor.withOpacity(0.2)),
      ),
      child: Text(text, style: const TextStyle(fontSize: 13, height: 1.5)),
    );
  }

  Widget _section({required String num, required String title, required String content}) {
    return Padding(
      padding: const EdgeInsets.only(top: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 28,
                height: 28,
                decoration: BoxDecoration(
                  color: AppSettings.primaryColor,
                  shape: BoxShape.circle,
                ),
                alignment: Alignment.center,
                child: Text(num,
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(title,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, height: 1.4)),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.only(left: 38),
            child: Text(content,
                style: const TextStyle(fontSize: 13, height: 1.6, color: Color(0xFF424242))),
          ),
        ],
      ),
    );
  }
}
