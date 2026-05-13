import 'package:flutter/foundation.dart';
import '../models/ipl_model.dart';
import '../services/api_service.dart';

class IplProvider extends ChangeNotifier {
  final _api = ApiService();

  List<TagihanModel> _tagihans = [];
  TagihanModel? _tagihanBulanIni;
  List<TagihanModel> _tunggakan = [];
  List<PembayaranModel> _riwayat = [];
  bool _isLoading = false;
  String? _error;

  List<TagihanModel> get tagihans => _tagihans;
  TagihanModel? get tagihanBulanIni => _tagihanBulanIni;
  List<TagihanModel> get tunggakan => _tunggakan;
  List<PembayaranModel> get riwayat => _riwayat;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> loadTagihanBulanIni() async {
    _isLoading = true;
    notifyListeners();
    try {
      final response = await _api.get('/ipl/tagihan/bulan-ini');
      final data = response.data['tagihan'];
      _tagihanBulanIni = data != null ? TagihanModel.fromJson(data) : null;
    } catch (e) {
      _error = e.toString();
    }
    _isLoading = false;
    notifyListeners();
  }

  Future<void> loadTunggakan() async {
    try {
      final response = await _api.get('/ipl/tunggakan');
      _tunggakan = (response.data['tunggakan'] as List)
          .map((t) => TagihanModel.fromJson(t))
          .toList();
      notifyListeners();
    } catch (e) {
      _error = e.toString();
    }
  }

  Future<void> loadRiwayat() async {
    try {
      final response = await _api.get('/ipl/pembayaran');
      _riwayat = (response.data['data'] as List)
          .map((p) => PembayaranModel.fromJson(p))
          .toList();
      notifyListeners();
    } catch (e) {
      _error = e.toString();
    }
  }

  Future<Map<String, dynamic>?> bayarTagihan(int tagihanId) async {
    try {
      final response = await _api.post('/ipl/tagihan/$tagihanId/bayar');
      return response.data;
    } catch (e) {
      _error = e.toString();
      return null;
    }
  }
}
