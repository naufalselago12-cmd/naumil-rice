# Dokumentasi Lengkap API Backend & WebSocket Naumil Store

Sistem E-Commerce & Pre-Order Makanan dengan Multi-Store, Dynamic QRIS, Open PO Campaign, Kitchen Display System (KDS), dan Real-Time Order Tracing.

---

## 1. Konfigurasi Lingkungan & Menjalankan Server

### Database MySQL (Lokal):
- **Host**: `localhost` (Port: `3306`)
- **Database Name**: `naumil_rice_db`
- **Username**: `root`
- **Password**: `(kosong / empty)`

### Menjalankan Backend Laravel:
```bash
cd backend
php artisan serve --port=8000
```
*Aplikasi Frontend*: `http://localhost:8000/` (Root domain langsung ke frontend)  
*Login Backend Admin*: `http://localhost:8000/backend/login` atau `/backend` (User: `naufal_admin` | Pass: `naufal2020`)  
*Admin Dashboard*: `http://localhost:8000/backend/dashboard`  
*KDS Fullscreen*: `http://localhost:8000/backend/kds`  

### Menjalankan WebSocket Laravel (Reverb):
```bash
cd backend
php artisan reverb:start
```
*WebSocket Host*: `localhost:8080`

### Menjalankan Frontend Vue.js (Vite):
```bash
cd frontend
npm run dev
```
*Frontend URL*: `http://localhost:5173`

---

## 2. Ringkasan Endpoint REST API (v1)

| No | Method | Endpoint | Deskripsi |
|---|---|---|---|
| 1 | `GET` | `/api/v1/stores` | Mengambil seluruh cabang toko aktif & info QRIS |
| 2 | `GET` | `/api/v1/stores/{id}` | Mengambil detail satu cabang toko |
| 3 | `GET` | `/api/v1/campaigns` | Mengambil seluruh campaign Open PO aktif |
| 4 | `GET` | `/api/v1/campaigns/{token}` | Mengambil detail campaign PO dari QR Token & cek expired |
| 5 | `GET` | `/api/v1/clients` | Mengambil daftar customer untuk dropdown sugesti |
| 6 | `POST` | `/api/v1/clients/check-or-register` | Validasi atau registrasi customer baru |
| 7 | `POST` | `/api/v1/orders` | Submit Pre-Order makanan dari pelanggan |
| 8 | `GET` | `/api/v1/orders/trace/{orderNumber}` | Lacak tahapan status pesanan secara real-time |
| 9 | `GET` | `/api/v1/orders/kds` | Antrean tiket pesanan untuk Kitchen Display System (KDS) |
| 10 | `PATCH` | `/api/v1/orders/{id}/status` | Update tahapan status pesanan & broadcast WebSocket |

---

## 3. Detail Endpoint & Contoh Pemanggilan

### 3.1. GET `/api/v1/stores`
Mengambil seluruh cabang toko aktif beserta setting nama toko, alamat, logo, dan QRIS dinamis toko.

**Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/stores" -H "Accept: application/json"
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Naumil Store - Flagship Sudirman",
      "code": "NR-SDR",
      "address": "Jl. Jenderal Sudirman No. 45, Jakarta Selatan",
      "phone": "081298765432",
      "logo": "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80",
      "qris_image": "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=000201010212...",
      "qris_data": "00020101021226600014ID.LINKAJA.WWW011893600918000000000102150812987654325204581253033605802ID5911NAUMIL_RICE6007JAKARTA62070703A016304",
      "status": "active",
      "menus_count": 5,
      "campaigns_count": 1
    }
  ]
}
```

---

### 3.2. GET `/api/v1/campaigns/{token}`
Dipanggil ketika pengguna melakukan scan QR Code campaign toko (`/campaign/{token}`). Sistem memvalidasi apakah campaign masih dibuka atau sudah expired (close order passed).

**Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/campaigns/PO-NR-SDR-ABC123" -H "Accept: application/json"
```

**Response (200 OK):**
```json
{
  "success": true,
  "is_expired": false,
  "message": "Campaign aktif.",
  "data": {
    "campaign": {
      "id": 1,
      "title": "Open Pre-Order Batch #15: Naumil Store Nusantara Feasts",
      "slug": "open-po-batch-15-naumil-sudirman",
      "banner_image": "https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200&q=80",
      "description": "Pre-order batch spesial akhir pekan...",
      "discount_type": "fixed",
      "discount_value": 5000,
      "min_purchase_amount": 100000,
      "close_order_at": "2026-10-15T21:00:00+07:00",
      "ready_at": "2026-10-16T11:30:00+07:00",
      "qr_token": "PO-NR-SDR-ABC123",
      "is_active": true,
      "is_expired": false
    },
    "store": {
      "id": 1,
      "name": "Naumil Store - Flagship Sudirman",
      "code": "NR-SDR",
      "address": "Jl. Jenderal Sudirman No. 45, Jakarta Selatan",
      "phone": "081298765432",
      "logo": "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&q=80",
      "qris_image": "https://api.qrserver.com/v1/create-qr-code/?...",
      "qris_data": "000201010212..."
    },
    "menus": [
      {
        "id": 1,
        "name": "Rice Bowl Cumi Hitam Madura",
        "description": "Nasi daun jeruk pulen disajikan dengan cumi saus tinta hitam...",
        "original_price": 45000,
        "custom_price": 45000,
        "discount_price": 5000,
        "final_price": 40000,
        "current_stock": 42,
        "image": "https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&q=80",
        "options": {
          "toppings": [
            { "id": 1, "name": "Telur Mata Sapi Setengah Matang", "price": 6000 }
          ],
          "upsizes": [
            { "id": 4, "name": "Jumbo Size (Nasi + 30% Lauk)", "price": 12000 }
          ],
          "conditions": [
            { "id": 6, "name": "Level Pedas: Extra Pedas Nampol", "price": 3000 }
          ]
        }
      }
    ]
  }
}
```

---

### 3.3. GET `/api/v1/clients`
Mengambil daftar customer terdaftar untuk suggestion dropdown jika user belum memiliki data di cache browser.

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Dimas Wicaksono",
      "whatsapp": "081234567890",
      "email": "dimas.wicak@gmail.com",
      "display_label": "Dimas Wicaksono [081234567890, dimas.wicak@gmail.com]"
    }
  ]
}
```

---

### 3.4. POST `/api/v1/orders`
Membuat pesanan Pre-Order baru dengan verifikasi mandatory customer (nama, WA, email), menu berserta opsi (topping, upsize, catatan), dan metode pembayaran (QRIS Dinamis atau Bayar Terima Makanan).

**Request:**
```bash
curl -X POST "http://localhost:8000/api/v1/orders" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "store_id": 1,
    "campaign_id": 1,
    "customer_name": "Dimas Wicaksono",
    "customer_whatsapp": "081234567890",
    "customer_email": "dimas.wicak@gmail.com",
    "customer_address": "Gedung Menara Sudirman Lt 14",
    "payment_method": "QRIS Dinamis",
    "notes": "Tolong pisahkan sambal mangga di cup terpisah ya kak.",
    "items": [
      {
        "menu_id": 1,
        "quantity": 2,
        "selected_options": [
          { "name": "Telur Mata Sapi Setengah Matang", "price": 6000 },
          { "name": "Jumbo Size (Nasi + 30% Lauk)", "price": 12000 }
        ],
        "special_notes": "1 telur setengah matang, 1 matang"
      }
    ]
  }'
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Pre-Order berhasil dibuat!",
  "data": {
    "order_number": "NR-PO-261012-K7X9",
    "order_id": 4,
    "status": "Order Konfirmed",
    "total_amount": 116000,
    "payment_method": "QRIS Dinamis",
    "store_qris_image": "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=000201...",
    "store_qris_data": "00020101021226600014ID.LINKAJA.WWW..."
  }
}
```

---

### 3.5. GET `/api/v1/orders/trace/{orderNumber}`
Melacak status pesanan pelanggan secara langsung dengan visualisasi tahapan KDS 5 tahap:
`Order Konfirmed` > `Order On Proses` > `Order On Packaging` > `Order On Delivery` > `Order Ready` (atau `Order Reject`).

**Request:**
```bash
curl -X GET "http://localhost:8000/api/v1/orders/trace/NR-PO-261012-001" -H "Accept: application/json"
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 1,
      "order_number": "NR-PO-261012-001",
      "status": "Order On Proses",
      "payment_method": "QRIS Dinamis",
      "payment_status": "paid",
      "subtotal": 97000,
      "discount_amount": 10000,
      "total_amount": 87000,
      "store": {
        "name": "Naumil Store - Flagship Sudirman",
        "phone": "081298765432"
      },
      "client": {
        "name": "Dimas Wicaksono",
        "whatsapp": "081234567890"
      },
      "items": [
        {
          "menu_name": "Rice Bowl Cumi Hitam Madura",
          "price": 40000,
          "quantity": 2,
          "subtotal": 80000,
          "selected_options": [
            { "name": "Telur Mata Sapi Setengah Matang", "price": 6000 }
          ],
          "special_notes": "1 setengah matang, 1 matang biasa"
        }
      ]
    },
    "stages": [
      "Order Konfirmed",
      "Order On Proses",
      "Order On Packaging",
      "Order On Delivery",
      "Order Ready"
    ],
    "current_stage_index": 1,
    "is_rejected": false,
    "rejection_reason": null
  }
}
```

---

### 3.6. GET `/api/v1/orders/kds` & PATCH `/api/v1/orders/{id}/status`
Digunakan oleh antrean Kitchen Display System (KDS) dan admin untuk menaikkan tahapan pesanan dan memicu notifikasi real-time WebSocket.

**Update Status Request:**
```bash
curl -X PATCH "http://localhost:8000/api/v1/orders/1/status" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "Order On Packaging",
    "note": "Makanan telah selesai dimasak di wok dan masuk ke box pengemasan"
  }'
```

---

## 4. Real-Time Laravel WebSocket (Reverb) Integration

Backend menggunakan **Laravel Reverb** sebagai server WebSocket bawaan.

### Event & Channel Mapping:
1. **Event**: `App\Events\OrderStatusUpdated` (`order.status.updated`)
   - **Public Channel**: `orders` (didengarkan oleh seluruh KDS)
   - **Public Channel**: `store.{store_id}` (didengarkan oleh cabang terkait)
   - **Public Channel**: `order.{order_number}` (didengarkan langsung oleh halaman tracing pemesan)
2. **Event**: `App\Events\NewOrderCreated` (`order.created`)
   - **Public Channel**: `orders` & `store.{store_id}`

### Contoh Client Listener (JavaScript / Laravel Echo):
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: 'l0eg4pqm0kfrx3ctzlls',
    wsHost: 'localhost',
    wsPort: 8080,
    wssPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

// Mendengarkan update status pesanan spesifik pada halaman tracing
echo.channel(`order.${orderNumber}`)
    .listen('.order.status.updated', (e) => {
        console.log('Status Pesanan Berubah:', e.status);
        // Perbarui UI secara langsung
    });
```
*(Catatan: Halaman KDS dan Tracing juga dilengkapi fallback polling heartbeat 3 detik sehingga tetap beroperasi real-time meskipun server WebSocket sedang dalam pemeliharaan).*
