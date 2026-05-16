import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../services/api_service.dart';

class PengaduanModel {
  final int id;
  final String judul;
  final String deskripsi;
  final String kategori;
  final List<String> foto;
  final String status;
  final String prioritas;
  final String? keteranganAdmin;
  final String createdAt;

  const PengaduanModel({
    required this.id,
    required this.judul,
    required this.deskripsi,
    required this.kategori,
    required this.foto,
    required this.status,
    required this.prioritas,
    this.keteranganAdmin,
    required this.createdAt,
  });

  factory PengaduanModel.fromJson(Map<String, dynamic> json) {
    return PengaduanModel(
      id: json['id'],
      judul: json['judul'],
      deskripsi: json['deskripsi'],
      kategori: json['kategori'],
      foto: json['foto'] != null ? List<String>.from(json['foto']) : [],
      status: json['status'],
      prioritas: json['prioritas'],
      keteranganAdmin: json['keterangan_admin'],
      createdAt: json['created_at'],
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
      final formData = FormData.fromMap({
        'judul': judul,
        'deskripsi': deskripsi,
        'kategori': kategori,
        if (fotoPaths != null)
          for (int i = 0; i < fotoPaths.length; i++)
            'foto[$i]': await MultipartFile.fromFile(fotoPaths[i]),
      });

      await _api.post('/pengaduan', formData: formData);
      await load();
      return true;
    } catch (e) {
      _error = e.toString();
      return false;
    }
  }
}
