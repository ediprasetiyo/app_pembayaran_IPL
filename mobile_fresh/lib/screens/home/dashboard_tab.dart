import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../models/news_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/ipl_provider.dart';
import '../../providers/news_provider.dart';
import '../../providers/notifikasi_provider.dart';
import '../../utils/app_theme.dart';
import '../news/news_detail_screen.dart';
import '../notification/notification_screen.dart';

class DashboardTab extends StatefulWidget {
  /// Callback untuk pindah ke tab tertentu di HomeScreen
  final void Function(int index)? onChangeTab;

  const DashboardTab({super.key, this.onChangeTab});

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
      context.read<NewsProvider>().fetchLatest();
    });
  }

  Future<void> _refresh() async {
    await Future.wait([
      context.read<IplProvider>().loadTagihanBulanIni(),
      context.read<IplProvider>().loadTunggakan(),
      context.read<NewsProvider>().fetchLatest(),
      context.read<NotifikasiProvider>().load(),
    ]);
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;
    final iplProvider = context.watch<IplProvider>();
    final newsProvider = context.watch<NewsProvider>();
    final notifProvider = context.watch<NotifikasiProvider>();
    final tagihan = iplProvider.tagihanBulanIni;
    final unreadNotif = notifProvider.unreadCount;

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      body: RefreshIndicator(
        color: AppTheme.primaryColor,
        onRefresh: _refresh,
        child: CustomScrollView(
          slivers: [
            // ==== HEADER ====
            SliverToBoxAdapter(
              child: _buildHeader(user, unreadNotif),
            ),

            // ==== TAGIHAN CARD (di bawah header dengan jarak) ====
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                child: _buildTagihanCard(tagihan, iplProvider),
              ),
            ),

            // ==== QUICK MENU ====
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
                child: _buildQuickMenu(iplProvider),
              ),
            ),

            // ==== NEWS SECTION ====
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                child: _buildSectionTitle('Berita & Pengumuman', icon: Icons.campaign_outlined),
              ),
            ),
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                child: _buildNewsSection(newsProvider),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ============== HEADER ==============
  Widget _buildHeader(dynamic user, int unreadNotif) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF1B5E20), Color(0xFF2E7D32), Color(0xFF388E3C)],
        ),
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(32),
          bottomRight: Radius.circular(32),
        ),
      ),
      padding: EdgeInsets.only(
        top: MediaQuery.of(context).padding.top + 18,
        bottom: 24,
        left: 20,
        right: 20,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 22,
                backgroundColor: Colors.white,
                child: Text(
                  (user?.name.isNotEmpty == true ? user!.name[0] : 'W').toUpperCase(),
                  style: const TextStyle(
                    color: AppTheme.primaryColor,
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _greeting(),
                      style: const TextStyle(color: Colors.white70, fontSize: 12),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      user?.name ?? 'Warga',
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              _notifIcon(unreadNotif),
            ],
          ),
          if (user?.warga != null) ...[
            const SizedBox(height: 14),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.18),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.location_on_outlined, color: Colors.white, size: 14),
                  const SizedBox(width: 6),
                  Flexible(
                    child: Text(
                      'Blok ${user!.warga!.blok} No. ${user.warga!.nomorRumah}',
                      style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w500),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _notifIcon(int unread) {
    return Stack(
      children: [
        Material(
          color: Colors.white.withOpacity(0.18),
          shape: const CircleBorder(),
          child: InkWell(
            customBorder: const CircleBorder(),
            onTap: () => Navigator.push(context,
                MaterialPageRoute(builder: (_) => const NotificationScreen())),
            child: const Padding(
              padding: EdgeInsets.all(10),
              child: Icon(Icons.notifications_outlined, color: Colors.white, size: 20),
            ),
          ),
        ),
        if (unread > 0)
          Positioned(
            right: 5,
            top: 5,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
              decoration: BoxDecoration(
                color: Colors.red,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: AppTheme.primaryColor, width: 1.5),
              ),
              constraints: const BoxConstraints(minWidth: 14, minHeight: 14),
              alignment: Alignment.center,
              child: Text(
                unread > 9 ? '9+' : '$unread',
                style: const TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold),
              ),
            ),
          ),
      ],
    );
  }

  String _greeting() {
    final hour = DateTime.now().hour;
    if (hour < 11) return 'Selamat pagi 👋';
    if (hour < 15) return 'Selamat siang 👋';
    if (hour < 18) return 'Selamat sore 👋';
    return 'Selamat malam 👋';
  }

  // ============== TAGIHAN CARD ==============
  Widget _buildTagihanCard(dynamic tagihan, IplProvider iplProvider) {
    final isLoading = iplProvider.isLoading;
    final tagihanList = iplProvider.tagihanBulanIniList;
    final hasTagihan = tagihanList.isNotEmpty;
    final totalBelumBayar = iplProvider.totalBelumBayar;
    final allLunas = hasTagihan && totalBelumBayar == 0;

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.06),
            blurRadius: 18,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      padding: EdgeInsets.zero,
      child: isLoading
          ? const Padding(
              padding: EdgeInsets.symmetric(vertical: 32),
              child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
            )
          : !hasTagihan
              ? Padding(
                  padding: const EdgeInsets.all(20),
                  child: _emptyTagihan(),
                )
              : Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // ===== HEADER: Periode + Status =====
                    Container(
                      padding: const EdgeInsets.fromLTRB(20, 18, 20, 16),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [
                            AppTheme.primaryColor.withOpacity(0.06),
                            AppTheme.primaryColor.withOpacity(0.02),
                          ],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: const BorderRadius.only(
                          topLeft: Radius.circular(20),
                          topRight: Radius.circular(20),
                        ),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          Container(
                            width: 38,
                            height: 38,
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(10),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withOpacity(0.05),
                                  blurRadius: 6,
                                  offset: const Offset(0, 2),
                                ),
                              ],
                            ),
                            child: const Icon(
                              Icons.receipt_long_rounded,
                              color: AppTheme.primaryColor,
                              size: 20,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Tagihan Bulan Ini',
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: Color(0xFF757575),
                                    letterSpacing: 0.3,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  DateFormat('MMMM yyyy', 'id_ID').format(DateTime.now()),
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 15,
                                    color: AppTheme.textPrimary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                            decoration: BoxDecoration(
                              color: allLunas ? Colors.green.shade100 : Colors.orange.shade100,
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  allLunas ? Icons.check_circle : Icons.schedule,
                                  size: 12,
                                  color: allLunas ? Colors.green.shade700 : Colors.orange.shade800,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  allLunas ? 'Lunas' : 'Belum Bayar',
                                  style: TextStyle(
                                    color: allLunas ? Colors.green.shade700 : Colors.orange.shade800,
                                    fontSize: 10,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),

                    // ===== TOTAL =====
                    Padding(
                      padding: const EdgeInsets.fromLTRB(20, 18, 20, 12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            allLunas ? 'Total yang Sudah Dibayar' : 'Total yang Harus Dibayar',
                            style: const TextStyle(
                              fontSize: 11,
                              color: Color(0xFF9E9E9E),
                              letterSpacing: 0.3,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _currency.format(totalBelumBayar > 0 ? totalBelumBayar : iplProvider.totalBulanIni),
                            style: const TextStyle(
                              fontSize: 28,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.primaryColor,
                              letterSpacing: -0.5,
                            ),
                          ),
                          if (tagihan != null) ...[
                            const SizedBox(height: 6),
                            Row(
                              children: [
                                const Icon(Icons.event_outlined, size: 12, color: Color(0xFF9E9E9E)),
                                const SizedBox(width: 4),
                                Text(
                                  'Jatuh tempo ${DateFormat('d MMMM yyyy', 'id_ID').format(tagihan.jatuhTempo)}',
                                  style: const TextStyle(
                                    fontSize: 11,
                                    color: Color(0xFF9E9E9E),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ],
                      ),
                    ),

                    // Divider
                    const Padding(
                      padding: EdgeInsets.symmetric(horizontal: 20),
                      child: Divider(height: 1, color: Color(0xFFEEEEEE)),
                    ),

                    // ===== RINCIAN =====
                    Padding(
                      padding: const EdgeInsets.fromLTRB(20, 12, 20, 16),
                      child: Column(
                        children: tagihanList.map((t) => _buildRincianItem(t)).toList(),
                      ),
                    ),

                    // ===== TOMBOL BAYAR =====
                    if (!allLunas)
                      Padding(
                        padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                        child: SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: () => widget.onChangeTab?.call(1),
                            icon: const Icon(Icons.payment, size: 18),
                            label: const Text('Bayar Sekarang'),
                            style: ElevatedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                              elevation: 0,
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
    );
  }

  Widget _buildRincianItem(dynamic t) {
    final isLunas = t.status == 'sudah_bayar';
    final isKedukaan = t.jenis == 'kedukaan';
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Container(
            width: 28,
            height: 28,
            decoration: BoxDecoration(
              color: (isKedukaan ? Colors.purple : AppTheme.primaryColor).withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(
              isKedukaan ? Icons.volunteer_activism : Icons.home_outlined,
              size: 14,
              color: isKedukaan ? Colors.purple : AppTheme.primaryColor,
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isKedukaan ? 'Uang Kedukaan' : 'IPL Bulanan',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppTheme.textPrimary,
                  ),
                ),
                if (isKedukaan)
                  const Text(
                    'Sekali bayar untuk warga baru',
                    style: TextStyle(fontSize: 10, color: Color(0xFF9E9E9E)),
                  ),
              ],
            ),
          ),
          if (isLunas)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: Colors.green.shade50,
                borderRadius: BorderRadius.circular(6),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.check_circle, size: 10, color: Colors.green.shade700),
                  const SizedBox(width: 3),
                  Text(
                    'Lunas',
                    style: TextStyle(
                      fontSize: 9,
                      fontWeight: FontWeight.w600,
                      color: Colors.green.shade700,
                    ),
                  ),
                ],
              ),
            ),
          const SizedBox(width: 8),
          Text(
            _currency.format(t.totalTagihan),
            style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.bold,
              color: isLunas ? const Color(0xFFBDBDBD) : AppTheme.textPrimary,
              decoration: isLunas ? TextDecoration.lineThrough : null,
            ),
          ),
        ],
      ),
    );
  }

  Widget _emptyTagihan() {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 16),
      child: Column(
        children: [
          Icon(Icons.check_circle_outline, size: 44, color: Colors.green.shade400),
          const SizedBox(height: 8),
          const Text('Tidak ada tagihan bulan ini',
              style: TextStyle(fontWeight: FontWeight.w600)),
          const Text('Anda sudah lunas 🎉',
              style: TextStyle(fontSize: 12, color: Color(0xFF9E9E9E))),
        ],
      ),
    );
  }

  // ============== QUICK MENU ==============
  Widget _buildQuickMenu(IplProvider iplProvider) {
    final tunggakan = iplProvider.tunggakan.length;

    final items = [
      _MenuItem(
        label: 'Riwayat\nPembayaran',
        icon: Icons.history,
        color: const Color(0xFF1976D2),
        onTap: () => widget.onChangeTab?.call(1),
      ),
      _MenuItem(
        label: 'Tunggakan',
        icon: Icons.warning_amber_rounded,
        color: const Color(0xFFFF9800),
        badge: tunggakan > 0 ? '$tunggakan' : null,
        onTap: () => widget.onChangeTab?.call(1),
      ),
      _MenuItem(
        label: 'Pengaduan',
        icon: Icons.report_problem_outlined,
        color: const Color(0xFFE53935),
        onTap: () => widget.onChangeTab?.call(2),
      ),
      _MenuItem(
        label: 'Profil',
        icon: Icons.person_outline,
        color: const Color(0xFF7B1FA2),
        onTap: () => widget.onChangeTab?.call(3),
      ),
    ];

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: items
            .map((item) => Expanded(child: _buildMenuItem(item)))
            .toList(),
      ),
    );
  }

  Widget _buildMenuItem(_MenuItem item) {
    return InkWell(
      onTap: item.onTap,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
        child: Column(
          children: [
            Stack(
              clipBehavior: Clip.none,
              children: [
                Container(
                  width: 46,
                  height: 46,
                  decoration: BoxDecoration(
                    color: item.color.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Icon(item.icon, color: item.color, size: 22),
                ),
                if (item.badge != null)
                  Positioned(
                    top: -4,
                    right: -4,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                      decoration: BoxDecoration(
                        color: Colors.red,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.white, width: 1.5),
                      ),
                      constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                      alignment: Alignment.center,
                      child: Text(
                        item.badge!,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 9,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              item.label,
              style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w500, height: 1.2),
              textAlign: TextAlign.center,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  // ============== SECTION TITLE ==============
  Widget _buildSectionTitle(String title, {required IconData icon}) {
    return Padding(
      padding: const EdgeInsets.only(top: 8, bottom: 8),
      child: Row(
        children: [
          Icon(icon, color: AppTheme.primaryColor, size: 18),
          const SizedBox(width: 6),
          Text(
            title,
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
          ),
        ],
      ),
    );
  }

  // ============== NEWS SECTION (HORIZONTAL CAROUSEL) ==============
  Widget _buildNewsSection(NewsProvider newsProvider) {
    if (newsProvider.isLoading) {
      return SizedBox(
        height: 260,
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
          ),
          child: const Center(child: CircularProgressIndicator(strokeWidth: 2)),
        ),
      );
    }

    if (newsProvider.latestNews.isEmpty) {
      return Container(
        height: 200,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.inbox_outlined, size: 44, color: Colors.grey.shade300),
            const SizedBox(height: 8),
            const Text(
              'Belum ada berita',
              style: TextStyle(color: Color(0xFF9E9E9E), fontSize: 13),
            ),
            const Text(
              'Pengumuman dari admin akan muncul di sini',
              style: TextStyle(color: Color(0xFFBDBDBD), fontSize: 11),
            ),
          ],
        ),
      );
    }

    return SizedBox(
      height: 285,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        physics: const BouncingScrollPhysics(),
        padding: const EdgeInsets.symmetric(horizontal: 4),
        itemCount: newsProvider.latestNews.length,
        itemBuilder: (context, index) {
          final news = newsProvider.latestNews[index];
          return Padding(
            padding: EdgeInsets.only(
              right: index == newsProvider.latestNews.length - 1 ? 0 : 12,
            ),
            child: _newsCard(news),
          );
        },
      ),
    );
  }

  Widget _newsCard(NewsModel news) {
    return SizedBox(
      width: 260,
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          onTap: () => _openNewsDetail(news),
          borderRadius: BorderRadius.circular(16),
          child: Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  blurRadius: 10,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Image
                ClipRRect(
                  borderRadius: const BorderRadius.only(
                    topLeft: Radius.circular(16),
                    topRight: Radius.circular(16),
                  ),
                  child: Stack(
                    children: [
                      SizedBox(
                        width: double.infinity,
                        height: 140,
                        child: news.gambarUrl != null && news.gambarUrl!.isNotEmpty
                            ? CachedNetworkImage(
                                imageUrl: news.gambarUrl!,
                                fit: BoxFit.cover,
                                placeholder: (_, __) => Container(
                                  color: AppTheme.primaryColor.withOpacity(0.08),
                                  alignment: Alignment.center,
                                  child: const SizedBox(
                                    width: 24,
                                    height: 24,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  ),
                                ),
                                errorWidget: (_, __, ___) => _placeholderImg(news),
                              )
                            : _placeholderImg(news),
                      ),
                      // Kategori badge
                      Positioned(
                        top: 8,
                        left: 8,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: _kategoriColor(news.kategori),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            '${news.kategoriEmoji} ${news.kategoriLabel}',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 9,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ),
                      if (news.isPinned)
                        Positioned(
                          top: 8,
                          right: 8,
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
                            decoration: BoxDecoration(
                              color: Colors.amber,
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: const Text('📌 Pinned',
                                style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.w600)),
                          ),
                        ),
                    ],
                  ),
                ),
                // Body
                Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        news.judul,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, height: 1.3),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 6),
                      Text(
                        news.ringkasan != null && news.ringkasan!.isNotEmpty
                            ? news.ringkasan!
                            : _stripHtml(news.konten),
                        style: const TextStyle(fontSize: 11, color: Color(0xFF757575), height: 1.4),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          const Icon(Icons.favorite_border, size: 12, color: Color(0xFF9E9E9E)),
                          const SizedBox(width: 3),
                          Text('${news.likesCount}', style: const TextStyle(fontSize: 10, color: Color(0xFF9E9E9E))),
                          const SizedBox(width: 10),
                          const Icon(Icons.chat_bubble_outline, size: 12, color: Color(0xFF9E9E9E)),
                          const SizedBox(width: 3),
                          Text('${news.commentsCount}', style: const TextStyle(fontSize: 10, color: Color(0xFF9E9E9E))),
                          const Spacer(),
                          Text(
                            news.publishedAt != null
                                ? DateFormat('d MMM', 'id_ID').format(news.publishedAt!)
                                : '-',
                            style: const TextStyle(fontSize: 10, color: Color(0xFFBDBDBD)),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _placeholderImg(NewsModel news) {
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            _kategoriColor(news.kategori).withOpacity(0.5),
            _kategoriColor(news.kategori).withOpacity(0.2),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      alignment: Alignment.center,
      child: Text(news.kategoriEmoji, style: const TextStyle(fontSize: 56)),
    );
  }

  Color _kategoriColor(String kategori) {
    switch (kategori) {
      case 'pengumuman': return Colors.blue;
      case 'kegiatan': return Colors.green;
      case 'darurat': return Colors.red;
      default: return Colors.grey;
    }
  }

  String _stripHtml(String html) {
    return html.replaceAll(RegExp(r'<[^>]*>'), ' ').replaceAll(RegExp(r'\s+'), ' ').trim();
  }

  void _openNewsDetail(NewsModel news) {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => NewsDetailScreen(news: news)),
    ).then((_) {
      // Refresh untuk update count komentar/like
      context.read<NewsProvider>().fetchLatest();
    });
  }
}

class _MenuItem {
  final String label;
  final IconData icon;
  final Color color;
  final String? badge;
  final VoidCallback onTap;

  _MenuItem({
    required this.label,
    required this.icon,
    required this.color,
    this.badge,
    required this.onTap,
  });
}
