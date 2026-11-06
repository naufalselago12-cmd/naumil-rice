# DOKUMENTASI LENGKAP SESSION PENGEMBANGAN: REALTIME NOTIFIKASI ONESIGNAL & FLUTTER APP

> **Tanggal & Waktu:** 6 November 2026  
> **Proyek:** Naumil Rice Management System (Web Backend & Mobile Admin App)  
> **Lokasi Proyek:** `D:\DATA\naumil_app\`  
> - Backend Laravel: `D:\DATA\naumil_app\naumil_rice\`  
> - Mobile Flutter: `D:\DATA\naumil_app\naumil_rice_apps\`  
> **OneSignal App ID:** `28d706da-25b6-4615-8728-1af01f3f871b`

---

## 1. Latar Belakang & Kebutuhan Pengguna

1. **Modul Notifikasi Realtime di Web Backend Store:**
   - Dibuatkan satu halaman dedicated `/notification` khusus untuk Admin Store Backend yang sudah login.
   - Push notifikasi menggunakan OneSignal Web SDK (`OneSignalSDKWorker.js`).
   - Logika pembedaan efek suara & visual:
     - **Bayar Setelah Makanan Jadi (COD / Bayar Terima Makanan):** Efek suara `notif-pembelian.wav`, kartu berwarna **Warm Amber / Oranye**.
     - **Pembayaran Langsung (QRIS / Midtrans / Mayar):** Efek suara `notif-pembayaran.wav`, kartu berwarna **Emerald Green**.
   - Kartu notifikasi modern memuat informasi: Nama Pelanggan, Rincian Pembelian (item, kuantitas, opsi, catatan), Total Harga, dan Metode Pembayaran.

2. **Solusi Limitasi Web Browser dengan Mobile App Flutter (`naumil_rice_apps`):**
   - Pada browser web (Chrome/Edge/Firefox), audio notifikasi tidak dapat diputar jika tab browser dalam kondisi ditutup atau terminimalisasi karena kebijakan *Autoplay Policy*.
   - Solusi: Dibuatkan aplikasi mobile Android mandiri menggunakan Flutter dengan seluruh fitur backend toko (Full Feature) ditambah integrasi OneSignal Native Notification Channels agar suara push notification tetap berbunyi kencang meskipun aplikasi ditutup, HP terkunci, atau layar mati.
   - Mengikuti panduan resmi integrasi OneSignal Flutter SDK (AI Prompt Guidelines).

3. **Penyesuaian URL Server saat Deploy ke Production:**
   - Fleksibilitas konfigurasi domain/IP server (dinamis via UI dan default via konstanta).

4. **Pemisahan & Restrukturisasi Folder Proyek:**
   - Memindahkan dan memisahkan proyek ke dalam `D:\DATA\naumil_app` menjadi 2 folder sejajar:
     1. `D:\DATA\naumil_app\naumil_rice` (Laravel Backend)
     2. `D:\DATA\naumil_app\naumil_rice_apps` (Flutter Mobile App)

---

## 2. Arsitektur & Implementasi Backend (Laravel 12)

### A. Database & Model Notifikasi
- **Migration:** `database/migrations/2026_11_06_000001_create_store_notifications_table.php`
  - Kolom: `id`, `store_id`, `order_id`, `type` (`cod` / `direct`), `title`, `message`, `sound` (`notif-pembelian.wav` / `notif-pembayaran.wav`), `customer_name`, `customer_phone`, `total_price`, `payment_method`, `payment_channel`, `items_summary`, `order_snapshot` (JSON rincian belanja lengkap), `is_read`, `read_at`, `created_at`, `updated_at`.
- **Model:** `app/Models/StoreNotification.php`
  - Konstanta tipe: `TYPE_COD = 'cod'`, `TYPE_DIRECT = 'direct'`.
  - Casting otomatis JSON untuk `order_snapshot`.

### B. Service OneSignal & Pemicu Otomatis (Triggers)
- **Service:** `app/Services/StoreNotificationService.php`
  - Mengirim HTTP POST ke OneSignal REST API `https://api.onesignal.com/notifications`.
  - Sasaran target: `include_aliases: { "external_id": ["naumil-store-admin-{userId}"] }`.
  - Penentuan Android Channel:
    - COD -> `channel_id: "naumil_cod_v1"`
    - Direct -> `channel_id: "naumil_direct_v1"`
  - Menghindari duplikasi notifikasi per pesanan dengan sistem pengecekan log.
- **Pemicu COD:** `app/Http/Controllers/Api/OrderApiController.php`
  - Dipicu saat pelanggan berhasil membuat order dengan opsi `Bayar Terima Makanan`.
- **Pemicu Direct Payment:** `app/Models/Order.php` (Model Hook `updated`)
  - Dipicu saat `payment_status` berubah menjadi `paid` atau `payment_on_review` (QRIS, Mayar, Midtrans).

### C. Halaman Web Notifikasi & Web SDK
- **Redirect Route:** `/notification` di `routes/web.php` otomatis mendeteksi cabang admin dan me-redirect ke `/{store_slug}/backend/notification`.
- **Web Controller:** `app/Http/Controllers/StoreBackend/StoreBackendNotificationController.php`
  - `index()`: Menampilkan riwayat notifikasi dengan filter (Semua, Belum Dibaca, COD, Pembayaran Langsung).
  - `feed()`: Endpoint JSON untuk fallback polling realtime setiap 8 detik jika koneksi OneSignal terhambat.
  - `markRead()` & `markAllRead()`: Menandai notifikasi telah dibaca.
- **Client Script & Audio Unlocker:** `resources/views/store_backend/partials/notification_client.blade.php`
  - Inisialisasi OneSignal Web SDK v16.
  - Registrasi external ID admin toko.
  - Handler *User Interaction Unlock* untuk membuka izin pemutaran audio browser.
  - Listener foreground notification OneSignal dengan pemutaran audio otomatis.
- **Halaman Tampilan:** `resources/views/store_backend/notifications/index.blade.php`
  - Desain kartu modern dengan perbedaan warna aksen (Amber untuk COD, Emerald Green untuk Direct Payment).
  - Tombol tes suara interaktif dan ringkasan breakdown pesanan.

### D. REST API Suite untuk Mobile (`/api/v1/admin/*`)
Dibuatkan 11 controller API di `app/Http/Controllers/Api/Admin/` dengan otentikasi Sanctum Bearer Token dan middleware `store.api` (`app/Http/Middleware/ApiStoreAdminMiddleware.php`):
1. `AdminAuthController`: Login admin cabang, info profil (`/me`), logout.
2. `AdminDashboardController`: Metrik penjualan harian, pesanan baru, status toko.
3. `AdminOrderController`: Daftar pesanan, rincian pesanan, ganti status, verifikasi bayar, sync Midtrans & Mayar.
4. `AdminMenuController`: CRUD menu/produk, toggle status habis, toggle aktif.
5. `AdminCategoryController`: CRUD kategori produk.
6. `AdminCampaignController`: Manajemen batch Open PO / Promo.
7. `AdminLoyaltyController`: Manajemen member pelanggan, penyesuaian poin loyalty, voucher promo.
8. `AdminReportController`: Laporan omzet, pendapatan bersih, metode bayar, dan 10 produk terlaris.
9. `AdminSettingsController`: Informasi toko, testing payment gateway, status integrasi OneSignal.
10. `AdminNotificationController`: Daftar notifikasi, tandai baca, dan trigger tes notifikasi.

---

## 3. Arsitektur & Implementasi Mobile Flutter (`naumil_rice_apps`)

### A. Versi SDK & Standar Kepatuhan OneSignal
- **Paket:** `onesignal_flutter: 5.5.2` (Dipin tepat sesuai rilisan stabil resmi `https://onesignal.github.io/sdk-releases/releases.json`).
- **Isolasi Service (`OneSignalService`):**
  - Seluruh pemanggilan OneSignal diisolasi ke dalam singleton `lib/services/onesignal_service.dart`.
  - Tidak ada widget atau screen yang memanggil method `OneSignal.*` secara langsung.
- **Push Subscription Verification Dialog:**
  - Dialog penjelasan izin push notifikasi menerapkan pola observer retensi.
  - Dialog hanya dimunculkan satu kali setelah terkonfirmasi bahwa device memiliki `subscriptionId` resmi dari server OneSignal (bukan bernilai `null` dan tidak berawalan `local-`).
  - Permintaan izin sistem Android (`requestPermission`) **hanya dieksekusi saat pengguna menekan tombol "Saya Mengerti, Aktifkan"** pada dialog, tidak pernah dipanggil otomatis saat startup aplikasi.

### B. Saluran Notifikasi Native Android (Background Audio Solution)
Konfigurasi di `android/app/src/main/kotlin/com/naumil/rice/naumil_rice_apps/MainActivity.kt`:
```kotlin
val audioAttributes = AudioAttributes.Builder()
    .setContentType(AudioAttributes.CONTENT_TYPE_SONIFICATION)
    .setUsage(AudioAttributes.USAGE_NOTIFICATION)
    .setFlags(AudioAttributes.FLAG_AUDIBILITY_ENFORCED)
    .build()

// 1. Channel Pesanan COD (Bayar Terima Makanan)
val soundCodUri = Uri.parse("android.resource://" + packageName + "/raw/notif_pembelian")
val channelCod = NotificationChannel("naumil_cod_v1", "Pesanan COD (Bayar Terima Makanan)", NotificationManager.IMPORTANCE_HIGH)
channelCod.setSound(soundCodUri, audioAttributes)

// 2. Channel Pembayaran Langsung (QRIS / Midtrans / Mayar)
val soundDirectUri = Uri.parse("android.resource://" + packageName + "/raw/notif_pembayaran")
val channelDirect = NotificationChannel("naumil_direct_v1", "Pembayaran Langsung (QRIS / Gateway)", NotificationManager.IMPORTANCE_HIGH)
channelDirect.setSound(soundDirectUri, audioAttributes)
```
- Audio mentah ditempatkan di `android/app/src/main/res/raw/notif_pembelian.wav` dan `notif_pembayaran.wav`.
- File audio in-app juga tersedia di `assets/sounds/` yang diputar oleh `SoundService` saat aplikasi berada di latar depan (*foreground*).

### C. Daftar Layar & Fitur Aplikasi
1. `LoginScreen`: Login akun admin cabang dengan tombol gerigi ⚙️ untuk konfigurasi Server Base URL secara dinamis.
2. `MainNavigationScreen`: Shell navigasi bawah (Bottom Navigation Bar) dengan Push Subscription Verification Dialog.
3. `NotificationScreen`: Daftar riwayat notifikasi kartu modern dengan warna pembeda COD vs Direct, breakdown produk, toggle mute, tombol tes suara, dan filter.
4. `DashboardScreen`: Ringkasan metrik omzet, pesanan baru, pesanan sedang diproses, dan pesanan terbaru.
5. `KdsScreen`: Tampilan Kitchen Display System untuk tim dapur dengan tombol transisi tahap memasak (Diterima -> Dimasak -> Siap Diambil -> Selesai).
6. `OrdersScreen` & `OrderDetailScreen`: Pencarian & filter pesanan, integrasi kirim WhatsApp pelanggan dengan template otomatis, verifikasi bukti bayar, sync Midtrans & Mayar, dan audit timeline pesanan.
7. `MenusScreen`: Pengelolaan menu/katalog dan kategori, switch aktif/nonaktif, dan toggle stok habis.
8. `CampaignsScreen`: Pengelolaan batch Open PO dan kuota pesanan.
9. `LoyaltyScreen`: Daftar pelanggan, penyesuaian poin member (+/-), dan voucher diskon.
10. `ReportsScreen`: Laporan penjualan harian/bulanan, breakdown pendapatan per metode bayar, dan grafik produk terlaris.
11. `SettingsScreen`: Profil toko, info jam operasional, status langganan OneSignal, dan tombol tes kirim notifikasi push.

### D. Hasil Build APK
- Perintah: `flutter build apk --debug`
- Output: `D:\DATA\naumil_app\naumil_rice_apps\build\app\outputs\flutter-apk\app-debug.apk`
- Status `flutter analyze`: **No issues found! (0 warnings, 0 errors)**.

---

## 4. Panduan Penyesuaian Domain / URL Server (Deployment)

Aplikasi Flutter mendukung 2 mekanisme penyesuaian URL server:

### Cara 1: Dinamis via Aplikasi (Tanpa Compile Ulang)
1. Buka aplikasi di smartphone Android.
2. Pada layar **Login**, klik ikon gerigi ⚙️ (**Atur Server API**) di kanan atas.
3. Masukkan domain server (contoh: `https://naumilrice.com`).
4. Klik **Simpan**. URL ini otomatis tersimpan di HP melalui `SharedPreferences`.

### Cara 2: Diset Sebagai Nilai Bawaan (Default Production Build)
1. Buka file `naumil_rice_apps/lib/constants.dart`.
2. Ubah baris `defaultBaseUrl`:
   ```dart
   static const String defaultBaseUrl = 'https://naumilrice.com';
   ```
3. Lakukan build APK Release:
   ```powershell
   cd naumil_rice_apps
   flutter build apk --release
   ```

### Konfigurasi `.env` Server Laravel:
```env
ONESIGNAL_APP_ID=28d706da-25b6-4615-8728-1af01f3f871b
ONESIGNAL_REST_API_KEY=os_v2_app_xxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

## 5. Struktur Akhir Proyek

Kedua proyek telah dipisahkan secara rapi ke dalam folder utama `D:\DATA\naumil_app`:

```
D:\DATA\naumil_app\
│
├── naumil_rice\                         <- Proyek Laravel Backend (25.018 File)
│   ├── app\                             <- Controllers, Models, Services OneSignal
│   ├── bootstrap\
│   ├── config\onesignal.php
│   ├── database\migrations\
│   ├── public\
│   │   ├── OneSignalSDKWorker.js        <- Worker Push Web SDK
│   │   └── sounds\                      <- notif-pembelian.wav & notif-pembayaran.wav
│   ├── resources\views\store_backend\   <- Web views /notification & audio unlocker
│   ├── routes\
│   │   ├── api.php                      <- 40 API endpoints (/api/v1/admin/*)
│   │   └── web.php
│   └── .env
│
└── naumil_rice_apps\                    <- Proyek Flutter Mobile App (2.088 File)
    ├── android\app\src\main\
    │   ├── kotlin\...\MainActivity.kt   <- Native Notification Channels (Audio URIs)
    │   └── res\raw\                     <- notif_pembelian.wav & notif_pembayaran.wav
    ├── assets\sounds\                   <- Audio asset in-app player
    ├── lib\
    │   ├── models\                      <- Order, Menu, Notification, User models
    │   ├── providers\                   <- AuthProvider & NotificationProvider
    │   ├── screens\                     <- 11 screens lengkap
    │   ├── services\                    <- OneSignalService, ApiService, SoundService
    │   ├── constants.dart               <- App ID, Base URL, Colors, Channels
    │   └── main.dart
    ├── build\app\outputs\flutter-apk\
    │   └── app-debug.apk                <- APK Terkompilasi Siap Pasang
    └── pubspec.yaml                     <- onesignal_flutter: 5.5.2
```

---

*Dokumen ini dibuat otomatis sebagai rangkuman menyeluruh dari seluruh implementasi teknis, keputusan arsitektur, dan instruksi operasional yang dikerjakan pada sesi ini.*
