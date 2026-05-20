import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_gen/gen_l10n/app_localizations.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'providers/auth_provider.dart';
import 'providers/ipl_provider.dart';
import 'providers/news_provider.dart';
import 'providers/notifikasi_provider.dart';
import 'providers/pengaduan_provider.dart';
import 'providers/locale_provider.dart';
import 'screens/auth/login_screen.dart';
import 'screens/home/home_screen.dart';
import 'services/app_settings.dart';
import 'services/api_service.dart';
import 'services/notification_service.dart';
import 'utils/app_theme.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Init API service dulu supaya AppSettings bisa pakai
  try { ApiService().init(); } catch (_) {}

  // 1. Load settings dari CACHE saja (instant — no network). Awaited.
  //    Network refresh dilakukan async di background.
  await AppSettings.loadFromCache();
  // Fire-and-forget network refresh (tidak block startup)
  AppSettings.refreshFromNetwork();

  // 2. Init Firebase + FCM — wrap dengan timeout supaya tidak hang
  //    Kalau gagal/timeout, app tetap jalan (notifikasi cuma tidak aktif).
  Future(() async {
    try {
      await Firebase.initializeApp().timeout(const Duration(seconds: 8));
      FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
      await NotificationService().initialize().timeout(const Duration(seconds: 8));
    } catch (e) {
      debugPrint('⚠️ Firebase init skipped/timeout: $e');
    }
  }); // fire-and-forget

  // 3. Load language dari SharedPreferences
  String language = 'id';
  try {
    final prefs = await SharedPreferences.getInstance()
        .timeout(const Duration(seconds: 2));
    language = prefs.getString('language') ?? 'id';
  } catch (_) {}

  runApp(MyApp(initialLanguage: language));
}

class MyApp extends StatelessWidget {
  final String initialLanguage;

  const MyApp({super.key, required this.initialLanguage});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => LocaleProvider(initialLanguage)),
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => IplProvider()),
        ChangeNotifierProvider(create: (_) => NewsProvider()),
        ChangeNotifierProvider(create: (_) => NotifikasiProvider()),
        ChangeNotifierProvider(create: (_) => PengaduanProvider()),
        // White-label settings reactive notifier — UI rebuild kalau settings berubah
        ChangeNotifierProvider.value(value: appSettingsNotifier),
      ],
      child: Consumer3<LocaleProvider, AuthProvider, AppSettingsNotifier>(
        builder: (context, localeProvider, authProvider, _, __) {
          return MaterialApp(
            title: AppSettings.appName,
            debugShowCheckedModeBanner: false,
            theme: AppTheme.light,
            locale: Locale(localeProvider.language),
            supportedLocales: const [
              Locale('id'),
              Locale('en'),
            ],
            localizationsDelegates: const [
              AppLocalizations.delegate,
              GlobalMaterialLocalizations.delegate,
              GlobalWidgetsLocalizations.delegate,
              GlobalCupertinoLocalizations.delegate,
            ],
            home: authProvider.isLoading
                ? const _SplashScreen()
                : authProvider.isAuthenticated
                    ? const HomeScreen()
                    : const LoginScreen(),
          );
        },
      ),
    );
  }
}

class _SplashScreen extends StatelessWidget {
  const _SplashScreen();

  @override
  Widget build(BuildContext context) {
    // Pakai logo dari backend (network) kalau ada, fallback ke asset bundle
    Widget logoWidget;
    final url = AppSettings.logoUrl;
    if (url.isNotEmpty && url.startsWith('http')) {
      logoWidget = Image.network(
        url,
        width: 240,
        fit: BoxFit.contain,
        errorBuilder: (_, __, ___) => Image.asset('assets/images/logo.png', width: 240),
      );
    } else {
      logoWidget = Image.asset('assets/images/logo.png', width: 240, fit: BoxFit.contain);
    }

    return Scaffold(
      backgroundColor: Colors.white,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Padding(
              padding: const EdgeInsets.all(24),
              child: logoWidget,
            ),
            const SizedBox(height: 12),
            Text(
              AppSettings.brandSubtitle,
              style: const TextStyle(
                color: Color(0xFF757575),
                fontSize: 14,
                letterSpacing: 1.2,
              ),
            ),
            const SizedBox(height: 56),
            SizedBox(
              width: 32,
              height: 32,
              child: CircularProgressIndicator(
                strokeWidth: 3,
                valueColor: AlwaysStoppedAnimation(AppTheme.primaryColor),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
