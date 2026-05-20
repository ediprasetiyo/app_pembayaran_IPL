import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../utils/constants.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();

  late Dio _dio;
  bool _initialized = false;
  final _storage = const FlutterSecureStorage();

  void init() {
    // Idempotent — panggil berulang aman (singleton)
    if (_initialized) return;
    _initialized = true;

    _dio = Dio(BaseOptions(
      baseUrl: AppConstants.baseUrl,
      connectTimeout: const Duration(seconds: 30),
      // Receive timeout longer karena upload pengaduan/avatar
      // bisa makan waktu (multipart + Cloudinary fallback).
      receiveTimeout: const Duration(seconds: 60),
      sendTimeout: const Duration(seconds: 60),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
    ));

    // Cache token in memory agar tidak baca storage tiap request
    // (FlutterSecureStorage hang di beberapa device Xiaomi/MIUI)
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        // Skip token lookup untuk endpoint login & public
        final path = options.path;
        if (path.contains('/auth/login') ||
            path.contains('/auth/register') ||
            path.contains('/auth/forgot-password') ||
            path.contains('/settings/public') ||
            path.contains('/health') ||
            path.contains('/midtrans/callback')) {
          handler.next(options);
          return;
        }

        // Pakai cached token kalau ada, kalau tidak baru baca storage dengan timeout
        if (_cachedToken == null) {
          try {
            _cachedToken = await _storage.read(key: 'auth_token')
                .timeout(const Duration(seconds: 3));
          } catch (_) {
            // Storage gagal/hang → coba lanjut tanpa token
          }
        }
        if (_cachedToken != null) {
          options.headers['Authorization'] = 'Bearer $_cachedToken';
        }
        handler.next(options);
      },
      onError: (DioException e, handler) {
        // Kalau 401, clear cached token
        if (e.response?.statusCode == 401) {
          _cachedToken = null;
        }
        handler.next(e);
      },
    ));
  }

  String? _cachedToken;

  Future<Response> get(String path, {Map<String, dynamic>? params}) =>
      _dio.get(path, queryParameters: params);

  Future<Response> post(String path, {dynamic data, FormData? formData}) {
    if (formData != null) {
      return _dio.post(
        path,
        data: formData,
        options: Options(contentType: 'multipart/form-data'),
      );
    }
    return _dio.post(path, data: data);
  }

  Future<Response> put(String path, {dynamic data}) =>
      _dio.put(path, data: data);

  Future<Response> delete(String path) => _dio.delete(path);

  Future<void> saveToken(String token) async {
    _cachedToken = token; // cache di memory dulu (instant)
    try {
      await _storage.write(key: 'auth_token', value: token)
          .timeout(const Duration(seconds: 3));
    } catch (_) {
      // Storage gagal — token tetap ada di memory, request bisa lanjut
    }
  }

  Future<void> deleteToken() async {
    _cachedToken = null;
    try {
      await _storage.delete(key: 'auth_token')
          .timeout(const Duration(seconds: 3));
    } catch (_) {}
  }

  Future<String?> getToken() async {
    if (_cachedToken != null) return _cachedToken;
    try {
      _cachedToken = await _storage.read(key: 'auth_token')
          .timeout(const Duration(seconds: 3));
      return _cachedToken;
    } catch (_) {
      return null;
    }
  }
}
