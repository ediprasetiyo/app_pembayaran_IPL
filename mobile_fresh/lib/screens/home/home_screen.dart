import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:provider/provider.dart';

import '../../providers/ipl_provider.dart';
import '../../providers/notifikasi_provider.dart';
import '../../providers/pengaduan_provider.dart';
import '../../utils/app_theme.dart';
import '../notification/notification_screen.dart';
import '../payment/payment_screen.dart';
import '../complaint/complaint_screen.dart';
import '../settings/settings_screen.dart';
import 'dashboard_tab.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> with WidgetsBindingObserver {
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _refreshAllData();
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  /// Auto-refresh saat app kembali dari background ke foreground.
  /// Ini bikin data realtime tanpa user perlu pull-to-refresh.
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _refreshAllData();
    }
  }

  void _refreshAllData() {
    if (!mounted) return;
    try {
      context.read<NotifikasiProvider>().load();
      context.read<IplProvider>().refreshAll();
      context.read<PengaduanProvider>().load();
    } catch (_) {}
  }

  void _changeTab(int index) {
    setState(() => _currentIndex = index);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    final screens = [
      DashboardTab(onChangeTab: _changeTab),
      const PaymentScreen(),
      const ComplaintScreen(),
      const SettingsScreen(),
    ];

    return Scaffold(
      body: IndexedStack(
        index: _currentIndex,
        children: screens,
      ),
      bottomNavigationBar: Consumer<NotifikasiProvider>(
        builder: (context, notifProvider, _) {
          return BottomNavigationBar(
            currentIndex: _currentIndex,
            onTap: (index) => setState(() => _currentIndex = index),
            items: [
              BottomNavigationBarItem(
                icon: const Icon(Icons.home_outlined),
                activeIcon: const Icon(Icons.home),
                label: l10n.home,
              ),
              BottomNavigationBarItem(
                icon: const Icon(Icons.payment_outlined),
                activeIcon: const Icon(Icons.payment),
                label: l10n.payment,
              ),
              BottomNavigationBarItem(
                icon: const Icon(Icons.report_problem_outlined),
                activeIcon: const Icon(Icons.report_problem),
                label: l10n.complaint,
              ),
              BottomNavigationBarItem(
                icon: const Icon(Icons.settings_outlined),
                activeIcon: const Icon(Icons.settings),
                label: l10n.settings,
              ),
            ],
          );
        },
      ),
    );
  }
}
