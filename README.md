# Naumil Rice - Unified Food E-Commerce & Pre-Order System (Laravel 12 + Inertia Vue 3)

Sistem Food E-Commerce & Open Pre-Order (PO) Kuliner Nusantara yang telah digabungkan menjadi **1 Single Project** terpadu menggunakan **Laravel 12**, **Inertia.js v3**, dan **Vue 3**.

---

## 📂 Struktur Proyek Terpadu

```
naumil_rice/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/            # AdminLTE Backend Controllers (Dashboard, Stores, Menus, Campaigns, Orders, KDS, Reports)
│   │   │   └── Api/              # REST API v1 Controllers (Store, Campaign, Client, Order)
│   │   └── Middleware/
│   │       └── HandleInertiaRequests.php  # Middleware Inertia.js
│   └── Models/                   # Eloquent Models & Relasi Database
│
├── database/
│   ├── migrations/               # Migrasi database MySQL
│   └── seeders/                  # Seeder data toko, menu, campaign, order
│
├── resources/
│   ├── css/
│   │   └── app.css               # Desain modern Naumil Rice (Responsive design system)
│   ├── js/
│   │   ├── app.js                # Inisialisasi Inertia Vue 3
│   │   ├── Layouts/
│   │   │   └── AppLayout.vue     # Layout utama Inertia (Navbar, Brand, Footer, Navigation)
│   │   └── Pages/
│   │       ├── Home.vue          # Halaman Beranda & Daftar Open PO Aktif
│   │       ├── Campaign.vue      # Halaman Pre-Order, Menu, Modal Opsi, Cart & Dynamic QRIS
│   │       ├── Stores.vue        # Halaman Direktori Cabang Dapur & Info QRIS
│   │       └── Trace.vue         # Pelacakan Pesanan Real-Time (5 Tahap Dapur)
│   └── views/
│       ├── app.blade.php         # Root layout HTML Inertia
│       └── admin/                # Blade views multi-theme untuk AdminLTE Backend (/backend)
│
├── routes/
│   ├── web.php                   # Rute Inertia (Frontend Pelanggan) & Rute AdminLTE
│   └── api.php                   # REST API v1
│
├── public/                       # Aset publik & hasil kompilasi Vite (public/build)
├── vite.config.js                # Konfigurasi Vite dengan plugin Laravel & Vue
├── composer.json                 # Dependensi PHP & Inertia Laravel
└── package.json                  # Dependensi frontend (@inertiajs/vue3, vue, lucide, dll.)
```

---

## 🚀 Panduan Menjalankan Proyek

Seluruh perintah kini dijalankan langsung dari **root direktori (`d:\DATA\naumil_rice`)**:

### 1. Jalankan Backend Laravel
Buka terminal di root direktori:
```powershell
php artisan serve --port=8000
```

### 2. Jalankan Vite Development Server
Buka terminal baru di root direktori:
```powershell
npm run dev
```

*(Atau untuk build file produksi terkompilasi)*:
```powershell
npm run build
```

### 3. Jalankan WebSocket Reverb (Opsional untuk Live KDS & Tracing Broadcast)
```powershell
php artisan reverb:start
```

---

## 🌐 Daftar URL Aplikasi

### 🛒 Frontend Pelanggan (Inertia Vue 3)
- **Beranda & Open PO**: [http://localhost:8000/](http://localhost:8000/)
- **Halaman Pre-Order Cabang**: [http://localhost:8000/campaign/{token}](http://localhost:8000/campaign/{token})
- **Direktori Cabang Dapur**: [http://localhost:8000/stores](http://localhost:8000/stores)
- **Pelacakan Status Pesanan Real-Time**: [http://localhost:8000/trace](http://localhost:8000/trace) atau [http://localhost:8000/trace/{orderNumber}](http://localhost:8000/trace/{orderNumber})

### 🛡️ Administrator & Dapur Backend (Blade AdminLTE Multi-Theme)
- **Login Admin**: [http://localhost:8000/backend/login](http://localhost:8000/backend/login)
  - **Username**: `naufal_admin`
  - **Password**: `naufal2020`
- **Dashboard Admin**: [http://localhost:8000/backend/dashboard](http://localhost:8000/backend/dashboard)
- **Kitchen Display System (KDS)**: [http://localhost:8000/backend/kds](http://localhost:8000/backend/kds)
- **Dokumentasi API Interaktif**: [http://localhost:8000/backend/api-docs](http://localhost:8000/backend/api-docs)

---

## ✨ Fitur-Fitur Utama

1. **Single Project & Single Domain**: Tidak memerlukan port terpisah (misal port 5173). Semua rute frontend dan backend berjalan mulus di port 8000.
2. **Inertia.js Vue 3 SPA**: Navigasi halaman cepat tanpa reload penuh (*single-page application* feel) dengan layout `AppLayout.vue`.
3. **Kustomisasi Menu & Floating Cart**: Pilih opsi upsize, topping tambahan, tingkat pedas, dan catatan dapur langsung dari modal interaktif.
4. **Dynamic QRIS Per Cabang**: Menampilkan barcode QRIS merchant toko secara dinamis dengan nominal otomatis.
5. **Customer Profiling & LocalStorage Cache**: Otomatis menyimpan nama, nomor WhatsApp, dan email pelanggan untuk kemudahan pemesanan berikutnya.
6. **5-Tahap Live Tracing**: Pelanggan dapat memantau pesanan mereka secara langsung dari dapur (*Order Konfirmed* ➔ *On Proses* ➔ *On Packaging* ➔ *On Delivery* ➔ *Order Ready*).
7. **AdminLTE Multi-Theme**: Dashboard backend lengkap dengan pilihan tema Lite, Dark, dan Primary Blue.
