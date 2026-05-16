import 'package:flutter/foundation.dart';
import '../services/api_service.dart';

class NotifikasiModel {
  final int id;
  final String judul;
  final String pesan;
  final String tipe;
  final bool isRead;
  final String createdAt;

  const NotifikasiModel({
    required this.id,
    required this.judul,
    required this.pesan,
    required this.tipe,
    required this.isRead,
    required this.createdAt,
  });

  factory NotifikasiModel.fromJson(Map<String, dynamic> json) {
    return NotifikasiModel(
      id: json['id'],
      judul: json['judul'],
      pesan: json['pesan'],
      tipe: json['tipe'],
      isRead: json['is_read'] ?? false,
      createdAt: json['created_at'],
    );
  }
}

class NotifikasiProvider extends ChangeNotifier {
  final _api = ApiService();

  List<NotifikasiModel> _notifikasis = [];
  int _unreadCount = 0;
  bool _isLoading = false;

  List<NotifikasiModel> get notifikasis => _notifikasis;
  int get unreadCount => _unreadCount;
  bool get isLoading => _isLoading;

  Future<void> load() async {
    _isLoading = true;
    notifyListeners();
    try {
      final response = await _api.get('/notifikasi');
      _notifikasis = (response.data['notifikasi']['data'] as List)
          .map((n) => NotifikasiModel.fromJson(n))
          .toList();
      _unreadCount = response.data['unread_count'];
    } catch (_) {}
    _isLoading = false;
    notifyListeners();
  }

  Future<void> markRead(int id) async {
    try {
      await _api.put('/notifikasi/$id/read');
      _notifikasis = _notifikasis
          .map((n) => n.id == id
              ? NotifikasiModel(
                  id: n.id,
                  judul: n.judul,
                  pesan: n.pesan,
                  tipe: n.tipe,
                  isRead: true,
                  createdAt: n.createdAt,
                )
              : n)
          .toList();
      _unreadCount = _notifikasis.where((n) => !n.isRead).length;
      notifyListeners();
    } catch (_) {}
  }

  Future<void> markAllRead() async {
    try {
      await _api.put('/notifikasi/read-all');
      await load();
    } catch (_) {}
  }
}
