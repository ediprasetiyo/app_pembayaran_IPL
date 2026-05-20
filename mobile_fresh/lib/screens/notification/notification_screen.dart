import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:provider/provider.dart';

import '../../providers/notifikasi_provider.dart';
import '../../utils/app_theme.dart';

class NotificationScreen extends StatefulWidget {
  const NotificationScreen({super.key});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<NotifikasiProvider>().load();
    });
  }

  IconData _getIcon(String tipe) => switch (tipe) {
        'pembayaran' => Icons.payment,
        'tagihan' => Icons.receipt_long,
        'pengaduan' => Icons.report_problem,
        'peringatan' => Icons.warning_amber_rounded,
        _ => Icons.info_outline,
      };

  Color _getColor(String tipe) => switch (tipe) {
        'pembayaran' => AppTheme.successColor,
        'tagihan' => AppTheme.warningColor,
        'pengaduan' => Colors.blue,
        'peringatan' => AppTheme.errorColor,
        _ => AppTheme.primaryColor,
      };

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final provider = context.watch<NotifikasiProvider>();

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.notifications),
        actions: [
          if (provider.unreadCount > 0)
            TextButton(
              onPressed: () => context.read<NotifikasiProvider>().markAllRead(),
              child: Text(
                l10n.readAll,
                style: const TextStyle(color: Colors.white),
              ),
            ),
        ],
      ),
      body: provider.isLoading
          ? const Center(child: CircularProgressIndicator())
          : provider.notifikasis.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.notifications_none, size: 64, color: AppTheme.textSecondary),
                      const SizedBox(height: 16),
                      Text(l10n.noNotifications,
                          style: const TextStyle(color: AppTheme.textSecondary)),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: () => context.read<NotifikasiProvider>().load(),
                  child: ListView.separated(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    itemCount: provider.notifikasis.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      final notif = provider.notifikasis[index];
                      return ListTile(
                        tileColor: notif.isRead ? null : AppTheme.primaryColor.withOpacity(0.04),
                        leading: CircleAvatar(
                          backgroundColor: _getColor(notif.tipe).withOpacity(0.1),
                          child: Icon(_getIcon(notif.tipe), color: _getColor(notif.tipe), size: 20),
                        ),
                        title: Text(
                          notif.judul,
                          style: TextStyle(
                            fontWeight: notif.isRead ? FontWeight.normal : FontWeight.bold,
                          ),
                        ),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(notif.pesan, maxLines: 2, overflow: TextOverflow.ellipsis),
                            const SizedBox(height: 2),
                            Text(
                              notif.createdAt,
                              style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary),
                            ),
                          ],
                        ),
                        isThreeLine: true,
                        trailing: !notif.isRead
                            ? Container(
                                width: 8,
                                height: 8,
                                decoration: BoxDecoration(
                                  color: AppTheme.primaryColor,
                                  shape: BoxShape.circle,
                                ),
                              )
                            : null,
                        onTap: () {
                          if (!notif.isRead) {
                            context.read<NotifikasiProvider>().markRead(notif.id);
                          }
                        },
                      );
                    },
                  ),
                ),
    );
  }
}
