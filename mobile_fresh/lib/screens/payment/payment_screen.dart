import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:webview_flutter/webview_flutter.dart';

import '../../models/ipl_model.dart';
import '../../providers/ipl_provider.dart';
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
    final result = await context.read<IplProvider>().bayarTagihan(tagihan.id);
    if (!context.mounted || result == null) return;

    final redirectUrl = result['redirect_url'] as String?;
    if (redirectUrl == null) return;

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => _MidtransWebView(url: redirectUrl, orderId: result['pembayaran']['order_id']),
      ),
    );
  }
}

class _MidtransWebView extends StatefulWidget {
  final String url;
  final String orderId;

  const _MidtransWebView({required this.url, required this.orderId});

  @override
  State<_MidtransWebView> createState() => _MidtransWebViewState();
}

class _MidtransWebViewState extends State<_MidtransWebView> {
  late final WebViewController _controller;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(NavigationDelegate(
        onPageStarted: (url) {
          if (url.contains('selesai') || url.contains('finish')) {
            _onPaymentComplete(true);
          } else if (url.contains('gagal') || url.contains('error')) {
            _onPaymentComplete(false);
          }
        },
      ))
      ..loadRequest(Uri.parse(widget.url));
  }

  void _onPaymentComplete(bool success) {
    context.read<IplProvider>().loadTagihanBulanIni();
    context.read<IplProvider>().loadTunggakan();
    Navigator.pop(context);
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(success ? 'Pembayaran berhasil!' : 'Pembayaran gagal.'),
      backgroundColor: success ? AppTheme.successColor : AppTheme.errorColor,
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pembayaran Midtrans'),
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: WebViewWidget(controller: _controller),
    );
  }
}

class _RiwayatTab extends StatelessWidget {
  final NumberFormat currency;

  const _RiwayatTab({required this.currency});

  @override
  Widget build(BuildContext context) {
    final iplProvider = context.watch<IplProvider>();

    if (iplProvider.riwayat.isEmpty) {
      return const Center(child: Text('Belum ada riwayat pembayaran.'));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: iplProvider.riwayat.length,
      itemBuilder: (context, index) {
        final item = iplProvider.riwayat[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 10),
          child: ListTile(
            leading: CircleAvatar(
              backgroundColor: item.isSuccess
                  ? AppTheme.successColor.withOpacity(0.1)
                  : AppTheme.errorColor.withOpacity(0.1),
              child: Icon(
                item.isSuccess ? Icons.check : Icons.close,
                color: item.isSuccess ? AppTheme.successColor : AppTheme.errorColor,
              ),
            ),
            title: Text(item.orderId),
            subtitle: Text(item.paymentType ?? '-'),
            trailing: Text(
              currency.format(item.nominal),
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
        );
      },
    );
  }
}
