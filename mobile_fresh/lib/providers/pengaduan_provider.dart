import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../services/api_service.dart';

class PengaduanModel {
  final int id;
  final String judul;
  final String deskripsi;
  final String kategori;
  final List<String>? foto;
  final List<String>? fotoUrls;
  final String status;
  final String prioritas;
  final String? keteranganAdmin;
  final String createdAt;

  const PengaduanModel({
    required this.id,
    required this.judul,
    required this.deskripsi,
    required this.kategori,
    this.foto,
    this.fotoUrls,
    required this.status,
    required this.prioritas,
    this.keteranganAdmin,
    required this.createdAt,
  });

  factory PengaduanModel.fromJson(Map<String, dynamic> json) {
    return PengaduanModel(
      id: json['id'],
      judul: json['judul'] ?? '',
      deskripsi: json['deskripsi'] ?? '',
      kategori: json['kategori'] ?? 'lainnya',
      foto: json['foto'] != null ? List<String>.from(json['foto']) : null,
      fotoUrls: json['foto_urls'] != null
          ? List<String>.from((json['foto_urls'] as List).where((e) => e != null))
          : null,
      status: json['status'] ?? 'baru',
      prioritas: json['prioritas'] ?? 'sedang',
      keteranganAdmin: json['keterangan_admin'],
      createdAt: json['created_at'] ?? '',
    );
  }
}

class PengaduanProvider extends ChangeNotifier {
  final _api = ApiService();

  List<PengaduanModel> _pengaduans = [];
  bool _isLoading = false;
  String? _error;

  List<PengaduanModel> get pengaduans => _pengaduans;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> load({String? status}) async {
    _isLoading = true;
    notifyListeners();
    try {
      final response = await _api.get('/pengaduan', params: status != null ? {'status': status} : null);
      _pengaduans = (response.data['data'] as List)
          .map((p) => PengaduanModel.fromJson(p))
          .toList();
    } catch (e) {
      _error = e.toString();
    }
    _isLoading = false;
    notifyListeners();
  }

  Future<bool> kirimPengaduan({
    required String judul,
    required String deskripsi,
    required String kategori,
    List<String>? fotoPaths,
  }) async {
    try {
      // Kalau ada foto → kirim sebagai multipart. Kalau tidak → kirim JSON biasa
      // (lebih reliable, LiteSpeed/ModSecurity kadang block multipart kosong).
      if (fotoPaths != null && fotoPaths.isNotEmpty) {
        final formData = FormData.fromMap({
          'judul': judul,
          'deskripsi': deskripsi,
          'kategori': kategori,
          for (int i = 0; i < fotoPaths.length; i++)
            'foto[$i]': await MultipartFile.fromFile(fotoPaths[i]),
        });
        await _api.post('/pengaduan', formData: formData);
      } else {
        await _api.post('/pengaduan', data: {
          'judul': judul,
          'deskripsi': deskripsi,
          'kategori': kategori,
        });
      }
      await load();
      return true;
    } catch (e) {
      // Parse error message ke yang lebih user-friendly
      _error = _parseError(e);
      if (kDebugMode) {
        debugPrint('❌ Pengaduan kirim error: $e');
      }
      return false;
    }
  }

  String _parseError(dynamic err) {
    try {
      if (err is DioException) {
        final resp = err.response;
        if (resp != null) {
          final data = resp.data;
          if (data is Map) {
            // Laravel validation errors
            if (data['errors'] is Map) {
              final errors = data['errors'] as Map;
              final firstField = errors.entries.firstWhere(
                (e) => e.value is List && (e.value as List).isNotEmpty,
                orElse: () => MapEntry('', []),
              );
              if (firstField.value is List && (firstField.value as List).isNotEmpty) {
                return (firstField.value as List).first.toString();
              }
            }
            if (data['message'] is String) return data['message'].toString();
          }
          return 'Server error (${resp.statusCode}). Coba lagi.';
        }
        if (err.type == DioExceptionType.connectionTimeout ||
            err.type == DioExceptionType.receiveTimeout) {
          return 'Koneksi timeout. Cek internet & coba lagi.';
        }
        return 'Tidak bisa terhubung ke server.';
      }
    } catch (_) {}
    return err.toString();
  }
}
