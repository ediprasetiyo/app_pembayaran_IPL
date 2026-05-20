import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../services/app_settings.dart';
import '../../utils/app_theme.dart';

class RefundPolicyScreen extends StatelessWidget {
  const RefundPolicyScreen({super.key});

  static const String _businessPhone = '082115525327';
  static const String _businessWhatsapp = '6282115525327';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Kebijakan Pengembalian Dana')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          _header(),
          const SizedBox(height: 20),

          _infoBox(
            icon: Icons.info_outline,
            color: Colors.blue,
            title: 'Ringkasan',
            content:
                'Aplikasi ${AppSettings.appName} berkomitmen menjaga setiap transaksi pembayaran '
                'IPL dan iuran lainnya aman. Jika terjadi masalah pembayaran, kami akan memproses '
                'pengembalian dana sesuai ketentuan di bawah.',
          ),

          const SizedBox(height: 16),

          _section(
            icon: Icons.check_circle_outline,
            iconColor: Colors.green,
            title: 'Kondisi Refund Diberikan',
            items: [
              'Terjadi pembayaran ganda (double-charge) akibat kesalahan sistem.',
              'Status tagihan tidak ter-update menjadi "Lunas" lebih dari 24 jam setelah pembayaran berhasil di Midtrans.',
              'Pembayaran terdebet di rekening Anda namun gagal tercatat di sistem kami.',
              'Pembayaran ke akun warga yang salah karena kesalahan teknis sistem (bukan kesalahan input warga).',
              'Pembatalan transaksi yang sudah disetujui pengelola karena alasan force majeure.',
            ],
          ),

          _section(
            icon: Icons.cancel_outlined,
            iconColor: Colors.red,
            title: 'Kondisi Refund TIDAK Diberikan',
            items: [
              'Salah memilih tagihan yang dibayar (mis. bayar bulan Maret padahal mau Februari).',
              'Berubah pikiran setelah pembayaran sukses.',
              'Tagihan yang sudah jatuh tempo dan ada denda keterlambatan — refund dapat dilakukan hanya untuk nominal pokok, denda tidak dikembalikan.',
              'Permintaan refund lebih dari 7 hari setelah tanggal transaksi.',
              'Pembayaran uang kedukaan yang sudah disetorkan ke pos kas — sesuai musyawarah RT, ini tidak dapat di-refund.',
            ],
          ),

          _section(
            icon: Icons.timer_outlined,
            iconColor: Colors.orange,
            title: 'Proses & Waktu Pemrosesan',
            items: [
              '1. Ajukan permintaan refund via WhatsApp ke admin maksimal 7 hari setelah transaksi.',
              '2. Sertakan bukti: screenshot transaksi, Order ID, dan nominal.',
              '3. Admin akan verifikasi dengan Midtrans dan sistem dalam 1-3 hari kerja.',
              '4. Jika disetujui, dana akan dikembalikan ke rekening sumber pembayaran dalam 3-7 hari kerja.',
              '5. Anda akan menerima notifikasi konfirmasi setelah refund selesai diproses.',
            ],
          ),

          _section(
            icon: Icons.payments_outlined,
            iconColor: AppSettings.primaryColor,
            title: 'Biaya Refund',
            items: [
              'Refund nominal pokok: 100% (full refund) — dikembalikan oleh Midtrans selaku payment gateway.',
              'Biaya admin transaksi Midtrans (jika ada): tidak dapat di-refund (sudah dipotong gateway saat transaksi).',
              'Biaya transfer bank untuk proses refund: ditanggung oleh Midtrans selaku penyedia jasa payment gateway, bukan oleh pengelola RT/perumahan.',
            ],
          ),

          const SizedBox(height: 20),

          // Kontak
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [AppSettings.primaryDarkColor, AppSettings.primaryColor],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  children: [
                    Icon(Icons.support_agent, color: Colors.white, size: 22),
                    SizedBox(width: 8),
                    Text('Ajukan Refund / Bantuan',
                        style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 15)),
                  ],
                ),
                const SizedBox(height: 8),
                const Text(
                  'Hubungi admin dengan sertakan Order ID transaksi.\n'
                  'Jam operasional: 10:00 - 17:00 WIB.',
                  style: TextStyle(color: Colors.white70, fontSize: 12, height: 1.5),
                ),
                const SizedBox(height: 12),
                ElevatedButton.icon(
                  onPressed: _hubungiAdmin,
                  icon: const Icon(Icons.chat_bubble_outline),
                  label: Text('Chat: $_businessPhone'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF25D366),
                    foregroundColor: Colors.white,
                    minimumSize: const Size(double.infinity, 44),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),
          Center(
            child: Text(
              'Berlaku efektif 20 Mei 2026\n© 2026 Perumahan ${AppSettings.fullBrandName}',
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
      'Halo Admin ${AppSettings.appName}, saya ingin mengajukan refund pembayaran.\n\n'
      'Order ID: [isi order ID]\nNominal: [isi nominal]\nAlasan: [isi alasan]',
    );
    final url = Uri.parse('https://wa.me/$_businessWhatsapp?text=$msg');
    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    }
  }

  Widget _header() {
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
          const Icon(Icons.assignment_return_outlined, color: Colors.white, size: 36),
          const SizedBox(width: 16),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Kebijakan Pengembalian Dana',
                    style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                SizedBox(height: 4),
                Text('Refund Policy — IPL & Iuran',
                    style: TextStyle(color: Colors.white70, fontSize: 12)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _infoBox({
    required IconData icon,
    required Color color,
    required String title,
    required String content,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: color.withOpacity(0.08),
        borderRadius: BorderRadius.circular(12),
        border: Border(left: BorderSide(color: color, width: 4)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: color, size: 18),
              const SizedBox(width: 6),
              Text(title, style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 13)),
            ],
          ),
          const SizedBox(height: 6),
          Text(content, style: const TextStyle(fontSize: 12.5, height: 1.5)),
        ],
      ),
    );
  }

  Widget _section({
    required IconData icon,
    required Color iconColor,
    required String title,
    required List<String> items,
  }) {
    return Padding(
      padding: const EdgeInsets.only(top: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: iconColor, size: 22),
              const SizedBox(width: 8),
              Expanded(
                child: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
              ),
            ],
          ),
          const SizedBox(height: 10),
          ...items.map((it) => Padding(
                padding: const EdgeInsets.only(left: 8, top: 6, bottom: 6),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      margin: const EdgeInsets.only(top: 6, right: 10),
                      width: 5,
                      height: 5,
                      decoration: BoxDecoration(color: iconColor, shape: BoxShape.circle),
                    ),
                    Expanded(child: Text(it, style: const TextStyle(fontSize: 13, height: 1.6))),
                  ],
                ),
              )),
        ],
      ),
    );
  }
}
