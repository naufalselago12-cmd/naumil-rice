@extends('layouts.admin')

@section('title', 'Dokumentasi API Backend')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Dokumentasi API Backend & WebSocket</h2>
        <p class="text-muted mb-0">Spesifikasi REST API v1 dan Event Broadcasting untuk frontend e-commerce, tracking pesanan, dan KDS.</p>
    </div>
    <span class="badge bg-success fs-6 p-2">API Version: 1.0.0 (Active)</span>
</div>

<div class="row g-4">
    <!-- Quick Jump Menu -->
    <div class="col-lg-3">
        <div class="card-custom sticky-top" style="top: 80px;">
            <div class="card-custom-header">
                <h6 class="card-custom-title"><i class="fa-solid fa-list me-2"></i> Daftar Endpoint</h6>
            </div>
            <div class="list-group list-group-flush small">
                <a href="#ep-stores" class="list-group-item list-group-item-action fw-semibold">1. Toko / Cabang</a>
                <a href="#ep-campaigns" class="list-group-item list-group-item-action fw-semibold">2. Campaign Open PO & QR</a>
                <a href="#ep-clients" class="list-group-item list-group-item-action fw-semibold">3. Pelanggan & Sugesti</a>
                <a href="#ep-orders" class="list-group-item list-group-item-action fw-semibold">4. Submit Pre-Order</a>
                <a href="#ep-trace" class="list-group-item list-group-item-action fw-semibold">5. Tracing Status Pesanan</a>
                <a href="#ep-kds" class="list-group-item list-group-item-action fw-semibold">6. Kitchen Display (KDS)</a>
                <a href="#ep-websocket" class="list-group-item list-group-item-action fw-semibold text-danger">7. Laravel WebSocket (Reverb)</a>
            </div>
        </div>
    </div>

    <!-- API Docs Details -->
    <div class="col-lg-9">
        <!-- 1. Stores -->
        <div class="card-custom mb-4" id="ep-stores">
            <div class="card-custom-header">
                <div>
                    <span class="badge bg-success me-2">GET</span>
                    <strong class="font-monospace fs-6">/api/v1/stores</strong>
                </div>
                <small class="text-muted">Ambil semua cabang aktif & QRIS</small>
            </div>
            <div class="p-3">
                <p class="small text-muted mb-2">Mengambil seluruh cabang toko aktif beserta informasi logo dan QRIS dinamis.</p>
                <div class="bg-dark text-light p-3 rounded-3 font-monospace small mb-3">
                    <div class="text-info">// Contoh Request</div>
                    curl -X GET "http://localhost:8000/api/v1/stores" -H "Accept: application/json"
                </div>

                <div class="bg-dark text-light p-3 rounded-3 font-monospace small" style="max-height: 220px; overflow-y: auto;">
                    <div class="text-success">// Contoh Response 200 OK</div>
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Naumil Store - Flagship Sudirman",
      "code": "NR-SDR",
      "address": "Jl. Jenderal Sudirman No. 45, Jakarta Selatan",
      "phone": "081298765432",
      "logo": "http://localhost:8000/storage/stores/logos/sdr.jpg",
      "qris_image": "http://localhost:8000/storage/stores/qris/sdr_qris.png",
      "qris_data": "00020101021226600014ID.LINKAJA.WWW...",
      "status": "active"
    }
  ]
}
                </div>
            </div>
        </div>

        <!-- 2. Campaigns -->
        <div class="card-custom mb-4" id="ep-campaigns">
            <div class="card-custom-header">
                <div>
                    <span class="badge bg-success me-2">GET</span>
                    <strong class="font-monospace fs-6">/api/v1/campaigns/{token}</strong>
                </div>
                <small class="text-muted">Detail Campaign via QR Token & Cek Expired</small>
            </div>
            <div class="p-3">
                <p class="small text-muted mb-2">
                    Dipanggil ketika user scan link QR Toko. Mengembalikan informasi menu PO, topping, diskon khusus, dan status apakah campaign masih aktif atau sudah expired/close order.
                </p>
                <div class="bg-dark text-light p-3 rounded-3 font-monospace small mb-3">
                    <div class="text-info">// Contoh Request</div>
                    curl -X GET "http://localhost:8000/api/v1/campaigns/PO-NR-SDR-ABC123" -H "Accept: application/json"
                </div>

                <div class="bg-dark text-light p-3 rounded-3 font-monospace small" style="max-height: 250px; overflow-y: auto;">
                    <div class="text-success">// Contoh Response 200 OK (Open PO)</div>
{
  "success": true,
  "is_expired": false,
  "message": "Campaign aktif.",
  "data": {
    "campaign": {
      "id": 1,
      "title": "Open Pre-Order Batch #15: Naumil Store Nusantara Feasts",
      "close_order_at": "2026-10-15T21:00:00+07:00",
      "ready_at": "2026-10-16T11:30:00+07:00",
      "qr_token": "PO-NR-SDR-ABC123",
      "is_expired": false
    },
    "store": {
      "id": 1,
      "name": "Naumil Store - Flagship Sudirman",
      "qris_image": "https://api.qrserver.com/v1/create-qr-code/?...",
      "qris_data": "00020101021226600014ID..."
    },
    "menus": [
      {
        "id": 1,
        "name": "Rice Bowl Cumi Hitam Madura",
        "original_price": 45000,
        "discount_price": 5000,
        "final_price": 40000,
        "options": {
          "toppings": [
            { "name": "Telur Mata Sapi Setengah Matang", "price": 6000 }
          ],
          "upsizes": [
            { "name": "Jumbo Size (Nasi + 30% Lauk)", "price": 12000 }
          ]
        }
      }
    ]
  }
}
                </div>
            </div>
        </div>

        <!-- 3. Clients Suggestion -->
        <div class="card-custom mb-4" id="ep-clients">
            <div class="card-custom-header">
                <div>
                    <span class="badge bg-success me-2">GET</span>
                    <strong class="font-monospace fs-6">/api/v1/clients</strong>
                </div>
                <small class="text-muted">Sugesti Dropdown Pelanggan</small>
            </div>
            <div class="p-3">
                <p class="small text-muted mb-2">
                    Menampilkan daftar customer yang pernah mendaftar dengan format concat: <code>Nama [No WA, Email]</code> jika browser user belum menyimpan cache local.
                </p>
                <div class="bg-dark text-light p-3 rounded-3 font-monospace small" style="max-height: 180px; overflow-y: auto;">
                    <div class="text-success">// Contoh Response 200 OK</div>
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
                </div>
            </div>
        </div>

        <!-- 4. Submit Order -->
        <div class="card-custom mb-4" id="ep-orders">
            <div class="card-custom-header">
                <div>
                    <span class="badge bg-primary me-2">POST</span>
                    <strong class="font-monospace fs-6">/api/v1/orders</strong>
                </div>
                <small class="text-muted">Submit Pre-Order Pelanggan</small>
            </div>
            <div class="p-3">
                <p class="small text-muted mb-2">
                    User submit pre-order makanan dengan nama, WhatsApp, email (wajib), pilihan menu + topping/upsize/catatan, dan metode pembayaran (QRIS Dinamis atau Bayar Terima Makanan).
                </p>
                <div class="bg-dark text-light p-3 rounded-3 font-monospace small mb-3" style="max-height: 250px; overflow-y: auto;">
                    <div class="text-info">// Contoh Request Body Payload JSON</div>
{
  "store_id": 1,
  "campaign_id": 1,
  "customer_name": "Dimas Wicaksono",
  "customer_whatsapp": "081234567890",
  "customer_email": "dimas.wicak@gmail.com",
  "customer_address": "Gedung Menara Sudirman Lt 14",
  "payment_method": "QRIS Dinamis",
  "notes": "Pisahkan sambal",
  "items": [
    {
      "menu_id": 1,
      "quantity": 2,
      "selected_options": [
        { "name": "Telur Mata Sapi Setengah Matang", "price": 6000 },
        { "name": "Jumbo Size", "price": 12000 }
      ],
      "special_notes": "1 pedas sedang, 1 extra pedas"
    }
  ]
}
                </div>

                <div class="bg-dark text-light p-3 rounded-3 font-monospace small" style="max-height: 180px; overflow-y: auto;">
                    <div class="text-success">// Contoh Response 201 Created</div>
{
  "success": true,
  "message": "Pre-Order berhasil dibuat!",
  "data": {
    "order_number": "NR-PO-261012-A8F2",
    "status": "Order Konfirmed",
    "total_amount": 116000,
    "payment_method": "QRIS Dinamis",
    "store_qris_image": "https://api.qrserver.com/v1/create-qr-code/?data=...",
    "store_qris_data": "000201010212..."
  }
}
                </div>
            </div>
        </div>

        <!-- 5. Tracing -->
        <div class="card-custom mb-4" id="ep-trace">
            <div class="card-custom-header">
                <div>
                    <span class="badge bg-success me-2">GET</span>
                    <strong class="font-monospace fs-6">/api/v1/orders/trace/{orderNumber}</strong>
                </div>
                <small class="text-muted">Pelacakan Live Status Pesanan Pelanggan</small>
            </div>
            <div class="p-3">
                <p class="small text-muted mb-2">
                    Mengembalikan tahapan pemrosesan: <code>Order Konfirmed &gt; Order On Proses &gt; Order On Packaging &gt; Order On Delivery &gt; Order Ready</code> (atau <code>Order Reject</code>).
                </p>
                <div class="bg-dark text-light p-3 rounded-3 font-monospace small" style="max-height: 200px; overflow-y: auto;">
                    <div class="text-success">// Contoh Response 200 OK</div>
{
  "success": true,
  "data": {
    "order": {
      "order_number": "NR-PO-261012-A8F2",
      "status": "Order On Proses",
      "total_amount": 116000
    },
    "stages": [
      "Order Konfirmed",
      "Order On Proses",
      "Order On Packaging",
      "Order On Delivery",
      "Order Ready"
    ],
    "current_stage_index": 1,
    "is_rejected": false
  }
}
                </div>
            </div>
        </div>

        <!-- 6. KDS -->
        <div class="card-custom mb-4" id="ep-kds">
            <div class="card-custom-header">
                <div>
                    <span class="badge bg-success me-2">GET</span>
                    <strong class="font-monospace fs-6">/api/v1/orders/kds</strong>
                </div>
                <small class="text-muted">Antrean Pesanan Dapur KDS</small>
            </div>
            <div class="p-3">
                <p class="small text-muted mb-2">Mengambil semua order aktif yang sedang dalam antrean dapur atau pengemasan.</p>
                <div class="bg-dark text-light p-3 rounded-3 font-monospace small mb-3">
                    curl -X GET "http://localhost:8000/api/v1/orders/kds?store_id=1"
                </div>

                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-warning text-dark">PATCH</span>
                    <strong class="font-monospace">/api/v1/orders/{id}/status</strong>
                </div>
                <div class="bg-dark text-light p-3 rounded-3 font-monospace small">
                    <div class="text-info">// Request Payload Update Status</div>
{
  "status": "Order On Packaging",
  "note": "Makanan telah selesai dimasak di wok"
}
                </div>
            </div>
        </div>

        <!-- 7. WebSocket Reverb -->
        <div class="card-custom mb-4" id="ep-websocket">
            <div class="card-custom-header bg-danger text-white">
                <div>
                    <i class="fa-solid fa-bolt me-2"></i>
                    <strong>Laravel WebSocket (Reverb) Realtime Channels</strong>
                </div>
            </div>
            <div class="p-3">
                <p class="small text-muted mb-2">
                    Websocket default terhubung pada port <code>8080</code> (Host: <code>localhost</code>).
                </p>
                <table class="table table-bordered small">
                    <thead class="table-light">
                        <tr>
                            <th>Channel Name</th>
                            <th>Event Name</th>
                            <th>Deskripsi Kegunaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-monospace text-primary">orders</td>
                            <td class="font-monospace text-success">order.status.updated</td>
                            <td>Broadcast global perubahan status ke Kitchen Display System (KDS)</td>
                        </tr>
                        <tr>
                            <td class="font-monospace text-primary">order.{order_number}</td>
                            <td class="font-monospace text-success">order.status.updated</td>
                            <td>Live update langsung pada layar tracing pemesan pengguna</td>
                        </tr>
                        <tr>
                            <td class="font-monospace text-primary">store.{store_id}</td>
                            <td class="font-monospace text-success">order.created</td>
                            <td>Notifikasi instan tiket order baru masuk di cabang dapur tersebut</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
