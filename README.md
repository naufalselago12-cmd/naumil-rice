# Naumil Rice - Multi-Store Food E-Commerce & Pre-Order System (Laravel 12 + Inertia Vue 3)

Sistem Food E-Commerce & Open Pre-Order (PO) Kuliner Nusantara dengan arsitektur **Multi-Store / Multi-Tenant** terpadu menggunakan **Laravel 12**, **Inertia.js v3**, dan **Vue 3**.

---

## 🏛️ Arsitektur Multi-Store & Portal Akses

Sistem ini mendukung pengelolaan banyak cabang/toko (multi-store) dengan pembagian hak akses dan antarmuka mandiri:

1. **Halaman Utama / Direktori Cabang (`/`)**:
   - Menampilkan **looping seluruh cabang/toko** yang aktif saat pertama kali web dibuka.
   - Dilengkapi pencarian cabang, preview menu, batch PO aktif, dan QRIS dinamis.
   - Klik cabang langsung mengarahkan pelanggan ke halaman toko spesifik: `base_url/(nama_toko)`.

2. **Frontend Toko Spesifik (`base_url/(nama_toko)`)**:
   - Contoh: `http://localhost:8000/sudirman` atau `http://localhost:8000/tebet`.
   - **Seluruh data yang terikat ke toko tersebut akan dimuat**:
     - Profil toko, jam operasional, alamat, dan WhatsApp cabang.
     - Kategori & Menu makanan/minuman milik cabang tersebut.
     - Batch Open Pre-Order aktif cabang tersebut.
     - Modal kustomisasi topping, upsize/porsi, dan tingkat kepedasan.
     - Keranjang belanja & checkout mandiri yang otomatis terikat ke ID cabang tersebut.
     - QRIS dinamis unik merchant per cabang.

3. **Backend Per Toko (`base_url/(nama_toko)/backend`)**:
   - Contoh: `http://localhost:8000/sudirman/backend` atau `http://localhost:8000/tebet/backend`.
   - Mengelola operasional cabang masing-masing:
     - Dashboard performa penjualan & antrean cabang.
     - Manajemen Menu Makanan & Topping cabang.
     - Manajemen Kategori Menu cabang.
     - Manajemen Batch Open PO & Cetak QR Token cabang.
     - Manajemen Pesanan & Pembaruan Status (Konfirmasi -> Masak -> Packing -> Kirim -> Selesai).
     - Layar Dapur Real-Time (Kitchen Display System / KDS).
     - Laporan Penjualan & Menu Terlaris cabang.
     - Pengaturan Profil & QRIS Payload cabang.
   - **Proteksi Otorisasi**: Store Admin hanya dapat mengakses backend cabang yang ditugaskan kepadanya.

4. **Super Admin Portal (`base_url/superadmin`)**:
   - URL: [http://localhost:8000/superadmin](http://localhost:8000/superadmin)
   - **Kredensial Default Master**:
     - **Username**: `naufal_su`
     - **Password**: `naufal_super2020`
   - **Fitur Super Admin**:
     - **Kelola Toko (Stores)**: Tambah cabang baru, edit slug URL toko (`nama_toko`), kode cabang, logo, QRIS, dan status operasional.
     - **Kelola User Per Toko (Users & Store Assignment)**: Buat user baru, atur role (`Super Admin` vs `Store Admin`), dan **tugaskan user ke cabang toko masing-masing** untuk mengelola backend toko bersangkutan.
     - **Direct Access**: Buka langsung frontend toko maupun backend toko manapun dari satu panel kontrol.
     - **Metrik Konsolidasi Global**: Total omset semua cabang, total pesanan seluruh cabang, dan total pengguna.

---

## 📂 Struktur Direktori Utama

```
naumil_rice/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── SuperAdmin/       # Controller Super Admin (/superadmin: Dashboard, Stores, Users)
│   │   │   ├── StoreBackend/     # Controller Backend Per Toko (/{store_slug}/backend)
│   │   │   ├── Admin/            # Controller Backend AdminLTE Global
│   │   │   ├── Api/              # REST API v1 (Stores, Campaigns, Clients, Orders)
│   │   │   └── StoreFrontController.php # Root Directory & Toko Frontend (/{store_slug})
│   │   └── Middleware/
│   │       ├── SuperAdminMiddleware.php  # Proteksi rute /superadmin (khusus role superadmin)
│   │       └── StoreScopeMiddleware.php  # Proteksi & scoping data toko (/{store_slug}/backend)
│   └── Models/                   # Store, User (role & store_id), Menu, Category, Order, dll.
│
├── database/
│   ├── migrations/               # Migrasi (Stores slug, Users role & store_id)
│   └── seeders/                  # Seeder Super Admin (naufal_su) & Store Admins
│
├── resources/
│   ├── js/
│   │   ├── Layouts/
│   │   │   └── AppLayout.vue     # Layout utama Inertia Vue 3
│   │   └── Pages/
│   │       ├── Home.vue          # Halaman Root (Looping per toko saat pertama load web)
│   │       ├── StoreFront.vue    # Halaman Toko (/{nama_toko}) memuat seluruh data terikat
│   │       ├── Campaign.vue      # Halaman Pre-Order spesifik QR
│   │       └── Trace.vue         # Pelacakan Pesanan Real-Time
│   └── views/
│       ├── superadmin/           # Blade views portal Super Admin
│       ├── store_backend/        # Blade views panel backend per toko
│       └── admin/                # Blade views dashboard AdminLTE
│
├── routes/
│   ├── web.php                   # Rute Super Admin, Store Backend, Storefront & Root
│   └── api.php                   # Rute REST API v1
└── vite.config.js
```

---

## 🚀 Panduan Menjalankan Sistem

Buka terminal di root direktori (`d:\DATA\naumil_rice`):

### 1. Jalankan Backend Laravel
```powershell
php artisan serve --port=8000
```

### 2. Jalankan Vite (Frontend Assets)
```powershell
npm run dev
```
*(Atau build produksi terkompilasi):*
```powershell
npm run build
```

---

## 🔑 Kredensial Pengguna Terdaftar

| Role / Level | URL Akses | Username | Password | Deskripsi / Hak Akses |
| :--- | :--- | :--- | :--- | :--- |
| **Super Admin** | `http://localhost:8000/superadmin` | `naufal_su` | `naufal_super2020` | Akses penuh master control, kelola semua cabang & kelola user per toko |
| **Store Admin Sudirman** | `http://localhost:8000/sudirman/backend` | `admin_sudirman` | `admin12345` | Khusus mengelola backend Cabang Sudirman (NR-SDR) |
| **Store Admin Tebet** | `http://localhost:8000/tebet/backend` | `admin_tebet` | `admin12345` | Khusus mengelola backend Cabang Tebet (NR-TBT) |

---

## 🌐 Daftar Tautan Rute Utama

### 🛒 Toko & Pembeli
- **Halaman Utama (Looping Per Toko)**: [http://localhost:8000/](http://localhost:8000/)
- **Toko Flagship Sudirman**: [http://localhost:8000/sudirman](http://localhost:8000/sudirman)
- **Toko Tebet Kitchen**: [http://localhost:8000/tebet](http://localhost:8000/tebet)
- **Pelacakan Pesanan (Live)**: [http://localhost:8000/trace](http://localhost:8000/trace)

### ⚙️ Backend Per Toko
- **Backend Cabang Sudirman**: [http://localhost:8000/sudirman/backend](http://localhost:8000/sudirman/backend)
- **Backend Cabang Tebet**: [http://localhost:8000/tebet/backend](http://localhost:8000/tebet/backend)

### 👑 Super Admin Portal
- **Login Super Admin**: [http://localhost:8000/superadmin/login](http://localhost:8000/superadmin/login)
- **Dashboard Super Admin**: [http://localhost:8000/superadmin](http://localhost:8000/superadmin)
- **Kelola Toko**: [http://localhost:8000/superadmin/stores](http://localhost:8000/superadmin/stores)
- **Kelola User Per Toko**: [http://localhost:8000/superadmin/users](http://localhost:8000/superadmin/users)
