import 'package:flutter/foundation.dart';
import '../models/ipl_model.dart';
import '../services/api_service.dart';

class IplProvider extends ChangeNotifier {
  final _api = ApiService();

  List<TagihanModel> _tagihans = [];
  TagihanModel? _tagihanBulanIni;
  List<TagihanModel> _tagihanBulanIniList = [];
  double _totalBulanIni = 0;
  double _totalBelumBayar = 0;
  List<TagihanModel> _tunggakan = [];
  List<PembayaranModel> _riwayat = [];
  bool _isLoading = false;
  String? _error;

  List<TagihanModel> get tagihans => _tagihans;
  TagihanModel? get tagihanBulanIni => _tagihanBulanIni;
  List<TagihanModel> get tagihanBulanIniList => _tagihanBulanIniList;
  double get totalBulanIni => _totalBulanIni;
  double get totalBelumBayar => _totalBelumBayar;
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

      final List list = response.data['tagihans'] ?? [];
      _tagihanBulanIniList = list.map((e) => TagihanModel.fromJson(e)).toList();
      _totalBulanIni = (response.data['total_keseluruhan'] ?? 0).toDouble();
      _totalBelumBayar = (response.data['total_belum_bayar'] ?? 0).toDouble();
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

  /// Fetch payment method list (yang aktif dari backoffice) dengan biaya
  /// admin sudah dihitung untuk tagihan ini.
  Future<List<Map<String, dynamic>>?> getPaymentMethods(int tagihanId) async {
    try {
      final res = await _api.get('/ipl/tagihan/$tagihanId/payment-methods');
      final list = (res.data['methods'] as List?) ?? [];
      return list.map<Map<String, dynamic>>((e) => Map<String, dynamic>.from(e)).toList();
    } catch (e) {
      _error = e.toString();
      return null;
    }
  }

  Future<Map<String, dynamic>?> bayarTagihan(int tagihanId, {String? paymentMethod}) async {
    try {
      // Kirim payment_method kalau user sudah pilih → backend hitung fee spesifik
      final body = <String, dynamic>{};
      if (paymentMethod != null && paymentMethod.isNotEmpty) {
        body['payment_method'] = paymentMethod;
      }
      final response = await _api.post('/ipl/tagihan/$tagihanId/bayar', data: body);
      return response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : null;
    } catch (e) {
      _error = e.toString();
      return null;
    }
  }

  /// Poll status pembayaran dari Midtrans via backend
  /// Backend akan call Midtrans API untuk cek status real-time
  Future<String?> cekStatusPembayaran(int pembayaranId) async {
    try {
      final response = await _api.get('/ipl/pembayaran/$pembayaranId');
      final pembayaran = response.data['pembayaran'];
      if (pembayaran is Map) {
        return pembayaran['status']?.toString();
      }
    } catch (_) {}
    return null;
  }

  /// Refresh semua data tagihan + pembayaran (panggil setelah bayar)
  Future<void> refreshAll() async {
    await Future.wait([
      loadTagihanBulanIni(),
      loadTunggakan(),
      loadRiwayat(),
    ]);
  }
}
