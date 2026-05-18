# Deploy ke Render.com (100% Gratis)

## Stack Gratis
- **Web hosting**: Render.com (free tier, 750h/bln, sleep 15min idle)
- **Database**: Neon.tech PostgreSQL (0.5 GB free, always-on)
- **Storage gambar**: Cloudinary (25 GB free)

## Langkah Setup

### 1. Daftar Akun (Gratis Semua)

| Layanan | Link | Login pakai |
|---|---|---|
| Render | https://render.com | GitHub |
| Neon | https://neon.tech | Google/GitHub |
| Cloudinary | https://cloudinary.com | Email/Google |

### 2. Buat Database di Neon

1. Login Neon → **New Project**
2. Nama: `ipl-griya-pesona-madani`
3. Region: **Singapore (closest to Indonesia)**
4. PostgreSQL version: latest
5. Copy **Connection String** (format: `postgresql://user:pass@host/db?sslmode=require`)

### 3. Dapatkan Credentials Cloudinary

1. Login Cloudinary → Dashboard
2. Catat: **Cloud Name**, **API Key**, **API Secret**

### 4. Deploy di Render

1. Render Dashboard → **+ New** → **Blueprint**
2. Connect GitHub repo `app_pembayaran_IPL`
3. Render auto-detect `backend_fresh/render.yaml`
4. Saat prompt env vars, isi:
   - `APP_URL`: kosongkan dulu (akan di-fill setelah deploy)
   - `DATABASE_URL`: paste connection string dari Neon
   - `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET`
   - `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`
   - `FRONTEND_URL`: URL Vercel backoffice
5. Klik **Apply** → Render build & deploy (~5-10 menit pertama kali)
6. Setelah live, copy URL (contoh: `https://ipl-backend.onrender.com`)
7. Update env `APP_URL` dengan URL tersebut → trigger redeploy

### 5. Migrate Data dari Railway (jika ada)

```bash
# Di local:
# 1. Export dari Railway MariaDB
mysqldump -h <railway-host> -u <user> -p<password> <db_name> > backup.sql

# 2. Convert MySQL → PostgreSQL pakai pgloader atau manual
# Tools: https://github.com/dimitri/pgloader

# 3. Atau pakai script export-import via Laravel:
php artisan db:seed --class=ImportFromMariaDbSeeder
```

### 6. Keep-Alive Cron (Optional, biar tidak tidur)

Pakai https://cron-job.org (gratis):
- Buat job baru
- URL: `https://ipl-backend.onrender.com/api/v1/health`
- Interval: 14 menit
- Status: enabled

### 7. Update Mobile & Backoffice

Edit URL di:
- `mobile_fresh/lib/utils/constants.dart` → `baseUrl`
- `backoffice/.env` di Vercel → `VITE_API_URL`

Rebuild APK & redeploy Vercel.

## Troubleshooting

### Build fails: PostgreSQL extension not found
Dockerfile sudah include `pdo_pgsql`. Kalau masih error, cek logs di Render.

### Migration fails: enum syntax error
File `2024_01_01_000010_*.php` dan `2024_01_01_000011_*.php` sudah dibuat DB-aware.
Pastikan `DB_CONNECTION=pgsql` di env.

### App tidur lama (cold start)
Setup cron-job.org ping `/api/v1/health` tiap 14 menit.

### Image upload gagal
Pastikan 3 env Cloudinary sudah di-set di Render dashboard.
