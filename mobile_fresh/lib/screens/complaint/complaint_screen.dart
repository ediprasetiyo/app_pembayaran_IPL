import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../providers/pengaduan_provider.dart';
import '../../utils/app_theme.dart';
import '../../utils/constants.dart';

class ComplaintScreen extends StatefulWidget {
  const ComplaintScreen({super.key});

  @override
  State<ComplaintScreen> createState() => _ComplaintScreenState();
}

class _ComplaintScreenState extends State<ComplaintScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<PengaduanProvider>().load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final provider = context.watch<PengaduanProvider>();

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.complaint),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_circle_outline),
            onPressed: () => _showFormPengaduan(context),
          ),
        ],
      ),
      body: provider.isLoading
          ? const Center(child: CircularProgressIndicator())
          : provider.pengaduans.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.inbox_outlined, size: 64, color: AppTheme.textSecondary),
                      const SizedBox(height: 16),
                      Text(l10n.noPengaduan,
                          style: const TextStyle(color: AppTheme.textSecondary)),
                      const SizedBox(height: 16),
                      ElevatedButton.icon(
                        onPressed: () => _showFormPengaduan(context),
                        icon: const Icon(Icons.add),
                        label: Text(l10n.buatPengaduan),
                        style: ElevatedButton.styleFrom(minimumSize: const Size(160, 44)),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: () => context.read<PengaduanProvider>().load(),
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: provider.pengaduans.length,
                    itemBuilder: (context, index) {
                      final item = provider.pengaduans[index];
                      return _PengaduanCard(
                        pengaduan: item,
                        onTap: () => _showDetailPengaduan(context, item),
                      );
                    },
                  ),
                ),
    );
  }

  void _showDetailPengaduan(BuildContext context, PengaduanModel pengaduan) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (_) => _DetailPengaduanSheet(pengaduan: pengaduan),
    );
  }

  void _showFormPengaduan(BuildContext context) {
    Navigator.push(
      context,
      MaterialPageRoute(builder: (_) => const _FormPengaduanScreen()),
    );
  }
}

class _PengaduanCard extends StatelessWidget {
  final PengaduanModel pengaduan;
  final VoidCallback? onTap;

  const _PengaduanCard({required this.pengaduan, this.onTap});

  Color get statusColor => switch (pengaduan.status) {
        'baru' => AppTheme.warningColor,
        'diproses' => Colors.blue,
        'selesai' => AppTheme.successColor,
        'ditolak' => AppTheme.errorColor,
        _ => AppTheme.textSecondary,
      };

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    pengaduan.judul,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    AppConstants.statusPengaduan[pengaduan.status] ?? pengaduan.status,
                    style: TextStyle(
                        color: statusColor, fontSize: 11, fontWeight: FontWeight.w600),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: AppTheme.backgroundColor,
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    AppConstants.kategoriPengaduan[pengaduan.kategori] ?? pengaduan.kategori,
                    style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              pengaduan.deskripsi,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(color: AppTheme.textSecondary, fontSize: 13),
            ),
            if (pengaduan.keteranganAdmin != null && pengaduan.keteranganAdmin!.isNotEmpty) ...[
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Colors.blue.withOpacity(0.05),
                  border: Border.all(color: Colors.blue.withOpacity(0.3)),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.admin_panel_settings, size: 16, color: Colors.blue),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        pengaduan.keteranganAdmin!,
                        style: const TextStyle(fontSize: 12, color: Colors.blue),
                      ),
                    ),
                  ],
                ),
              ),
            ],
            const SizedBox(height: 8),
            Row(
              children: [
                if (pengaduan.foto != null && pengaduan.foto!.isNotEmpty) ...[
                  const Icon(Icons.image_outlined, size: 14, color: AppTheme.textSecondary),
                  const SizedBox(width: 4),
                  Text('${pengaduan.foto!.length} foto',
                      style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary)),
                  const SizedBox(width: 12),
                ],
                const Icon(Icons.touch_app_outlined, size: 14, color: AppTheme.textSecondary),
                const SizedBox(width: 4),
                const Text('Tap untuk detail',
                    style: TextStyle(fontSize: 11, color: AppTheme.textSecondary)),
              ],
            ),
          ],
        ),
      ),
    ),
    );
  }
}

// ============== DETAIL SHEET ==============
class _DetailPengaduanSheet extends StatelessWidget {
  final PengaduanModel pengaduan;

  const _DetailPengaduanSheet({required this.pengaduan});

  Color get statusColor => switch (pengaduan.status) {
        'baru' => AppTheme.warningColor,
        'diproses' => Colors.blue,
        'selesai' => AppTheme.successColor,
        'ditolak' => AppTheme.errorColor,
        _ => AppTheme.textSecondary,
      };

  @override
  Widget build(BuildContext context) {
    return DraggableScrollableSheet(
      initialChildSize: 0.7,
      maxChildSize: 0.95,
      minChildSize: 0.5,
      expand: false,
      builder: (_, controller) => SingleChildScrollView(
        controller: controller,
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
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
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: Text(
                    pengaduan.judul,
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    AppConstants.statusPengaduan[pengaduan.status] ?? pengaduan.status,
                    style: TextStyle(
                      color: statusColor,
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: AppTheme.backgroundColor,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                AppConstants.kategoriPengaduan[pengaduan.kategori] ?? pengaduan.kategori,
                style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary),
              ),
            ),
            const SizedBox(height: 16),
            // Foto
            if (pengaduan.fotoUrls != null && pengaduan.fotoUrls!.isNotEmpty) ...[
              const Text('Foto Pendukung',
                  style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
              const SizedBox(height: 8),
              SizedBox(
                height: 130,
                child: ListView.builder(
                  scrollDirection: Axis.horizontal,
                  itemCount: pengaduan.fotoUrls!.length,
                  itemBuilder: (_, idx) {
                    final url = pengaduan.fotoUrls![idx];
                    return Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: GestureDetector(
                        onTap: () => _showImagePreview(context, url),
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: Image.network(
                            url,
                            width: 130,
                            height: 130,
                            fit: BoxFit.cover,
                            errorBuilder: (_, __, ___) => Container(
                              width: 130,
                              height: 130,
                              color: Colors.grey.shade200,
                              alignment: Alignment.center,
                              child: const Icon(Icons.broken_image, color: Colors.grey),
                            ),
                          ),
                        ),
                      ),
                    );
                  },
                ),
              ),
              const SizedBox(height: 16),
            ],
            const Text('Deskripsi',
                style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppTheme.backgroundColor,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                pengaduan.deskripsi,
                style: const TextStyle(fontSize: 13, height: 1.5),
              ),
            ),
            if (pengaduan.keteranganAdmin != null && pengaduan.keteranganAdmin!.isNotEmpty) ...[
              const SizedBox(height: 16),
              const Row(
                children: [
                  Icon(Icons.admin_panel_settings, size: 16, color: Colors.blue),
                  SizedBox(width: 4),
                  Text('Tanggapan Admin',
                      style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: Colors.blue)),
                ],
              ),
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.blue.withOpacity(0.05),
                  border: Border.all(color: Colors.blue.withOpacity(0.3)),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  pengaduan.keteranganAdmin!,
                  style: const TextStyle(fontSize: 13, height: 1.5),
                ),
              ),
            ],
            const SizedBox(height: 16),
            const Divider(),
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.access_time, size: 14, color: AppTheme.textSecondary),
                const SizedBox(width: 4),
                Text(
                  'Dilaporkan ${pengaduan.createdAt}',
                  style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary),
                ),
              ],
            ),
            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  void _showImagePreview(BuildContext context, String url) {
    showDialog(
      context: context,
      builder: (_) => Dialog(
        backgroundColor: Colors.black,
        insetPadding: const EdgeInsets.all(8),
        child: Stack(
          children: [
            InteractiveViewer(
              child: Image.network(url, fit: BoxFit.contain),
            ),
            Positioned(
              top: 8,
              right: 8,
              child: IconButton(
                icon: const Icon(Icons.close, color: Colors.white),
                onPressed: () => Navigator.pop(context),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _FormPengaduanScreen extends StatefulWidget {
  const _FormPengaduanScreen();

  @override
  State<_FormPengaduanScreen> createState() => _FormPengaduanScreenState();
}

class _FormPengaduanScreenState extends State<_FormPengaduanScreen> {
  final _formKey = GlobalKey<FormState>();
  final _judulCtrl = TextEditingController();
  final _deskripsiCtrl = TextEditingController();
  String _kategori = 'infrastruktur';
  List<String> _fotoPaths = [];
  bool _isLoading = false;

  @override
  void dispose() {
    _judulCtrl.dispose();
    _deskripsiCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickImage() async {
    if (_fotoPaths.length >= 1) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Hanya boleh upload 1 foto. Hapus dulu foto sebelumnya kalau mau ganti.')),
      );
      return;
    }

    final picker = ImagePicker();
    final image = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 70,
      maxWidth: 1600,
    );
    if (image == null) return;

    // Cek ukuran file (max 2 MB)
    final file = File(image.path);
    final sizeInBytes = await file.length();
    final sizeInMB = sizeInBytes / (1024 * 1024);
    if (sizeInMB > 2) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Ukuran gambar terlalu besar (${sizeInMB.toStringAsFixed(2)} MB). Maksimal 2 MB.'),
            backgroundColor: AppTheme.errorColor,
          ),
        );
      }
      return;
    }

    setState(() => _fotoPaths.add(image.path));
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);
    final success = await context.read<PengaduanProvider>().kirimPengaduan(
      judul: _judulCtrl.text,
      deskripsi: _deskripsiCtrl.text,
      kategori: _kategori,
      fotoPaths: _fotoPaths.isNotEmpty ? _fotoPaths : null,
    );

    if (!mounted) return;
    setState(() => _isLoading = false);

    if (success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Pengaduan berhasil dikirim!'),
          backgroundColor: AppTheme.successColor,
        ),
      );
      Navigator.pop(context);
    } else {
      // Tampilkan detail error supaya gampang diagnose
      final errMsg = context.read<PengaduanProvider>().error ?? 'Gagal mengirim pengaduan.';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Gagal: $errMsg', maxLines: 3),
          backgroundColor: AppTheme.errorColor,
          duration: const Duration(seconds: 6),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Buat Pengaduan')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextFormField(
                controller: _judulCtrl,
                decoration: const InputDecoration(
                  labelText: 'Judul Pengaduan',
                  prefixIcon: Icon(Icons.title),
                ),
                validator: (v) => v == null || v.isEmpty ? 'Judul wajib diisi' : null,
              ),
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
                value: _kategori,
                decoration: const InputDecoration(
                  labelText: 'Kategori',
                  prefixIcon: Icon(Icons.category_outlined),
                ),
                items: AppConstants.kategoriPengaduan.entries
                    .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value)))
                    .toList(),
                onChanged: (v) => setState(() => _kategori = v!),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _deskripsiCtrl,
                maxLines: 5,
                decoration: const InputDecoration(
                  labelText: 'Deskripsi Lengkap',
                  alignLabelWithHint: true,
                  prefixIcon: Padding(
                    padding: EdgeInsets.only(bottom: 80),
                    child: Icon(Icons.description_outlined),
                  ),
                ),
                validator: (v) => v == null || v.isEmpty ? 'Deskripsi wajib diisi' : null,
              ),
              const SizedBox(height: 16),
              const Text('Foto Pendukung (opsional)',
                  style: TextStyle(fontWeight: FontWeight.w600)),
              const SizedBox(height: 4),
              const Text(
                'Maksimal 1 foto, ukuran maksimal 2 MB',
                style: TextStyle(fontSize: 11, color: AppTheme.textSecondary),
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  ..._fotoPaths.map((path) => Stack(
                        clipBehavior: Clip.none,
                        children: [
                          ClipRRect(
                            borderRadius: BorderRadius.circular(10),
                            child: Image.file(
                              File(path),
                              width: 88,
                              height: 88,
                              fit: BoxFit.cover,
                              errorBuilder: (_, __, ___) => Container(
                                width: 88,
                                height: 88,
                                color: Colors.grey.shade200,
                                child: const Icon(Icons.broken_image, color: Colors.grey),
                              ),
                            ),
                          ),
                          Positioned(
                            top: -6,
                            right: -6,
                            child: GestureDetector(
                              onTap: () => setState(() => _fotoPaths.remove(path)),
                              child: Container(
                                padding: const EdgeInsets.all(3),
                                decoration: BoxDecoration(
                                  color: AppTheme.errorColor,
                                  shape: BoxShape.circle,
                                  border: Border.all(color: Colors.white, width: 2),
                                ),
                                child: const Icon(Icons.close, size: 12, color: Colors.white),
                              ),
                            ),
                          ),
                        ],
                      )),
                  if (_fotoPaths.length < 1)
                    GestureDetector(
                      onTap: _pickImage,
                      child: Container(
                        width: 88,
                        height: 88,
                        decoration: BoxDecoration(
                          color: AppTheme.primaryColor.withOpacity(0.05),
                          border: Border.all(color: AppTheme.primaryColor.withOpacity(0.5), width: 1.5),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: const Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.add_photo_alternate_outlined, color: AppTheme.primaryColor, size: 28),
                            SizedBox(height: 4),
                            Text('Tambah', style: TextStyle(fontSize: 10, color: AppTheme.primaryColor)),
                          ],
                        ),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 32),
              ElevatedButton(
                onPressed: _isLoading ? null : _submit,
                child: _isLoading
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text('Kirim Pengaduan'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
