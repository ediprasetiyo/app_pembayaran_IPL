import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../providers/auth_provider.dart';
import '../../providers/ipl_provider.dart';
import '../../providers/notifikasi_provider.dart';
import '../../utils/app_theme.dart';
import '../notification/notification_screen.dart';
import '../payment/payment_screen.dart';

class DashboardTab extends StatefulWidget {
  const DashboardTab({super.key});

  @override
  State<DashboardTab> createState() => _DashboardTabState();
}

class _DashboardTabState extends State<DashboardTab> {
  final _currency = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<IplProvider>().loadTagihanBulanIni();
      context.read<IplProvider>().loadTunggakan();
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final user = context.watch<AuthProvider>().user;
    final iplProvider = context.watch<IplProvider>();
    final notifProvider = context.watch<NotifikasiProvider>();
    final tagihan = iplProvider.tagihanBulanIni;

    return Scaffold(
      backgroundColor: AppTheme.backgroundColor,
      body: RefreshIndicator(
        onRefresh: () async {
          await context.read<IplProvider>().loadTagihanBulanIni();
          await context.read<IplProvider>().loadTunggakan();
          await context.read<NotifikasiProvider>().load();
        },
        child: CustomScrollView(
          slivers: [
            SliverAppBar(
              expandedHeight: 180,
              pinned: true,
              backgroundColor: AppTheme.primaryColor,
              actions: [
                Stack(
                  alignment: Alignment.topRight,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.notifications_outlined, color: Colors.white),
                      onPressed: () => Navigator.push(context,
                          MaterialPageRoute(builder: (_) => const NotificationScreen())),
                    ),
                    if (notifProvider.unreadCount > 0)
                      Positioned(
                        right: 8,
                        top: 8,
                        child: Container(
                          padding: const EdgeInsets.all(4),
                          decoration: const BoxDecoration(
                            color: Colors.red,
                            shape: BoxShape.circle,
                          ),
                          child: Text(
                            '${notifProvider.unreadCount}',
                            style: const TextStyle(color: Colors.white, fontSize: 10),
                          ),
                        ),
                      ),
                  ],
                ),
              ],
              flexibleSpace: FlexibleSpaceBar(
                background: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 80, 20, 16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      Text(
                        l10n.greeting(user?.name.split(' ').first ?? ''),
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      if (user?.warga != null)
                        Text(
                          user!.warga!.alamatLengkap,
                          style: const TextStyle(color: Colors.white70, fontSize: 13),
                        ),
                    ],
                  ),
                ),
              ),
            ),
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    _TagihanCard(tagihan: tagihan, currency: _currency, l10n: l10n),
                    const SizedBox(height: 16),
                    _TunggakanCard(
                      tunggakan: iplProvider.tunggakan,
                      currency: _currency,
                      l10n: l10n,
                    ),
                    const SizedBox(height: 16),
                    _MenuGrid(l10n: l10n),
                    const SizedBox(height: 32),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TagihanCard extends StatelessWidget {
  final tagihan;
  final NumberFormat currency;
  final AppLocalizations l10n;

  const _TagihanCard({required this.tagihan, required this.currency, required this.l10n});

  @override
  Widget build(BuildContext context) {
    if (tagihan == null) {
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Center(child: Text(l10n.noTagihan)),
        ),
      );
    }

    final statusColor = tagihan.status == 'sudah_bayar'
        ? AppTheme.successColor
        : tagihan.status == 'terlambat'
            ? AppTheme.errorColor
            : AppTheme.warningColor;

    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [AppTheme.primaryColor, const Color(0xFF2E7D32)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primaryColor.withOpacity(0.3),
            blurRadius: 12,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                l10n.iplTagihanBulanIni,
                style: const TextStyle(color: Colors.white70, fontSize: 13),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.2),
                  border: Border.all(color: statusColor),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  tagihan.status == 'sudah_bayar'
                      ? l10n.lunas
                      : tagihan.status == 'terlambat'
                          ? l10n.terlambat
                          : l10n.belumBayar,
                  style: TextStyle(color: statusColor, fontSize: 12, fontWeight: FontWeight.w600),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            currency.format(tagihan.totalTagihan),
            style: const TextStyle(
              color: Colors.white,
              fontSize: 28,
              fontWeight: FontWeight.bold,
            ),
          ),
          Text(
            '${tagihan.namaBulan} ${tagihan.tahun}',
            style: const TextStyle(color: Colors.white70),
          ),
          if (tagihan.denda > 0) ...[
            const SizedBox(height: 4),
            Text(
              'Denda: ${currency.format(tagihan.denda)}',
              style: const TextStyle(color: Colors.orangeAccent, fontSize: 12),
            ),
          ],
          const SizedBox(height: 16),
          if (tagihan.isBelumBayar)
            ElevatedButton(
              onPressed: () => Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const PaymentScreen(),
                ),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: AppTheme.primaryColor,
                minimumSize: const Size(double.infinity, 44),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: Text(l10n.bayarSekarang,
                  style: const TextStyle(fontWeight: FontWeight.bold)),
            ),
        ],
      ),
    );
  }
}

class _TunggakanCard extends StatelessWidget {
  final List tunggakan;
  final NumberFormat currency;
  final AppLocalizations l10n;

  const _TunggakanCard({required this.tunggakan, required this.currency, required this.l10n});

  @override
  Widget build(BuildContext context) {
    if (tunggakan.isEmpty) return const SizedBox.shrink();

    final total = tunggakan.fold<double>(0, (sum, t) => sum + (t.totalTagihan as double));

    return Card(
      color: const Color(0xFFFFF3E0),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: AppTheme.warningColor.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.warning_amber_rounded,
                  color: AppTheme.warningColor, size: 28),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    l10n.tunggakan(tunggakan.length),
                    style: const TextStyle(fontWeight: FontWeight.bold),
                  ),
                  Text(
                    'Total: ${currency.format(total)}',
                    style: const TextStyle(color: AppTheme.warningColor),
                  ),
                ],
              ),
            ),
            TextButton(
              onPressed: () => Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const PaymentScreen()),
              ),
              child: Text(l10n.bayar),
            ),
          ],
        ),
      ),
    );
  }
}

class _MenuGrid extends StatelessWidget {
  final AppLocalizations l10n;

  const _MenuGrid({required this.l10n});

  @override
  Widget build(BuildContext context) {
    final menus = [
      (Icons.receipt_long, l10n.riwayatPembayaran, const Color(0xFF1565C0)),
      (Icons.family_restroom, l10n.dataKeluarga, const Color(0xFF6A1B9A)),
      (Icons.campaign_outlined, l10n.pengaduan, const Color(0xFFC62828)),
      (Icons.info_outline, l10n.informasi, const Color(0xFF00695C)),
    ];

    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      childAspectRatio: 1.4,
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      children: menus.map((menu) {
        return Card(
          child: InkWell(
            onTap: () {},
            borderRadius: BorderRadius.circular(16),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: menu.$3.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(menu.$1, color: menu.$3, size: 24),
                  ),
                  Text(
                    menu.$2,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                    maxLines: 2,
                  ),
                ],
              ),
            ),
          ),
        );
      }).toList(),
    );
  }
}
