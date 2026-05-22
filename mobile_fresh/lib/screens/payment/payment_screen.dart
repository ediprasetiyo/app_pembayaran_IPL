import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../../models/ipl_model.dart';
import '../../providers/ipl_provider.dart';
import '../../services/payment_icons.dart';
import '../../utils/app_theme.dart';

class PaymentScreen extends StatefulWidget {
  const PaymentScreen({super.key});

  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final _currency = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() {
      // Refresh data saat ganti tab
      if (_tabController.indexIsChanging) return;
      if (_tabController.index == 0) {
        context.read<IplProvider>().loadTunggakan();
      } else {
        context.read<IplProvider>().loadRiwayat();
      }
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<IplProvider>().loadTunggakan();
      context.read<IplProvider>().loadRiwayat();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.payment),
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white60,
          indicatorColor: Colors.white,
          tabs: [
            Tab(text: l10n.tagihan),
            Tab(text: l10n.riwayat),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _TagihanTab(currency: _currency),
          _RiwayatTab(currency: _currency),
        ],
      ),
    );
  }
}

class _TagihanTab extends StatelessWidget {
  final NumberFormat currency;

  const _TagihanTab({required this.currency});

  @override
  Widget build(BuildContext context) {
    final iplProvider = context.watch<IplProvider>();
    final l10n = AppLocalizations.of(context)!;

    if (iplProvider.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (iplProvider.tunggakan.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.check_circle_outline, size: 64, color: AppTheme.successColor),
            const SizedBox(height: 16),
            Text(l10n.noTunggakan,
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
            Text(l10n.semuaLunas, style: const TextStyle(color: AppTheme.textSecondary)),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => context.read<IplProvider>().loadTunggakan(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: iplProvider.tunggakan.length,
        itemBuilder: (context, index) {
          final tagihan = iplProvider.tunggakan[index];
          return _TagihanItem(tagihan: tagihan, currency: currency);
        },
      ),
    );
  }
}

class _TagihanItem extends StatelessWidget {
  final TagihanModel tagihan;
  final NumberFormat currency;

  const _TagihanItem({required this.tagihan, required this.currency});

  @override
  Widget build(BuildContext context) {
    final isLate = tagihan.isTerlambat;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  '${tagihan.namaBulan} ${tagihan.tahun}',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: isLate
                        ? AppTheme.errorColor.withOpacity(0.1)
                        : AppTheme.warningColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    isLate ? 'Terlambat' : 'Belum Bayar',
                    style: TextStyle(
                      color: isLate ? AppTheme.errorColor : AppTheme.warningColor,
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
            const Divider(height: 16),
            _row('IPL', currency.format(tagihan.nominal)),
            if (tagihan.denda > 0)
              _row('Denda (5%)', currency.format(tagihan.denda),
                  valueColor: AppTheme.errorColor),
            const SizedBox(height: 4),
            _row('Total', currency.format(tagihan.totalTagihan),
                isBold: true),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Jatuh tempo: ${tagihan.jatuhTempo}',
                    style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
                  ),
                ),
                ElevatedButton(
                  onPressed: () => _bayar(context, tagihan),
                  style: ElevatedButton.styleFrom(
                    minimumSize: const Size(100, 36),
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                  ),
                  child: const Text('Bayar'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _row(String label, String value, {bool isBold = false, Color? valueColor}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(
            color: AppTheme.textSecondary,
            fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
          )),
          Text(value, style: TextStyle(
            fontWeight: isBold ? FontWeight.bold : FontWeight.w500,
            color: valueColor,
          )),
        ],
      ),
    );
  }

  Future<void> _bayar(BuildContext context, TagihanModel tagihan) async {
    // === STEP 1: Load list payment methods + biaya admin per method ===
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );
    final methods = await context.read<IplProvider>().getPaymentMethods(tagihan.id);
    if (!context.mounted) return;
    Navigator.pop(context); // close loading

    if (methods == null || methods.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Belum ada metode pembayaran yang aktif. Hubungi admin.'),
          backgroundColor: AppTheme.errorColor,
        ),
      );
      return;
    }

    // === STEP 2: Tampilkan modal pilih payment method ===
    final selectedMethod = await _showMethodPicker(context, tagihan, methods);
    if (selectedMethod == null) return;

    // === STEP 3: Loading + call bayar dengan method yg dipilih ===
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );

    final result = await context.read<IplProvider>().bayarTagihan(
      tagihan.id,
      paymentMethod: selectedMethod,
    );
    if (!context.mounted) return;
    Navigator.pop(context); // close loading

    if (result == null) {
      final err = context.read<IplProvider>().error ?? 'Gagal memproses pembayaran';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(err), backgroundColor: AppTheme.errorColor),
      );
      return;
    }

    final redirectUrl = result['redirect_url'] as String?;
    if (redirectUrl == null || redirectUrl.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Pembayaran tidak dapat diproses (URL kosong). Hubungi admin.'),
          backgroundColor: AppTheme.errorColor,
        ),
      );
      return;
    }

    // Ambil pembayaran ID untuk polling status
    int? pembayaranId;
    final p = result['pembayaran'];
    if (p is Map && p['id'] != null) {
      final pid = p['id'];
      if (pid is int) {
        pembayaranId = pid;
      } else if (pid is String) {
        pembayaranId = int.tryParse(pid);
      }
    }

    // Breakdown sudah ditampilkan di method picker — langsung buka WebView.
    if (!context.mounted) return;
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => _MidtransWebView(
          url: redirectUrl,
          orderId: result['pembayaran']?['order_id'] ?? '',
          pembayaranId: pembayaranId,
        ),
      ),
    );
  }

  /// Tampilkan modal pilih payment method dengan biaya admin masing-masing.
  /// Return: payment method code yang dipilih, atau null kalau batal.
  Future<String?> _showMethodPicker(
    BuildContext context,
    TagihanModel tagihan,
    List<Map<String, dynamic>> methods,
  ) async {
    final fmt = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

    // Group by category
    final grouped = <String, List<Map<String, dynamic>>>{};
    for (final m in methods) {
      final cat = (m['category'] ?? 'Lainnya').toString();
      grouped.putIfAbsent(cat, () => []).add(m);
    }

    return showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.85,
        maxChildSize: 0.95,
        minChildSize: 0.5,
        expand: false,
        builder: (ctx, scrollController) => Column(
          children: [
            // Handle bar
            const SizedBox(height: 10),
            Container(
              width: 40, height: 4,
              decoration: BoxDecoration(
                color: Colors.grey.shade300,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            // Header
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 14, 20, 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.payment, color: AppTheme.primaryColor),
                      const SizedBox(width: 8),
                      const Text(
                        'Pilih Metode Pembayaran',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Nominal: ${fmt.format(tagihan.totalTagihan)} · '
                    'Biaya admin sesuai tarif PT Midtrans',
                    style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            // List per kategori
            Expanded(
              child: ListView(
                controller: scrollController,
                padding: const EdgeInsets.symmetric(vertical: 8),
                children: [
                  for (final entry in grouped.entries) ...[
                    Padding(
                      padding: const EdgeInsets.fromLTRB(20, 12, 20, 6),
                      child: Text(
                        entry.key.toUpperCase(),
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: AppTheme.textSecondary,
                          letterSpacing: 0.8,
                        ),
                      ),
                    ),
                    ...entry.value.map((m) {
                      final code = m['code'].toString();
                      final name = m['name'].toString();
                      final icon = m['icon']?.toString() ?? '💳';
                      final logoUrl = m['logo_url']?.toString() ?? '';
                      final feeAmount = (m['fee_amount'] ?? 0) is num ? (m['fee_amount'] as num).toInt() : 0;
                      final totalAmount = (m['total_amount'] ?? 0) is num ? (m['total_amount'] as num).toInt() : 0;
                      final feeLabel = m['fee_label']?.toString() ?? '';

                      return InkWell(
                        onTap: () => Navigator.pop(ctx, code),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                          child: Row(
                            children: [
                              // Brand logo (network) dengan emoji fallback kalau gagal load
                              SizedBox(
                                width: 44,
                                height: 32,
                                child: logoUrl.isNotEmpty
                                    ? Image.network(
                                        logoUrl,
                                        fit: BoxFit.contain,
                                        errorBuilder: (_, __, ___) => Center(
                                          child: Text(icon, style: const TextStyle(fontSize: 24)),
                                        ),
                                        loadingBuilder: (_, child, progress) {
                                          if (progress == null) return child;
                                          return Center(
                                            child: Text(icon, style: const TextStyle(fontSize: 24)),
                                          );
                                        },
                                      )
                                    : Center(
                                        child: Text(icon, style: const TextStyle(fontSize: 24)),
                                      ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(name, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                                    const SizedBox(height: 2),
                                    Text(
                                      'Biaya admin: ${fmt.format(feeAmount)} ($feeLabel)',
                                      style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary),
                                    ),
                                  ],
                                ),
                              ),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(
                                    fmt.format(totalAmount),
                                    style: TextStyle(
                                      color: AppTheme.primaryColor,
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                    ),
                                  ),
                                  const Text(
                                    'Total bayar',
                                    style: TextStyle(fontSize: 10, color: AppTheme.textSecondary),
                                  ),
                                ],
                              ),
                              const SizedBox(width: 4),
                              const Icon(Icons.chevron_right, color: AppTheme.textSecondary, size: 20),
                            ],
                          ),
                        ),
                      );
                    }),
                  ],
                  const SizedBox(height: 12),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    child: Text(
                      'ℹ️ Biaya admin adalah tarif resmi PT Midtrans selaku payment gateway. '
                      'Tarif dapat berubah sewaktu-waktu sesuai kebijakan Midtrans.',
                      style: TextStyle(fontSize: 10, color: Colors.grey.shade600, height: 1.5),
                      textAlign: TextAlign.center,
                    ),
                  ),
                  const SizedBox(height: 16),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  static Widget _breakdownRow(String label, String value, {bool bold = false, bool big = false, Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Expanded(
            child: Text(
              label,
              style: TextStyle(
                fontSize: big ? 14 : 13,
                fontWeight: bold ? FontWeight.bold : FontWeight.normal,
                color: color,
              ),
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: big ? 16 : 13,
              fontWeight: bold ? FontWeight.bold : FontWeight.w600,
              color: bold ? AppTheme.primaryColor : color,
            ),
          ),
        ],
      ),
    );
  }
}

class _MidtransWebView extends StatefulWidget {
  final String url;
  final String orderId;
  final int? pembayaranId;

  const _MidtransWebView({required this.url, required this.orderId, this.pembayaranId});

  @override
  State<_MidtransWebView> createState() => _MidtransWebViewState();
}

class _MidtransWebViewState extends State<_MidtransWebView> {
  late final WebViewController _controller;
  bool _closing = false;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(NavigationDelegate(
        onPageStarted: (url) {
          final u = url.toLowerCase();
          // Match berbagai pattern URL finish/complete Midtrans
          if (u.contains('selesai') ||
              u.contains('finish') ||
              u.contains('settlement') ||
              u.contains('success') ||
              u.contains('thank')) {
            _onPaymentComplete(true);
          } else if (u.contains('gagal') ||
              u.contains('failed') ||
              u.contains('error') ||
              u.contains('expired')) {
            _onPaymentComplete(false);
          }
        },
      ))
      ..loadRequest(Uri.parse(widget.url));
  }

  void _showQrisHelp() {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: Row(
          children: [
            Icon(Icons.qr_code_2, color: AppTheme.primaryColor),
            const SizedBox(width: 8),
            const Text('Cara Bayar QRIS'),
          ],
        ),
        content: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: const [
              Text('1. Klik metode "QRIS" / "GoPay" / "ShopeePay"'),
              SizedBox(height: 8),
              Text('2. Setelah QR Code muncul, screenshot layar HP (Power + Volume Down).'),
              SizedBox(height: 8),
              Text('3. Buka e-wallet (DANA / GoPay / OVO / ShopeePay / m-Banking).'),
              SizedBox(height: 8),
              Text('4. Pilih menu Scan QR → pilih "Dari Galeri" → pilih screenshot tadi.'),
              SizedBox(height: 8),
              Text('5. Konfirmasi pembayaran di e-wallet.'),
              SizedBox(height: 12),
              Text(
                'ℹ️ Tombol "Download QR" di halaman Midtrans memang tidak aktif di dalam aplikasi — gunakan screenshot.',
                style: TextStyle(fontStyle: FontStyle.italic, fontSize: 12),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Mengerti'),
          ),
        ],
      ),
    );
  }

  Future<void> _onPaymentComplete(bool success) async {
    if (_closing) return;
    _closing = true;
    await _checkAndClose(autoSuccess: success);
  }

  /// Manual close: cek status real ke server dulu
  Future<void> _handleManualClose() async {
    if (_closing) return;
    _closing = true;
    await _checkAndClose();
  }

  Future<void> _checkAndClose({bool? autoSuccess}) async {
    final provider = context.read<IplProvider>();

    // Tampilkan loading mini
    if (mounted) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (_) => const Center(child: CircularProgressIndicator()),
      );
    }

    // Poll status dari server (server kadang sudah dapat callback Midtrans)
    String? statusReal;
    if (widget.pembayaranId != null) {
      statusReal = await provider.cekStatusPembayaran(widget.pembayaranId!);
    }

    // Refresh semua data tagihan
    await provider.refreshAll();

    if (!mounted) return;
    Navigator.pop(context); // close loading
    Navigator.pop(context); // close WebView

    // Tentukan message berdasarkan status real
    String msg;
    Color bgColor;
    if (statusReal == 'success') {
      msg = '✅ Pembayaran BERHASIL! Tagihan sudah lunas.';
      bgColor = AppTheme.successColor;
    } else if (statusReal == 'pending') {
      msg = '⏳ Pembayaran sedang diproses. Tarik ke bawah di tab Riwayat untuk refresh status.';
      bgColor = Colors.orange;
    } else if (statusReal == 'failed' || statusReal == 'expired' || statusReal == 'cancel') {
      msg = '❌ Pembayaran gagal/dibatalkan. Silakan coba lagi.';
      bgColor = AppTheme.errorColor;
    } else {
      // Status tidak diketahui — fallback ke autoSuccess
      msg = (autoSuccess ?? false)
          ? '⏳ Pembayaran selesai. Status sedang disinkronkan, cek tab Riwayat dalam beberapa detik.'
          : 'ℹ️ WebView ditutup. Cek tab Riwayat untuk status terbaru.';
      bgColor = AppTheme.primaryColor;
    }

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(msg),
        backgroundColor: bgColor,
        duration: const Duration(seconds: 5),
      ));
    }
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: () async {
        await _handleManualClose();
        return false;
      },
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Pembayaran Midtrans'),
          leading: IconButton(
            icon: const Icon(Icons.close),
            onPressed: _handleManualClose,
            tooltip: 'Tutup & cek status',
          ),
          actions: [
            IconButton(
              icon: const Icon(Icons.help_outline),
              onPressed: _showQrisHelp,
              tooltip: 'Bantuan QRIS',
            ),
            IconButton(
              icon: const Icon(Icons.refresh),
              onPressed: _handleManualClose,
              tooltip: 'Cek status pembayaran',
            ),
          ],
        ),
        body: Column(
          children: [
            // Banner instruksi QRIS / e-wallet
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              color: const Color(0xFFFFF8E1),
              child: Row(
                children: [
                  const Icon(Icons.lightbulb_outline, size: 18, color: Color(0xFFB8860B)),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Pilih QRIS / GoPay → Screenshot QR (Power + Vol Down) → scan via e-wallet.',
                      style: TextStyle(
                        fontSize: 11.5,
                        color: Colors.brown.shade800,
                        height: 1.3,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            Expanded(child: WebViewWidget(controller: _controller)),
          ],
        ),
      ),
    );
  }
}

class _RiwayatTab extends StatefulWidget {
  final NumberFormat currency;

  const _RiwayatTab({required this.currency});

  @override
  State<_RiwayatTab> createState() => _RiwayatTabState();
}

class _RiwayatTabState extends State<_RiwayatTab> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<IplProvider>().loadRiwayat();
      // Force refresh PaymentIcons supaya logo brand muncul (network call)
      // Setelah complete, setState supaya widget rebuild dengan logo terbaru
      PaymentIcons.refreshFromNetwork().then((_) {
        if (mounted) setState(() {});
      });
    });
  }

  @override
  Widget build(BuildContext context) {
    final iplProvider = context.watch<IplProvider>();

    if (iplProvider.riwayat.isEmpty) {
      return RefreshIndicator(
        onRefresh: () => context.read<IplProvider>().loadRiwayat(),
        child: ListView(
          children: [
            const SizedBox(height: 100),
            Icon(Icons.receipt_long_outlined, size: 64, color: Colors.grey.shade300),
            const SizedBox(height: 12),
            const Center(
              child: Text(
                'Belum ada riwayat pembayaran',
                style: TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
            const Center(
              child: Text(
                'Tarik ke bawah untuk refresh',
                style: TextStyle(color: Color(0xFF9E9E9E), fontSize: 12),
              ),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => context.read<IplProvider>().loadRiwayat(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: iplProvider.riwayat.length,
        itemBuilder: (context, index) {
          final item = iplProvider.riwayat[index];
          return _RiwayatCard(
            pembayaran: item,
            currency: widget.currency,
            onTap: () => _showDetailPembayaran(context, item),
          );
        },
      ),
    );
  }

  void _showDetailPembayaran(BuildContext context, PembayaranModel pembayaran) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (_) => _DetailPembayaranSheet(
        pembayaran: pembayaran,
        currency: widget.currency,
      ),
    );
  }
}

class _RiwayatCard extends StatelessWidget {
  final PembayaranModel pembayaran;
  final NumberFormat currency;
  final VoidCallback? onTap;

  const _RiwayatCard({
    required this.pembayaran,
    required this.currency,
    this.onTap,
  });

  Color get statusColor {
    switch (pembayaran.status) {
      case 'success': return AppTheme.successColor;
      case 'pending': return AppTheme.warningColor;
      case 'failed':
      case 'expired':
      case 'cancel': return AppTheme.errorColor;
      default: return AppTheme.textSecondary;
    }
  }

  IconData get statusIcon {
    switch (pembayaran.status) {
      case 'success': return Icons.check_circle;
      case 'pending': return Icons.schedule;
      case 'failed':
      case 'expired':
      case 'cancel': return Icons.cancel;
      default: return Icons.help;
    }
  }

  String get statusLabel {
    switch (pembayaran.status) {
      case 'success': return 'Berhasil';
      case 'pending': return 'Menunggu';
      case 'failed': return 'Gagal';
      case 'expired': return 'Kedaluwarsa';
      case 'cancel': return 'Dibatalkan';
      default: return pembayaran.status;
    }
  }

  String get methodLabel {
    final m = pembayaran.paymentType;
    if (m == null || m.isEmpty) return 'Pembayaran';
    // Manual labels untuk method non-Midtrans (icon sudah tampil terpisah, jadi tanpa emoji)
    final manual = {
      'tunai': 'Tunai',
      'transfer': 'Transfer Manual',
      'bank_transfer': 'Bank Transfer',
    }[m];
    if (manual != null) return manual;
    // Untuk method Midtrans, pakai nama brand dari PaymentIcons (e.g., "GoPay", "DANA")
    return PaymentIcons.name(m);
  }

  /// Widget untuk display logo brand (network image dengan fallback emoji)
  Widget buildMethodIcon({double size = 28}) {
    final code = pembayaran.paymentType;
    final logoUrl = PaymentIcons.logoUrl(code);
    if (logoUrl != null) {
      return SizedBox(
        width: size + 8,
        height: size,
        child: Image.network(
          logoUrl,
          fit: BoxFit.contain,
          errorBuilder: (_, __, ___) => Center(
            child: Text(PaymentIcons.emojiIcon(code), style: TextStyle(fontSize: size * 0.85)),
          ),
        ),
      );
    }
    // Fallback emoji untuk method non-Midtrans atau gagal load
    final manualIcon = {
      'tunai': '💵',
      'transfer': '🏦',
      'bank_transfer': '🏦',
    }[code];
    return Text(
      manualIcon ?? PaymentIcons.emojiIcon(code),
      style: TextStyle(fontSize: size * 0.85),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Logo brand payment method + status indicator overlay
            Stack(
              clipBehavior: Clip.none,
              children: [
                Container(
                  width: 48,
                  height: 44,
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: Colors.grey.shade200),
                  ),
                  alignment: Alignment.center,
                  child: buildMethodIcon(size: 28),
                ),
                Positioned(
                  bottom: -2,
                  right: -2,
                  child: Container(
                    width: 18,
                    height: 18,
                    decoration: BoxDecoration(
                      color: statusColor,
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 2),
                    ),
                    child: Icon(statusIcon, color: Colors.white, size: 10),
                  ),
                ),
              ],
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          methodLabel,
                          style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: statusColor.withOpacity(0.12),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          statusLabel,
                          style: TextStyle(
                            color: statusColor,
                            fontSize: 10,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    pembayaran.orderId,
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppTheme.textSecondary,
                      fontFamily: 'monospace',
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    currency.format(pembayaran.nominal),
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                      color: AppTheme.primaryColor,
                    ),
                  ),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: AppTheme.textSecondary),
          ],
        ),
      ),
      ),
    );
  }
}

// ============== DETAIL PEMBAYARAN SHEET ==============
class _DetailPembayaranSheet extends StatelessWidget {
  final PembayaranModel pembayaran;
  final NumberFormat currency;

  const _DetailPembayaranSheet({
    required this.pembayaran,
    required this.currency,
  });

  Color get statusColor {
    switch (pembayaran.status) {
      case 'success': return AppTheme.successColor;
      case 'pending': return AppTheme.warningColor;
      case 'failed':
      case 'expired':
      case 'cancel': return AppTheme.errorColor;
      default: return AppTheme.textSecondary;
    }
  }

  IconData get statusIcon {
    switch (pembayaran.status) {
      case 'success': return Icons.check_circle;
      case 'pending': return Icons.schedule;
      default: return Icons.cancel;
    }
  }

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      initialChildSize: 0.7,
      maxChildSize: 0.95,
      minChildSize: 0.5,
      expand: false,
      builder: (_, controller) => SingleChildScrollView(
        controller: controller,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Drag handle
            const SizedBox(height: 12),
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),

            // Header status (full-width gradient)
            Container(
              margin: const EdgeInsets.all(16),
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    statusColor.withOpacity(0.12),
                    statusColor.withOpacity(0.04),
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: statusColor.withOpacity(0.25)),
              ),
              child: Column(
                children: [
                  Container(
                    width: 64,
                    height: 64,
                    decoration: BoxDecoration(
                      color: statusColor.withOpacity(0.15),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(statusIcon, color: statusColor, size: 36),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    pembayaran.statusLabel,
                    style: TextStyle(
                      color: statusColor,
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    currency.format(pembayaran.nominal),
                    style: const TextStyle(
                      fontSize: 26,
                      fontWeight: FontWeight.bold,
                      color: AppTheme.textPrimary,
                    ),
                  ),
                ],
              ),
            ),

            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Detail Transaksi',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  const SizedBox(height: 10),
                  _row('Order ID', pembayaran.orderId, isMono: true),
                  // Metode Pembayaran: logo brand + nama (e.g., logo DANA + "DANA")
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 6),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const SizedBox(
                          width: 130,
                          child: Text('Metode Pembayaran',
                              style: TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
                        ),
                        const Text(': ', style: TextStyle(color: AppTheme.textSecondary)),
                        Expanded(
                          child: Row(
                            children: [
                              _buildMethodLogo(pembayaran.paymentType, size: 24),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  pembayaran.methodLabel,
                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (pembayaran.midtransTransactionId != null)
                    _row('Transaction ID', pembayaran.midtransTransactionId!, isMono: true),
                  _row('Tanggal Transaksi',
                      DateFormat('d MMMM yyyy, HH:mm', 'id_ID').format(pembayaran.createdAt)),
                  if (pembayaran.updatedAt != null && pembayaran.status == 'success')
                    _row('Tanggal Lunas',
                        DateFormat('d MMMM yyyy, HH:mm', 'id_ID').format(pembayaran.updatedAt!)),

                  if (pembayaran.tagihan != null) ...[
                    const SizedBox(height: 16),
                    const Text('Detail Tagihan',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 10),
                    _row(
                      'Jenis',
                      pembayaran.tagihan!.jenis == 'kedukaan'
                          ? 'Uang Kedukaan'
                          : 'IPL Bulanan',
                    ),
                    _row(
                      'Periode',
                      '${pembayaran.tagihan!.namaBulan} ${pembayaran.tagihan!.tahun}',
                    ),
                    _row('Nominal', currency.format(pembayaran.tagihan!.nominal)),
                  ],

                  if (pembayaran.catatan != null && pembayaran.catatan!.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    const Text('Catatan',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                    const SizedBox(height: 6),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: AppTheme.backgroundColor,
                        borderRadius: BorderRadius.circular(10),
                        border: Border(
                          left: BorderSide(color: AppTheme.primaryColor.withOpacity(0.5), width: 3),
                        ),
                      ),
                      child: Text(
                        pembayaran.catatan!,
                        style: const TextStyle(fontSize: 13, height: 1.5),
                      ),
                    ),
                  ],

                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: () => Navigator.pop(context),
                      icon: const Icon(Icons.check, size: 18),
                      label: const Text('Tutup'),
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _row(String label, String value, {bool isMono = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(
              label,
              style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary),
            ),
          ),
          const Text(': ', style: TextStyle(color: AppTheme.textSecondary)),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                fontFamily: isMono ? 'monospace' : null,
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Build logo brand untuk method (network image dengan emoji fallback)
  static Widget _buildMethodLogo(String? code, {double size = 24}) {
    final logoUrl = PaymentIcons.logoUrl(code);
    if (logoUrl != null) {
      return Container(
        width: size + 6,
        height: size,
        padding: const EdgeInsets.all(1),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(4),
          border: Border.all(color: Colors.grey.shade200),
        ),
        child: Image.network(
          logoUrl,
          fit: BoxFit.contain,
          errorBuilder: (_, __, ___) => Center(
            child: Text(PaymentIcons.emojiIcon(code), style: TextStyle(fontSize: size * 0.85)),
          ),
        ),
      );
    }
    final manualIcon = {'tunai': '💵', 'transfer': '🏦', 'bank_transfer': '🏦', 'lainnya': '📋'}[code];
    return Text(
      manualIcon ?? PaymentIcons.emojiIcon(code),
      style: TextStyle(fontSize: size * 0.85),
    );
  }
}
