import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';

/// White-label settings yang loaded dari backend (/settings/public).
/// Setelah load, value bisa diakses lewat static getter:
///   AppSettings.primaryColor
///   AppSettings.logoUrl
///   AppSettings.brandTitle
///
/// Cache di SharedPreferences supaya app tetap branded saat offline.
class AppSettings {
  // Default fallback values (kalau backend belum punya setting)
  static const String _defaultAppName = 'IPL Griya Pesona Madani';
  static const String _defaultBrandTitle = 'Griya Pesona';
  static const String _defaultBrandSubtitle = 'Madani Tenjo';
  static const String _defaultFooter = 'Perumahan Griya Pesona Madani Tenjo - Blok E';
  static const String _defaultLogoUrl = '';
  static const Color _defaultPrimary = Color(0xFF1B5E20);
  static const Color _defaultPrimaryDark = Color(0xFF0D3D12);
  static const Color _defaultPrimaryLight = Color(0xFFE8F5E9);
  static const Color _defaultAccent = Color(0xFFFFC107);

  static Map<String, dynamic> _data = {};
  static bool _loaded = false;
  static bool get isLoaded => _loaded;

  // ==== STRINGS ====
  static String get appName => _data['app_name']?.toString() ?? _defaultAppName;
  static String get brandTitle => _data['brand_title']?.toString() ?? _defaultBrandTitle;
  static String get brandSubtitle => _data['brand_subtitle']?.toString() ?? _defaultBrandSubtitle;
  static String get footerText => _data['footer_text']?.toString() ?? _defaultFooter;
  static String get logoUrl {
    final url = _data['logo_url']?.toString() ?? '';
    if (url.isEmpty) return _defaultLogoUrl;
    if (url.startsWith('http')) return url;
    // Prepend backend host untuk URL relative (/storage/...)
    return 'https://ipl-griya-pesona-madani.my.id$url';
  }

  // ==== COLORS ====
  static Color get primaryColor => _parseColor(_data['theme_primary']) ?? _defaultPrimary;
  static Color get primaryDarkColor => _parseColor(_data['theme_primary_dark']) ?? _defaultPrimaryDark;
  static Color get primaryLightColor => _parseColor(_data['theme_primary_light']) ?? _defaultPrimaryLight;
  static Color get accentColor => _parseColor(_data['theme_accent']) ?? _defaultAccent;

  /// Load settings dari backend. Pakai cache SharedPreferences kalau offline.
  /// Panggil ini sebelum runApp() di main.dart.
  static Future<void> load() async {
    // 1. Load dari cache dulu (instant, tanpa network)
    try {
      final prefs = await SharedPreferences.getInstance();
      final cached = prefs.getString('app_settings_cache');
      if (cached != null) {
        _data = jsonDecode(cached) as Map<String, dynamic>;
        _loaded = true;
      }
    } catch (_) {}

    // 2. Coba refresh dari network (non-blocking kalau cache sudah ada)
    try {
      final api = ApiService();
      // init() butuh dipanggil. Tapi mungkin udah dipanggil di AuthProvider.
      // Untuk safety:
      try { api.init(); } catch (_) {}

      final response = await api.get('/settings/public').timeout(
        const Duration(seconds: 5),
      );
      final incoming = response.data['settings'];
      if (incoming is Map) {
        _data = Map<String, dynamic>.from(incoming);
        _loaded = true;

        // Update cache
        try {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('app_settings_cache', jsonEncode(_data));
        } catch (_) {}
      }
    } catch (e) {
      debugPrint('⚠️ AppSettings load gagal (pakai cache/default): $e');
    }
  }

  /// Reload manual — bisa dipanggil setelah login atau pull-to-refresh.
  static Future<void> refresh() => load();

  static Color? _parseColor(dynamic value) {
    if (value == null) return null;
    final s = value.toString().replaceAll('#', '');
    if (s.length != 6 && s.length != 8) return null;
    try {
      return Color(int.parse(s.length == 6 ? 'FF$s' : s, radix: 16));
    } catch (_) {
      return null;
    }
  }
}
