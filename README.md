# Sistem Pembayaran IPL - Perumahan Griya Pesona Madani Tenjo

Sistem manajemen IPL (Iuran Pemeliharaan Lingkungan) terintegrasi untuk Perumahan Griya Pesona Madani Tenjo, Blok E.

## Arsitektur Sistem

```
┌──────────────────┐     ┌──────────────────────┐     ┌──────────────────┐
│  Mobile (Flutter) │────▶│  Backend API (Laravel) │◀────│  Back Office (Vue)│
│  Android & iOS   │     │  MySQL Database        │     │  Admin Panel     │
└──────────────────┘     └──────────────────────┘     └──────────────────┘
         │                          │
         │                    ┌─────▼──────┐
         └────────────────────▶  Midtrans  │
                              │  (Payment) │
                              └────────────┘
```

---

## 1. Backend Laravel API

### Persyaratan
- PHP >= 8.1
- Composer
- MySQL 8.0+
- Firebase Project (untuk FCM)
- Akun Midtrans (Sandbox/Production)

### Instalasi

```bash
cd backend

# Install dependencies
composer install

# Salin dan konfigurasi .env
cp .env.example .env
php artisan key:generate

# Konfigurasi database di .env
DB_DATABASE=ipl_griya_pesona
DB_USERNAME=root
DB_PASSWORD=

# Konfigurasi Midtrans di .env
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxx
MIDTRANS_IS_PRODUCTION=false

# Konfigurasi Firebase (letakkan file credentials di root)
FIREBASE_CREDENTIALS=firebase-credentials.json

# Konfigurasi Admin WA
ADMIN_PHONE=6281234567890

# IPL Amount
IPL_MONTHLY_AMOUNT=150000

# Jalankan migrasi
php artisan migrate

# Buat admin pertama
php artisan tinker
>>> User::create(['name' => 'Admin', 'phone' => '08xxx', 'password' => bcrypt('password'), 'role' => 'admin'])

# Install Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Jalankan server
php artisan serve
```

### Scheduler (Cron Job)
Tambahkan di crontab server:
```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 2. Mobile Flutter

### Persyaratan
- Flutter SDK >= 3.0.0
- Android Studio / Xcode
- Firebase project (Android & iOS)

### Instalasi

```bash
cd mobile

# Install dependencies
flutter pub get

# Generate localization
flutter gen-l10n

# Konfigurasi API URL di lib/utils/constants.dart
# Ubah baseUrl sesuai IP server backend

# Konfigurasi Midtrans di constants.dart
# Ubah midtransClientKey

# Setup Firebase
# - Download google-services.json (Android) → android/app/
# - Download GoogleService-Info.plist (iOS) → ios/Runner/

# Jalankan
flutter run
```

### Fitur Mobile
- ✅ Login dengan nomor telepon
- ✅ Dashboard tagihan IPL bulan ini
- ✅ Pembayaran via Midtrans (Snap)
- ✅ Daftar tunggakan
- ✅ Riwayat pembayaran
- ✅ Form pengaduan dengan foto
- ✅ Notifikasi Push (FCM)
- ✅ Multi-bahasa (Indonesia & English)
- ✅ Lupa password → WhatsApp admin

---

## 3. Back Office Vue.js

### Persyaratan
- Node.js >= 18
- npm / yarn

### Instalasi

```bash
cd backoffice

# Install dependencies
npm install

# Jalankan development
npm run dev

# Build production
npm run build
```

Akses di: `http://localhost:5173`

### Fitur Back Office
- ✅ Dashboard dengan statistik & chart
- ✅ Manajemen data warga (CRUD)
- ✅ Input detail anggota keluarga
- ✅ Manajemen tagihan IPL
- ✅ Generate tagihan bulanan
- ✅ Riwayat pembayaran Midtrans
- ✅ Kelola pengaduan warga
- ✅ Laporan & rekapitulasi per bulan

---

## Database Schema

```sql
users             - Data akun pengguna (admin/warga)
warga             - Data detail hunian warga
anggota_keluarga  - Data anggota keluarga
ipl_tagihan       - Tagihan IPL per bulan per rumah
pembayaran        - Transaksi pembayaran via Midtrans
pengaduan         - Laporan/keluhan warga
notifikasi        - Notifikasi in-app
tarif_ipl         - Konfigurasi tarif IPL per tahun
```

---

## API Endpoints

### Auth
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/v1/auth/login` | Login dengan nomor telepon |
| POST | `/api/v1/auth/logout` | Logout |
| GET | `/api/v1/auth/me` | Data user yang login |
| POST | `/api/v1/auth/forgot-password` | Dapatkan link WA admin |

### IPL
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/ipl/tagihan/bulan-ini` | Tagihan bulan ini |
| GET | `/api/v1/ipl/tunggakan` | Daftar tunggakan |
| POST | `/api/v1/ipl/tagihan/{id}/bayar` | Buat transaksi Midtrans |
| POST | `/api/v1/ipl/midtrans/callback` | Webhook Midtrans |

### Pengaduan
| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/pengaduan` | Daftar pengaduan |
| POST | `/api/v1/pengaduan` | Buat pengaduan baru |
| PUT | `/api/v1/pengaduan/{id}` | Update status (admin) |

---

## Notifikasi Otomatis

- **Generate tagihan**: Setiap tanggal 1 jam 08:00 → otomatis buat tagihan & kirim notifikasi
- **Reminder**: Tanggal 28 jam 08:00 → ingatkan warga yang belum bayar
- **Cek keterlambatan**: Setiap hari jam 09:00 → tandai tagihan terlambat, tambah denda 5%

---

## Teknologi

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 10, MySQL, Sanctum |
| Mobile | Flutter 3, Provider, Dio |
| Back Office | Vue 3, Vite, Pinia, Tailwind CSS |
| Payment | Midtrans Snap |
| Push Notification | Firebase FCM |
| Scheduler | Laravel Scheduler |
