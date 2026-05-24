# 🔑 Kredensial Akun untuk Test Transaksi - Midtrans Review

> **Untuk:** Tim Verifikasi Midtrans
> **Dari:** Edi Prasetiyo (WhatsApp: 082115525327)
> **Tanggal:** 22 Mei 2026
> **Aplikasi:** IPL Griya Pesona Madani Tenjo

---

## 📌 RINGKASAN

Dokumen ini berisi kredensial dan panduan untuk Tim Midtrans melakukan tes
transaksi end-to-end pada aplikasi IPL Griya Pesona Madani Tenjo. Aplikasi terdiri
dari 2 antarmuka:

1. **Back Office (Web Admin)** — untuk pengelola RT (super_admin/admin/bendahara)
2. **Mobile App (Android)** — untuk warga melakukan pembayaran

---

## 1. AKUN BACK OFFICE (Web Admin)

**URL:** https://admin.ipl-griya-pesona-madani.my.id

### Login Super Admin:
```
Nomor HP   : 081908226774
Password   : (lihat di bagian "Catatan Kredensial Aman" di bawah)
Role       : Super Admin (akses penuh)
```

> **Catatan:** Login backoffice menggunakan **Nomor HP** (bukan email).

### Fitur yang Bisa Diakses Super Admin:
- 📊 Dashboard — overview keuangan
- 👥 Data Warga — kelola data warga
- 📄 Tagihan IPL — generate & manage tagihan bulanan
- 💳 Pembayaran — riwayat pembayaran via Midtrans
- 💸 Pengeluaran / Kas — catat pengeluaran RT
- ⚠️ Pengaduan — kelola pengaduan warga
- 📢 Berita — pengumuman & berita RT
- 📈 Laporan — laporan keuangan tahunan/bulanan + export Excel/PDF
- 🛡️ Manajemen User — kelola admin/bendahara/humas/warga
- 🏘️ Manajemen Blok — kelola blok perumahan
- 💳 **Pembayaran Midtrans** — kelola payment method aktif + tarif biaya admin
- 📋 Audit Log — riwayat aksi semua user (untuk fraud detection)
- ⚙️ Pengaturan — branding, tema, nominal IPL, template notifikasi

---

## 2. AKUN MOBILE APP (Test Warga)

### Download APK:
URL: _(akan dikirim via WhatsApp setelah Tim Midtrans request)_

Atau hubungi WhatsApp **082115525327** untuk dapat link download APK.

### Login Akun Warga Test:
```
Nomor HP   : 082115525327
Password   : (lihat di bagian "Catatan Kredensial Aman" di bawah)
Role       : Warga
Nama       : EDI PRASETIYO
Blok       : E
```

---

## 3. CARA TEST TRANSAKSI (Step-by-Step)

### Test Skenario A: Pembayaran via Mobile App (Warga)

1. **Install APK** di HP Android (kirim via WA atau link download)
2. **Buka aplikasi** → tunggu splash screen
3. **Login** dengan kredensial warga di atas (082115525327)
4. Tab **Pembayaran** → akan tampil tagihan IPL bulan ini
5. Klik tombol **"Bayar"**
6. Muncul **modal pilih metode pembayaran** dengan list:
   - Virtual Account (BCA, BNI, BRI, Permata, Mandiri)
   - GoPay, ShopeePay, DANA
   - QRIS
   - Kartu Kredit
   - Indomaret, Alfamart
   Beserta **biaya admin** per metode (tarif Midtrans).
7. **Pilih metode** apapun (e.g., GoPay / QRIS untuk paling cepat)
8. Snap UI Midtrans terbuka **di dalam aplikasi (in-app WebView)** — TIDAK redirect ke browser eksternal
9. Selesaikan pembayaran (test di Sandbox: gunakan Midtrans Payment Simulator)
10. **Notifikasi push** muncul di HP: "Pembayaran IPL Berhasil"
11. Tab **Pembayaran > Riwayat** — transaksi tampil dengan logo brand & status "Lunas"

### Test Skenario B: Verifikasi di Back Office (Super Admin)

1. Login Back Office: https://admin.ipl-griya-pesona-madani.my.id
2. Pakai kredensial Super Admin
3. **Dashboard** — angka "Total IPL" bertambah sesuai pembayaran
4. **Pembayaran** — transaksi baru muncul di list dengan order_id Midtrans
5. **Audit Log** — entry `payment_success` tercatat
6. **Laporan → Export Excel atau PDF** — lihat transaksi di rekap

---

## 4. ENDPOINT API YANG TERHUBUNG KE MIDTRANS

| Action | URL | Method |
|--------|-----|--------|
| Create Transaction (mobile) | `https://ipl-griya-pesona-madani.my.id/api/v1/ipl/tagihan/{id}/bayar` | POST |
| **Midtrans Notification URL** | `https://ipl-griya-pesona-madani.my.id/api/v1/ipl/midtrans/callback` | POST |
| Status check | `https://ipl-griya-pesona-madani.my.id/api/v1/ipl/pembayaran/{id}` | GET |
| Health check (public) | `https://ipl-griya-pesona-madani.my.id/api/v1/health` | GET |

**Catatan:** Saat ini masih pakai **Midtrans Sandbox**. Akan switch ke Production
setelah approval Anda.

---

## 5. SAMPLE TRANSAKSI YANG SUDAH BERHASIL (Sandbox)

Berikut contoh transaksi yang sudah sukses di Midtrans Sandbox sebagai bukti
aplikasi sudah berfungsi end-to-end:

| Order ID | Tanggal | Nominal | Method | Status |
|----------|---------|---------|--------|--------|
| `IPL-1-52026-QmNdpT` | 20 Mei 2026 | Rp 65.000 | GoPay | settlement (sukses) |

Bisa dicek di Midtrans Sandbox Dashboard merchant `dszgofcr` atau via API
status check di atas.

---

## 6. STAKEHOLDER & PENANGGUNG JAWAB

| Field | Nilai |
|-------|-------|
| **Penanggung Jawab Aplikasi** | Edi Prasetiyo |
| **No. KTP** | _(sesuai KTP yang sudah di-upload)_ |
| **WhatsApp** | 082115525327 |
| **Lokasi** | Perumahan Griya Pesona Madani Tenjo, Bogor |
| **Rekening Settlement** | _(sesuai yang di-isi di form Midtrans)_ |
| **Surat Penunjukan RT** | Lampiran terpisah (PDF) |
| **Mata Uang** | Rupiah Indonesia (IDR) |
| **Estimasi Volume** | 30-50 transaksi/bulan, Rp 2-3 juta/bulan |

---

## 7. DOKUMEN LEGAL (Public Accessible)

| Dokumen | URL |
|---------|-----|
| Tentang Aplikasi | https://ipl-griya-pesona-madani.my.id/about.html |
| Syarat & Ketentuan | https://ipl-griya-pesona-madani.my.id/terms.html |
| Kebijakan Refund | https://ipl-griya-pesona-madani.my.id/refund-policy.html |
| Kebijakan Privasi | https://ipl-griya-pesona-madani.my.id/privacy.html |
| Kontak Bisnis | https://ipl-griya-pesona-madani.my.id/contact.html |

---

## 8. KONTAK SUPPORT (Selama Review)

Untuk pertanyaan teknis atau request akses tambahan selama proses review:

- **Nama:** Edi Prasetiyo
- **WhatsApp:** **082115525327** (respons cepat)
- **Jam respons:** 10:00 - 17:00 WIB (Senin-Minggu)

---

## 9. ⚠️ CATATAN KREDENSIAL AMAN

> **PENTING:** Password TIDAK dicantumkan di dokumen ini untuk keamanan.
>
> Password akun di atas akan dikirim **secara terpisah** via:
>
> 1. **Pesan terenkripsi** di Midtrans Dashboard support, ATAU
> 2. **WhatsApp langsung** ke contact person Midtrans, ATAU
> 3. **Email** ke alamat email resmi Midtrans (verifikasi@midtrans.com / similar)
>
> Setelah review selesai, password akan **diganti** untuk keamanan.

### 🔑 Password Akun Test (untuk Midtrans):

**Super Admin (Back Office):**
```
Username: 081908226774
Password: midtrans-review-2026
```

**Warga (Mobile App):**
```
Username: 082115525327
Password: midtrans-review-2026
```

> **⚠️ Untuk Edi:** Password di atas adalah **password sementara untuk Midtrans review**.
> Sebelum submit, **harus di-set** ke password tersebut via Backoffice → Manajemen User → klik user → ubah password.
> Setelah Midtrans approve, **wajib ganti** ke password baru yang aman.

---

## 10. INSTRUKSI UNTUK TIM MIDTRANS

Yang Tim Midtrans bisa lakukan saat review:

✅ **Login ke backoffice** dengan akun super_admin
✅ **Lihat semua menu**: dashboard, warga, tagihan, pembayaran, kas, dll
✅ **Cek log transaksi** sandbox di menu Pembayaran
✅ **Lihat Audit Log** untuk verifikasi aktivitas
✅ **Install APK mobile** dan lakukan tes transaksi
✅ **Test Snap UI** dengan Midtrans Sandbox Simulator
✅ **Verifikasi compliance** (in-app payment, Rupiah, T&C, Refund accessible)

❌ **Tidak perlu** mengubah data warga atau pengaturan
❌ **Tidak perlu** menghapus data — bisa rusak untuk warga real

---

**Terima kasih atas perhatian dan waktu Tim Midtrans untuk review aplikasi ini.**

Hormat saya,
**Edi Prasetiyo**
Pengelola Aplikasi IPL Griya Pesona Madani Tenjo
WhatsApp: 082115525327
