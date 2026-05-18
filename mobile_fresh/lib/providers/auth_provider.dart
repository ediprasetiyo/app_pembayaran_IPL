import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user_model.dart';
import '../services/api_service.dart';

class AuthProvider extends ChangeNotifier {
  UserModel? _user;
  bool _isLoading = true;
  String? _error;

  UserModel? get user => _user;
  bool get isLoading => _isLoading;
  bool get isAuthenticated => _user != null;
  String? get error => _error;

  final _api = ApiService();

  AuthProvider() {
    _api.init();
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    try {
      final token = await _api.getToken();
      if (token != null) {
        try {
          // Timeout 8 detik biar tidak stuck kalau server lambat
          final response = await _api
              .get('/auth/me')
              .timeout(const Duration(seconds: 8));
          _user = UserModel.fromJson(response.data['user']);
        } catch (_) {
          // Token invalid/expired/server error → hapus & lanjut ke login
          await _api.deleteToken();
        }
      }
    } catch (_) {
      // Jangan biarkan exception apapun blokir splash transition
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> login(String phone, String password) async {
    _error = null;
    try {
      // Pakai default timeout Dio (30 detik) — koneksi mobile kadang lambat
      final response = await _api.post('/auth/login', data: {
        'phone': phone,
        'password': password,
      });

      // Save token DULU sebelum parse user, biar request lain bisa pakai token
      if (response.data is Map && response.data['token'] != null) {
        await _api.saveToken(response.data['token']);
      }

      try {
        _user = UserModel.fromJson(response.data['user']);
      } catch (parseErr) {
        // Parsing user gagal — login secara teknis sukses (token diterima),
        // tapi user data tidak bisa di-parse. Bersihkan token & beri pesan.
        await _api.deleteToken();
        _error = 'Format data user dari server tidak sesuai. Detail: $parseErr';
        notifyListeners();
        return false;
      }

      try {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('language', _user!.language);
      } catch (_) {/* prefs failure tidak boleh blok login */}

      notifyListeners();
      return true;
    } catch (e) {
      _error = _parseError(e);
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    try {
      await _api.post('/auth/logout');
    } catch (_) {}
    await _api.deleteToken();
    _user = null;
    notifyListeners();
  }

  Future<bool> updateProfile(Map<String, dynamic> data, {String? avatarPath}) async {
    try {
      dynamic response;
      if (avatarPath != null && avatarPath.isNotEmpty) {
        // Upload via POST multipart
        final formData = FormData.fromMap({
          ...data,
          'avatar': await MultipartFile.fromFile(avatarPath),
        });
        response = await _api.post('/auth/profile', formData: formData);
      } else {
        response = await _api.put('/auth/profile', data: data);
      }
      _user = UserModel.fromJson(response.data['user']);
      notifyListeners();
      return true;
    } catch (e) {
      _error = _parseError(e);
      return false;
    }
  }

  Future<bool> changePassword(String oldPassword, String newPassword) async {
    try {
      await _api.put('/auth/change-password', data: {
        'current_password': oldPassword,
        'password': newPassword,
        'password_confirmation': newPassword,
      });
      return true;
    } catch (e) {
      _error = _parseError(e);
      return false;
    }
  }

  Future<Map<String, dynamic>> forgotPassword(String phone) async {
    try {
      final response = await _api.post('/auth/forgot-password', data: {'phone': phone});
      return {'success': true, 'data': response.data};
    } catch (e) {
      return {'success': false, 'error': _parseError(e)};
    }
  }

  String _parseError(dynamic error) {
    // Coba ambil response dari DioException (bisa DioException, DioError, dll)
    try {
      final response = (error as dynamic).response;
      if (response != null && response.data is Map) {
        final data = response.data as Map;
        // Cek message field standar Laravel
        if (data['message'] is String) return data['message'] as String;
        // Cek errors field (validation errors Laravel)
        if (data['errors'] is Map) {
          final errors = data['errors'] as Map;
          final firstField = errors.values.first;
          if (firstField is List && firstField.isNotEmpty) {
            return firstField.first.toString();
          }
        }
      }
    } catch (_) {}

    // Cek error type untuk pesan yg lebih spesifik
    final errStr = error.toString().toLowerCase();
    if (errStr.contains('connectiontimeout') || errStr.contains('connection timeout')) {
      return 'Koneksi timeout. Periksa internet Anda.';
    }
    if (errStr.contains('connectionerror') || errStr.contains('socketexception')) {
      return 'Tidak bisa terhubung ke server. Pastikan internet aktif.';
    }
    if (errStr.contains('certificate') || errStr.contains('handshake')) {
      return 'Masalah sertifikat SSL. Coba update aplikasi.';
    }

    return 'Terjadi kesalahan: ${error.toString().substring(0, error.toString().length > 100 ? 100 : error.toString().length)}';
  }
}
