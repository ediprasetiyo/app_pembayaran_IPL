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
    final token = await _api.getToken();
    if (token != null) {
      try {
        final response = await _api.get('/auth/me');
        _user = UserModel.fromJson(response.data['user']);
      } catch (_) {
        await _api.deleteToken();
      }
    }
    _isLoading = false;
    notifyListeners();
  }

  Future<bool> login(String phone, String password) async {
    _error = null;
    try {
      final response = await _api.post('/auth/login', data: {
        'phone': phone,
        'password': password,
      });

      await _api.saveToken(response.data['token']);
      _user = UserModel.fromJson(response.data['user']);

      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('language', _user!.language);

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
    if (error.runtimeType.toString().contains('DioException')) {
      final response = (error as dynamic).response;
      if (response != null) {
        if (response.data is Map) {
          return response.data['message'] ?? 'Terjadi kesalahan.';
        }
      }
    }
    return 'Terjadi kesalahan. Periksa koneksi internet.';
  }
}
