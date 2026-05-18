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
import 'utils/app_theme.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // NOTE: Firebase & Push Notification dinonaktifkan sementara
  // sampai google-services.json tersedia.
  // try {
  //   await Firebase.initializeApp();
  //   await NotificationService.initialize();
  // } catch (e) {
  //   debugPrint('Firebase init skipped: $e');
  // }

  final prefs = await SharedPreferences.getInstance();
  final language = prefs.getString('language') ?? 'id';

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
      ],
      child: Consumer2<LocaleProvider, AuthProvider>(
        builder: (context, localeProvider, authProvider, _) {
          return MaterialApp(
            title: 'IPL Griya Pesona Madani',
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
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFFE8F5E9), Colors.white, Color(0xFFE8F5E9)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            stops: [0.0, 0.5, 1.0],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              const Spacer(flex: 2),
              // Logo dengan card putih
              Container(
                margin: const EdgeInsets.symmetric(horizontal: 32),
                padding: const EdgeInsets.all(32),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(28),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF1B5E20).withOpacity(0.08),
                      blurRadius: 24,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Image.asset(
                      'assets/images/logo.png',
                      width: 220,
                      fit: BoxFit.contain,
                    ),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1B5E20).withOpacity(0.08),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Text(
                        'Tenjo • Blok E',
                        style: TextStyle(
                          color: Color(0xFF1B5E20),
                          fontSize: 12,
                          letterSpacing: 1.0,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const Spacer(flex: 1),
              const SizedBox(
                width: 36,
                height: 36,
                child: CircularProgressIndicator(
                  strokeWidth: 3,
                  valueColor: AlwaysStoppedAnimation(Color(0xFF1B5E20)),
                ),
              ),
              const SizedBox(height: 16),
              const Text(
                'Menghubungkan...',
                style: TextStyle(
                  color: Color(0xFF757575),
                  fontSize: 12,
                  letterSpacing: 0.5,
                ),
              ),
              const Spacer(flex: 2),
              const Padding(
                padding: EdgeInsets.only(bottom: 24),
                child: Text(
                  'Created by Edi Prasetiyo',
                  style: TextStyle(
                    color: Color(0xFF9E9E9E),
                    fontSize: 11,
                    letterSpacing: 0.3,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
