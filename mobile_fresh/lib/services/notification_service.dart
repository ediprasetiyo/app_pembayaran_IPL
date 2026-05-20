import 'dart:convert';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

/// Background handler — MUST be top-level / static
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  // Background: cukup log. FCM sudah otomatis tampilkan notif kalau ada `notification` payload.
  debugPrint('🔔 BG notification: ${message.messageId} / ${message.notification?.title}');
}

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final FlutterLocalNotificationsPlugin _localNotif = FlutterLocalNotificationsPlugin();
  String? _fcmToken;
  String? get fcmToken => _fcmToken;

  /// Channel ID harus sama dengan default_notification_channel_id di AndroidManifest.xml
  static const AndroidNotificationChannel _highChannel = AndroidNotificationChannel(
    'ipl_high_importance_channel',
    'IPL Notifikasi Penting',
    description: 'Reminder tagihan, pembayaran, dan pengumuman penting.',
    importance: Importance.high,
    playSound: true,
    enableVibration: true,
  );

  Future<void> initialize() async {
    // 1. Setup local notifications (untuk display di foreground)
    const androidInit = AndroidInitializationSettings('@mipmap/launcher_icon');
    const initSettings = InitializationSettings(android: androidInit);

    await _localNotif.initialize(
      initSettings,
      onDidReceiveNotificationResponse: (response) {
        debugPrint('Notif tapped: ${response.payload}');
      },
    );

    // 2. Buat channel di Android 8+ (kalau belum ada)
    await _localNotif
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(_highChannel);

    // 3. Minta permission notifikasi (Android 13+ & iOS)
    final settings = await FirebaseMessaging.instance.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );
    debugPrint('FCM permission: ${settings.authorizationStatus}');

    // 4. Get FCM token
    try {
      _fcmToken = await FirebaseMessaging.instance.getToken();
      debugPrint('🔑 FCM Token: $_fcmToken');
    } catch (e) {
      debugPrint('Gagal getToken: $e');
    }

    // 5. Refresh token listener
    FirebaseMessaging.instance.onTokenRefresh.listen((newToken) {
      _fcmToken = newToken;
      debugPrint('🔄 FCM token refreshed: $newToken');
      // TODO: bisa kirim ke backend lagi via /auth/profile
    });

    // 6. Foreground message handler — tampilkan local notification manual
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      debugPrint('📩 FG message: ${message.notification?.title}');
      _showLocalNotification(message);
    });

    // 7. App opened from notification (background → tap notif)
    FirebaseMessaging.onMessageOpenedApp.listen((message) {
      debugPrint('👆 App opened from notif: ${message.data}');
    });
  }

  void _showLocalNotification(RemoteMessage message) {
    final notif = message.notification;
    if (notif == null) return;

    _localNotif.show(
      DateTime.now().millisecondsSinceEpoch ~/ 1000,
      notif.title ?? 'Notifikasi IPL',
      notif.body ?? '',
      NotificationDetails(
        android: AndroidNotificationDetails(
          _highChannel.id,
          _highChannel.name,
          channelDescription: _highChannel.description,
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/launcher_icon',
          playSound: true,
          enableVibration: true,
        ),
      ),
      payload: jsonEncode(message.data),
    );
  }
}
