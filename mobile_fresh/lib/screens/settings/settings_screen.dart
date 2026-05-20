import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../providers/auth_provider.dart';
import '../../providers/locale_provider.dart';
import '../../screens/auth/login_screen.dart';
import '../../utils/app_theme.dart';

const String _adminWhatsApp = '6282115525327';
const String _adminName = 'Admin Griya Pesona Madani';

class SettingsScreen extends StatelessWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final user = context.watch<AuthProvider>().user;
    final locale = context.watch<LocaleProvider>();

    return Scaffold(
      appBar: AppBar(title: Text(l10n.settings)),
      body: ListView(
        children: [
          // Profile Header
          Container(
            padding: const EdgeInsets.all(20),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF1B5E20), Color(0xFF388E3C)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: Row(
              children: [
                GestureDetector(
                  onTap: () => _showEditProfile(context),
                  child: Stack(
                    children: [
                      CircleAvatar(
                        radius: 36,
                        backgroundColor: Colors.white,
                        backgroundImage: user?.avatar != null && user!.avatar!.isNotEmpty
                            ? NetworkImage(_getAvatarUrl(user.avatar!))
                            : null,
                        child: user?.avatar == null || user!.avatar!.isEmpty
                            ? Text(
                                (user?.name ?? 'U')[0].toUpperCase(),
                                style: TextStyle(
                                  color: AppTheme.primaryColor,
                                  fontSize: 28,
                                  fontWeight: FontWeight.bold,
                                ),
                              )
                            : null,
                      ),
                      Positioned(
                        bottom: 0,
                        right: 0,
                        child: Container(
                          padding: const EdgeInsets.all(4),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            shape: BoxShape.circle,
                            border: Border.all(color: AppTheme.primaryColor, width: 2),
                          ),
                          child: Icon(Icons.camera_alt, size: 12, color: AppTheme.primaryColor),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        user?.name ?? '-',
                        style: const TextStyle(
                            color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                        overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        user?.phone ?? '-',
                        style: const TextStyle(color: Colors.white70),
                      ),
                      if (user?.warga != null)
                        Container(
                          margin: const EdgeInsets.only(top: 4),
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            user!.warga!.alamatLengkap,
                            style: const TextStyle(color: Colors.white, fontSize: 11),
                          ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 8),

          // Account
          _SectionHeader(title: l10n.account),
          Card(
            margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Column(
              children: [
                _SettingsTile(
                  icon: Icons.person_outline,
                  title: l10n.editProfile,
                  subtitle: 'Ubah nama & foto profil',
                  onTap: () => _showEditProfile(context),
                ),
                const Divider(height: 1, indent: 56),
                _SettingsTile(
                  icon: Icons.lock_outline,
                  title: l10n.changePassword,
                  subtitle: 'Ubah password login',
                  onTap: () => _showChangePassword(context),
                ),
                const Divider(height: 1, indent: 56),
                _SettingsTile(
                  icon: Icons.family_restroom,
                  title: l10n.dataKeluarga,
                  subtitle: user?.warga != null
                      ? '${user!.warga!.anggotaKeluarga.length} anggota keluarga'
                      : 'Lihat anggota KK',
                  onTap: () => _showDataKeluarga(context),
                ),
              ],
            ),
          ),

          const SizedBox(height: 8),

          // Language
          _SectionHeader(title: l10n.language),
          Card(
            margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Column(
              children: [
                _LanguageTile(
                  flag: '🇮🇩',
                  language: 'Bahasa Indonesia',
                  isSelected: locale.language == 'id',
                  onTap: () => context.read<LocaleProvider>().setLanguage('id'),
                ),
                const Divider(height: 1, indent: 56),
                _LanguageTile(
                  flag: '🇬🇧',
                  language: 'English',
                  isSelected: locale.language == 'en',
                  onTap: () => context.read<LocaleProvider>().setLanguage('en'),
                ),
              ],
            ),
          ),

          const SizedBox(height: 8),

          // About
          _SectionHeader(title: l10n.about),
          Card(
            margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Column(
              children: [
                const _SettingsTile(
                  icon: Icons.info_outline,
                  title: 'Versi Aplikasi',
                  trailing: Text('1.0.0', style: TextStyle(color: AppTheme.textSecondary)),
                  onTap: null,
                ),
                const Divider(height: 1, indent: 56),
                _SettingsTile(
                  icon: Icons.support_agent,
                  title: 'Hubungi Admin',
                  subtitle: 'Chat WhatsApp admin',
                  trailing: const Icon(Icons.chat, color: Color(0xFF25D366), size: 20),
                  onTap: () => openAdminWhatsApp(context),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: ElevatedButton.icon(
              onPressed: () => _confirmLogout(context),
              icon: const Icon(Icons.logout),
              label: Text(l10n.logout),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.errorColor,
                foregroundColor: Colors.white,
              ),
            ),
          ),

          const SizedBox(height: 12),
          const Center(
            child: Text(
              'Created by Edi Prasetiyo',
              style: TextStyle(fontSize: 11, color: Color(0xFF9E9E9E)),
            ),
          ),
          const SizedBox(height: 32),
        ],
      ),
    );
  }

  String _getAvatarUrl(String avatar) {
    if (avatar.startsWith('http')) return avatar;
    return 'https://ipl-griya-pesona-madani.my.id$avatar';
  }

  void _showEditProfile(BuildContext context) {
    final user = context.read<AuthProvider>().user;
    final nameCtrl = TextEditingController(text: user?.name);
    File? pickedImage;
    bool saving = false;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setState) => Padding(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(ctx).viewInsets.bottom +
                MediaQuery.of(ctx).padding.bottom + 24,
            left: 20, right: 20, top: 20,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40, height: 4,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              const Text('Edit Profil', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              Center(
                child: GestureDetector(
                  onTap: () async {
                    final picker = ImagePicker();
                    final img = await picker.pickImage(
                      source: ImageSource.gallery,
                      imageQuality: 70,
                      maxWidth: 800,
                    );
                    if (img != null) {
                      setState(() => pickedImage = File(img.path));
                    }
                  },
                  child: Stack(
                    children: [
                      CircleAvatar(
                        radius: 48,
                        backgroundColor: AppTheme.primaryColor.withOpacity(0.1),
                        backgroundImage: pickedImage != null
                            ? FileImage(pickedImage!) as ImageProvider
                            : (user?.avatar != null && user!.avatar!.isNotEmpty
                                ? NetworkImage(_getAvatarUrl(user.avatar!))
                                : null),
                        child: (pickedImage == null &&
                                (user?.avatar == null || user!.avatar!.isEmpty))
                            ? Text(
                                (user?.name ?? 'U')[0].toUpperCase(),
                                style: TextStyle(
                                  color: AppTheme.primaryColor,
                                  fontSize: 32,
                                  fontWeight: FontWeight.bold,
                                ),
                              )
                            : null,
                      ),
                      Positioned(
                        bottom: 0, right: 0,
                        child: Container(
                          padding: const EdgeInsets.all(6),
                          decoration: BoxDecoration(
                            color: AppTheme.primaryColor,
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white, width: 2),
                          ),
                          child: const Icon(Icons.camera_alt, size: 14, color: Colors.white),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 8),
              const Center(
                child: Text('Tap foto untuk ubah dari galeri',
                    style: TextStyle(fontSize: 11, color: AppTheme.textSecondary)),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: nameCtrl,
                decoration: const InputDecoration(
                  labelText: 'Nama Lengkap',
                  prefixIcon: Icon(Icons.person_outline),
                ),
              ),
              const SizedBox(height: 20),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: saving ? null : () async {
                    setState(() => saving = true);
                    final success = await context.read<AuthProvider>().updateProfile(
                          {'name': nameCtrl.text.trim()},
                          avatarPath: pickedImage?.path,
                        );
                    if (!context.mounted) return;
                    Navigator.pop(ctx);
                    _showCenteredSnack(
                      context,
                      success ? 'Profil berhasil diperbarui!' : 'Gagal update profil',
                      success: success,
                    );
                  },
                  style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14)),
                  child: saving
                      ? const SizedBox(
                          height: 20, width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Simpan'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showCenteredSnack(BuildContext context, String msg, {bool success = true}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg, textAlign: TextAlign.center),
        backgroundColor: success ? AppTheme.successColor : AppTheme.errorColor,
        behavior: SnackBarBehavior.floating,
        margin: const EdgeInsets.symmetric(horizontal: 40, vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        duration: const Duration(seconds: 2),
      ),
    );
  }

  void _showChangePassword(BuildContext context) {
    final oldCtrl = TextEditingController();
    final newCtrl = TextEditingController();
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(ctx).viewInsets.bottom +
              MediaQuery.of(ctx).padding.bottom + 24,
          left: 20, right: 20, top: 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 40, height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            const Text('Ganti Password', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 16),
            TextField(
              controller: oldCtrl,
              obscureText: true,
              decoration: const InputDecoration(
                labelText: 'Password Lama',
                prefixIcon: Icon(Icons.lock_outline),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: newCtrl,
              obscureText: true,
              decoration: const InputDecoration(
                labelText: 'Password Baru',
                prefixIcon: Icon(Icons.lock_reset),
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () async {
                  Navigator.pop(ctx);
                  final success = await context
                      .read<AuthProvider>()
                      .changePassword(oldCtrl.text, newCtrl.text);
                  if (!context.mounted) return;
                  _showCenteredSnack(
                    context,
                    success ? 'Password berhasil diubah!' : 'Gagal mengubah password.',
                    success: success,
                  );
                },
                style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14)),
                child: const Text('Simpan'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showDataKeluarga(BuildContext context) {
    final user = context.read<AuthProvider>().user;
    final keluarga = user?.warga?.anggotaKeluarga ?? [];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        maxChildSize: 0.95,
        minChildSize: 0.5,
        expand: false,
        builder: (ctx, scrollController) => SingleChildScrollView(
          controller: scrollController,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40, height: 4,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Icon(Icons.family_restroom, color: AppTheme.primaryColor),
                  const SizedBox(width: 8),
                  const Text('Data Keluarga (KK)',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                'Anggota keluarga sesuai Kartu Keluarga yang didaftarkan',
                style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
              ),
              const SizedBox(height: 20),

              _kepalaKeluargaCard(user),
              const SizedBox(height: 16),

              if (keluarga.isEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 40),
                  child: Column(
                    children: [
                      Icon(Icons.people_outline, size: 56, color: Colors.grey.shade300),
                      const SizedBox(height: 8),
                      const Text('Belum ada anggota keluarga terdaftar',
                          style: TextStyle(color: AppTheme.textSecondary)),
                      const SizedBox(height: 4),
                      Text('Hubungi admin untuk menambahkan',
                          style: TextStyle(fontSize: 11, color: Colors.grey.shade500)),
                    ],
                  ),
                )
              else ...[
                Text(
                  'Anggota Keluarga (${keluarga.length})',
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
                ),
                const SizedBox(height: 8),
                ...keluarga.map((a) => _anggotaCard(a)),
              ],

              const SizedBox(height: 24),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppTheme.primaryColor.withOpacity(0.05),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: AppTheme.primaryColor.withOpacity(0.2)),
                ),
                child: Row(
                  children: [
                    Icon(Icons.info_outline, size: 16, color: AppTheme.primaryColor),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Untuk mengubah data keluarga, hubungi admin via WhatsApp.',
                        style: TextStyle(fontSize: 11, color: Colors.grey.shade700),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
    );
  }

  Widget _kepalaKeluargaCard(dynamic user) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [AppTheme.primaryColor.withOpacity(0.1), AppTheme.primaryColor.withOpacity(0.02)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppTheme.primaryColor.withOpacity(0.3)),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 24,
            backgroundColor: AppTheme.primaryColor,
            child: Text(
              (user?.name ?? 'K')[0].toUpperCase(),
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: AppTheme.primaryColor,
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: const Text('Kepala Keluarga',
                      style: TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.w600)),
                ),
                const SizedBox(height: 4),
                Text(user?.name ?? '-',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                Text(user?.phone ?? '-',
                    style: const TextStyle(fontSize: 12, color: AppTheme.textSecondary)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _anggotaCard(dynamic a) {
    final isPerempuan = a.jenisKelamin == 'perempuan';
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 20,
            backgroundColor: (isPerempuan ? Colors.pink : Colors.blue).withOpacity(0.15),
            child: Icon(
              isPerempuan ? Icons.female : Icons.male,
              color: isPerempuan ? Colors.pink : Colors.blue,
              size: 22,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(a.nama,
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                      decoration: BoxDecoration(
                        color: AppTheme.primaryColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        _hubunganLabel(a.hubungan),
                        style: TextStyle(fontSize: 10, color: AppTheme.primaryColor, fontWeight: FontWeight.w600),
                      ),
                    ),
                    if (a.pekerjaan != null && a.pekerjaan.toString().isNotEmpty) ...[
                      const SizedBox(width: 6),
                      Text('· ${a.pekerjaan}',
                          style: const TextStyle(fontSize: 10, color: AppTheme.textSecondary)),
                    ],
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _hubunganLabel(String h) {
    return {
      'kepala_keluarga': 'Kepala Keluarga',
      'istri': 'Istri',
      'anak': 'Anak',
      'orang_tua': 'Orang Tua',
      'saudara': 'Saudara',
      'lainnya': 'Lainnya',
    }[h] ?? h;
  }

  void _confirmLogout(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Konfirmasi Logout'),
        content: const Text('Apakah Anda yakin ingin keluar?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Batal')),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(ctx);
              await context.read<AuthProvider>().logout();
              if (!context.mounted) return;
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (_) => const LoginScreen()),
                (_) => false,
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.errorColor,
              minimumSize: const Size(80, 40),
            ),
            child: const Text('Logout'),
          ),
        ],
      ),
    );
  }
}

// === GLOBAL: Open WhatsApp to Admin ===
Future<void> openAdminWhatsApp(BuildContext context, {String? customMessage}) async {
  final user = context.read<AuthProvider>().user;
  final message = customMessage ??
      'Halo $_adminName,\n\n'
      'Saya ${user?.name ?? "Warga"} dari Blok ${user?.warga?.blok ?? "-"} No. ${user?.warga?.nomorRumah ?? "-"}\n'
      'Nomor HP: ${user?.phone ?? "-"}\n\n'
      'Saya ingin menyampaikan...';
  final url = Uri.parse('https://wa.me/$_adminWhatsApp?text=${Uri.encodeComponent(message)}');

  try {
    if (await canLaunchUrl(url)) {
      await launchUrl(url, mode: LaunchMode.externalApplication);
    } else {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
          content: Text('WhatsApp tidak terpasang. Install WhatsApp terlebih dahulu.'),
        ));
      }
    }
  } catch (e) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Gagal buka WhatsApp: $e')));
    }
  }
}

class _SectionHeader extends StatelessWidget {
  final String title;
  const _SectionHeader({required this.title});
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
      child: Text(
        title.toUpperCase(),
        style: const TextStyle(
          color: AppTheme.textSecondary,
          fontSize: 11,
          fontWeight: FontWeight.w600,
          letterSpacing: 0.5,
        ),
      ),
    );
  }
}

class _SettingsTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  final Widget? trailing;
  final VoidCallback? onTap;
  const _SettingsTile({
    required this.icon,
    required this.title,
    this.subtitle,
    this.trailing,
    required this.onTap,
  });
  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(icon, color: AppTheme.primaryColor),
      title: Text(title),
      subtitle: subtitle != null ? Text(subtitle!, style: const TextStyle(fontSize: 11)) : null,
      trailing: trailing ?? (onTap != null ? const Icon(Icons.chevron_right, color: AppTheme.textSecondary) : null),
      onTap: onTap,
    );
  }
}

class _LanguageTile extends StatelessWidget {
  final String flag;
  final String language;
  final bool isSelected;
  final VoidCallback onTap;
  const _LanguageTile({
    required this.flag,
    required this.language,
    required this.isSelected,
    required this.onTap,
  });
  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Text(flag, style: const TextStyle(fontSize: 24)),
      title: Text(language),
      trailing: isSelected
          ? Icon(Icons.check_circle, color: AppTheme.primaryColor)
          : null,
      onTap: onTap,
    );
  }
}
