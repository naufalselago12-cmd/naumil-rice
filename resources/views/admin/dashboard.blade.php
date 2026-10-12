@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1">Dashboard Ikhtisar</h2>
        <p class="text-muted mb-0">Statistik penjualan, antrean pre-order makanan, dan performa cabang.</p>
    </div>

    <!-- Store Selector Filter -->
    <form method="GET" action="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2">
        <label class="fw-semibold text-nowrap"><i class="fa-solid fa-location-dot me-1 text-danger"></i> Filter Cabang:</label>
        <select name="store_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 220px;">
            <option value="">Semua Cabang (Konsolidasi)</option>
            @foreach($stores as $st)
                <option value="{{ $st->id }}" {{ ($selectedStore && $selectedStore->id == $st->id) ? 'selected' : '' }}>
                    {{ $st->name }} ({{ $st->code }})
                </option>
            @endforeach
        </select>
    </form>
</div>

<!-- Small Boxes / Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="small-box" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
            <div class="inner">
                <h3>Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
                <p>Total Omset Pre-Order</p>
            </div>
            <div class="icon">
                <i class="fa-solid fa-rupiah-sign"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="small-box" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
            <div class="inner">
                <h3>{{ $totalOrders }}</h3>
                <p>Total Pesanan Masuk</p>
            </div>
            <div class="icon">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="small-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <div class="inner">
                <h3>{{ $activeCampaigns }}</h3>
                <p>Campaign Open PO Aktif</p>
            </div>
            <div class="icon">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="small-box" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
            <div class="inner">
                <h3>{{ $totalStores }}</h3>
                <p>Cabang & Dapur Naumil</p>
            </div>
            <div class="icon">
                <i class="fa-solid fa-store"></i>
            </div>
        </div>
    </div>
</div>

<!-- Order Status Pipeline Progress -->
<div class="card-custom mb-4">
    <div class="card-custom-header">
        <h5 class="card-custom-title"><i class="fa-solid fa-bars-progress me-2 text-primary"></i> Live Kitchen & Delivery Stages</h5>
        <a href="{{ route('admin.kds') }}" class="btn btn-sm btn-outline-danger">
            <i class="fa-solid fa-tv me-1"></i> Buka Layar KDS
        </a>
    </div>
    <div class="p-4">
        <div class="row g-3 text-center">
            <div class="col-md-2 col-4">
                <div class="p-3 rounded-3 bg-light border">
                    <span class="badge bg-secondary mb-2">Tahap 1</span>
                    <div class="fs-4 fw-bold text-dark">{{ $ordersByStatus[\App\Models\Order::STATUS_CONFIRMED] ?? 0 }}</div>
                    <small class="text-muted d-block fw-semibold">Order Konfirmed</small>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-3 rounded-3 bg-light border">
                    <span class="badge bg-primary mb-2">Tahap 2</span>
                    <div class="fs-4 fw-bold text-primary">{{ $ordersByStatus[\App\Models\Order::STATUS_PROCESSING] ?? 0 }}</div>
                    <small class="text-muted d-block fw-semibold">Order On Proses</small>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-3 rounded-3 bg-light border">
                    <span class="badge bg-warning text-dark mb-2">Tahap 3</span>
                    <div class="fs-4 fw-bold text-warning">{{ $ordersByStatus[\App\Models\Order::STATUS_PACKAGING] ?? 0 }}</div>
                    <small class="text-muted d-block fw-semibold">Order On Packaging</small>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-3 rounded-3 bg-light border">
                    <span class="badge bg-info text-dark mb-2">Tahap 4</span>
                    <div class="fs-4 fw-bold text-info">{{ $ordersByStatus[\App\Models\Order::STATUS_DELIVERY] ?? 0 }}</div>
                    <small class="text-muted d-block fw-semibold">Order On Delivery</small>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-3 rounded-3 bg-light border">
                    <span class="badge bg-success mb-2">Tahap 5</span>
                    <div class="fs-4 fw-bold text-success">{{ $ordersByStatus[\App\Models\Order::STATUS_READY] ?? 0 }}</div>
                    <small class="text-muted d-block fw-semibold">Order Ready</small>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-3 rounded-3 bg-light border">
                    <span class="badge bg-danger mb-2">Dibatalkan</span>
                    <div class="fs-4 fw-bold text-danger">{{ $ordersByStatus[\App\Models\Order::STATUS_REJECT] ?? 0 }}</div>
                    <small class="text-muted d-block fw-semibold">Order Reject</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders Table & Quick Actions -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-clock-rotate-left me-2 text-info"></i> Pesanan Pre-Order Terbaru</h5>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Order</th>
                            <th>Pelanggan</th>
                            <th>Cabang</th>
                            <th>Total</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                            <tr>
                                <td class="fw-bold font-monospace text-primary">{{ $order->order_number }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $order->client->name }}</div>
                                    <small class="text-muted">{{ $order->client->whatsapp }}</small>
                                </td>
                                <td><span class="badge bg-secondary">{{ $order->store->code }}</span></td>
                                <td class="fw-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge {{ $order->payment_method === 'QRIS Dinamis' ? 'bg-info text-dark' : 'bg-warning text-dark' }}">
                                        {{ $order->payment_method }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match($order->status) {
                                            'Order Konfirmed' => 'bg-secondary',
                                            'Order On Proses' => 'bg-primary',
                                            'Order On Packaging' => 'bg-warning text-dark',
                                            'Order On Delivery' => 'bg-info text-dark',
                                            'Order Ready' => 'bg-success',
                                            'Order Reject' => 'bg-danger',
                                            default => 'bg-light text-dark'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $order->status }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Belum ada pesanan masuk.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Shortcuts -->
    <div class="col-lg-4">
        <div class="card-custom mb-4">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-bolt me-2 text-warning"></i> Aksi Cepat</h5>
            </div>
            <div class="p-3 d-grid gap-2">
                <a href="{{ route('admin.stores.create') }}" class="btn btn-outline-primary text-start p-2">
                    <i class="fa-solid fa-plus-circle me-2"></i> Tambah Cabang / Toko Baru
                </a>
                <a href="{{ route('admin.menus.create') }}" class="btn btn-outline-success text-start p-2">
                    <i class="fa-solid fa-plus-circle me-2"></i> Tambah Menu & Topping Baru
                </a>
                <a href="{{ route('admin.campaigns.create') }}" class="btn btn-outline-warning text-start p-2">
                    <i class="fa-solid fa-qrcode me-2"></i> Buat Campaign Open PO & QR Baru
                </a>
                <a href="{{ route('admin.kds') }}" class="btn btn-danger text-start p-2">
                    <i class="fa-solid fa-desktop me-2"></i> Mode Fullscreen Kitchen Display (KDS)
                </a>
            </div>
        </div>

        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-info-circle me-2 text-info"></i> Petunjuk Pre-Order & QRIS</h5>
            </div>
            <div class="p-3 fs-7 text-muted" style="font-size: 0.88rem;">
                <p class="mb-2"><strong>1. Setting QRIS Toko:</strong> Unggah file QRIS atau masukkan kode payload QRIS dinamis di menu <em>Cabang/Toko</em>.</p>
                <p class="mb-2"><strong>2. Open PO Campaign:</strong> Buat campaign dengan menentukan menu yang diikutkan, diskon khusus, tanggal batas close order, dan ready order.</p>
                <p class="mb-2"><strong>3. QR Expired:</strong> QR code campaign akan otomatis expired bila tanggal close order sudah terlewati.</p>
                <p class="mb-0"><strong>4. Live KDS:</strong> Dapur dapat melihat antrean pesanan dan menaikkan tahap pesanan secara real-time via WebSocket.</p>
            </div>
        </div>
    </div>
</div>
@endsection
