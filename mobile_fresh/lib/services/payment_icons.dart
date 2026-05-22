import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';

/// Cache & service untuk payment method icons (logo + nama brand).
/// Fetched dari backend (/payment-methods/icons), cached lokal.
/// Dipakai di Riwayat Pembayaran untuk tampilkan logo brand DANA/GoPay/dll.
class PaymentIcons {
  static Map<String, Map<String, dynamic>> _map = {};
  static bool _loaded = false;

  static bool get isLoaded => _loaded;

  /// Mapping Midtrans-generic codes ke internal codes kita.
  /// Karena Midtrans webhook return 'bank_transfer' untuk semua VA (BCA/BNI/BRI/Permata),
  /// kita map ke salah satu untuk display logo.
  static const _aliases = {
    'bank_transfer': 'bca_va', // fallback generic VA → BCA icon
    'cstore': 'indomaret',     // convenience store → Indomaret icon
    'echannel': 'other_va',    // Mandiri bill → generic bank
  };

  /// Get info untuk 1 payment method code.
  /// Return: { name, logo_url, icon, category } atau null.
  static Map<String, dynamic>? get(String? code) {
    if (code == null || code.isEmpty) return null;
    // Try direct lookup
    if (_map.containsKey(code)) return _map[code];
    // Try alias lookup
    final alias = _aliases[code];
    if (alias != null && _map.containsKey(alias)) return _map[alias];
    return null;
  }

  /// Dapatkan logo URL untuk method tertentu (atau null).
  static String? logoUrl(String? code) {
    final info = get(code);
    final url = info?['logo_url']?.toString();
    return (url != null && url.isNotEmpty) ? url : null;
  }

  /// Dapatkan emoji icon fallback.
  static String emojiIcon(String? code) {
    final info = get(code);
    return info?['icon']?.toString() ?? '💳';
  }

  /// Dapatkan nama brand (e.g., "GoPay", "DANA", "BCA Virtual Account").
  static String name(String? code) {
    if (code == null || code.isEmpty) return 'Pembayaran';
    final info = get(code);
    final n = info?['name']?.toString();
    if (n != null && n.isNotEmpty) return n;
    // Fallback ke uppercase code
    return code.toUpperCase().replaceAll('_', ' ');
  }

  /// Load dari cache lokal — instant, no network.
  static Future<void> loadFromCache() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final cached = prefs.getString('payment_icons_cache');
      if (cached != null) {
        final decoded = jsonDecode(cached) as Map<String, dynamic>;
        _map = decoded.map(
          (k, v) => MapEntry(k, Map<String, dynamic>.from(v as Map)),
        );
        _loaded = true;
      }
    } catch (_) {}
  }

  /// Refresh dari backend (fire-and-forget).
  static Future<void> refreshFromNetwork() async {
    try {
      final api = ApiService();
      final res = await api.get('/payment-methods/icons').timeout(
        const Duration(seconds: 5),
      );
      final incoming = res.data['methods'];
      if (incoming is Map) {
        _map = Map<String, dynamic>.from(incoming).map(
          (k, v) => MapEntry(k.toString(), Map<String, dynamic>.from(v as Map)),
        );
        _loaded = true;
        try {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('payment_icons_cache', jsonEncode(_map));
        } catch (_) {}
      }
    } catch (e) {
      debugPrint('⚠️ PaymentIcons load gagal: $e');
    }
  }
}
