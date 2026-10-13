@extends('store_backend.layouts.app')

@section('title', 'Dashboard Dapur - ' . $store->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1 text-dark">Dashboard Dapur: {{ $store->name }}</h2>
        <p class="text-muted mb-0">Monitoring pesanan masuk, persiapan memasak, dan performa cabang {{ $store->code }}.</p>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('store.backend.kds', $store->slug) }}" class="btn btn-danger">
            <i class="fa-solid fa-desktop me-1"></i> Buka Layar KDS
        </a>
        <a href="{{ route('store.backend.menus.create', $store->slug) }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Tambah Menu
        </a>
    </div>
</div>

<!-- Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="metric-box-light">
            <div class="lbl">Total Omset Cabang</div>
            <div class="val text-success">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            <small class="text-muted"><i class="fa-solid fa-receipt me-1"></i> Dari {{ $totalOrders }} pesanan masuk</small>
            <div class="icon"><i class="fa-solid fa-rupiah-sign"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="metric-box-light">
            <div class="lbl">Total Pesanan</div>
            <div class="val text-primary">{{ $totalOrders }}</div>
            <small class="text-muted"><i class="fa-solid fa-bag-shopping me-1"></i> Transaksi di cabang ini</small>
            <div class="icon"><i class="fa-solid fa-bag-shopping"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="metric-box-light">
            <div class="lbl">Campaign Open PO Aktif</div>
            <div class="val text-warning">{{ $activeCampaigns }}</div>
            <small class="text-muted"><i class="fa-solid fa-bullhorn me-1"></i> Batch siap dipesan</small>
            <div class="icon"><i class="fa-solid fa-bullhorn"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="metric-box-light">
            <div class="lbl">Varian Menu Siap Saji</div>
            <div class="val text-info">{{ $totalMenus }}</div>
            <small class="text-muted"><i class="fa-solid fa-bowl-rice me-1"></i> Menu aktif di cabang ini</small>
            <div class="icon"><i class="fa-solid fa-bowl-food"></i></div>
        </div>
    </div>
</div>

<!-- Status Grid -->
<div class="row g-2 mb-4">
    @php
        $statusColors = [
            'Order Konfirmed' => 'info',
            'Order On Proses' => 'warning',
            'Order On Packaging' => 'secondary',
            'Order On Delivery' => 'primary',
            'Order Ready' => 'success',
            'Order Reject' => 'danger'
        ];
    @endphp
    @foreach($ordersByStatus as $statusName => $count)
        <div class="col-md-2 col-6">
            <div class="card p-3 border text-center rounded-3 bg-white shadow-sm">
                <span class="badge bg-{{ $statusColors[$statusName] ?? 'primary' }} mb-2 mx-auto">{{ $statusName }}</span>
                <div class="fs-4 fw-bold font-mono">{{ $count }}</div>
                <small class="text-muted" style="font-size: 0.72rem;">Pesanan</small>
            </div>
        </div>
    @endforeach
</div>

<!-- Recent Orders Table -->
<div class="card-custom">
    <div class="card-custom-header">
        <h5 class="card-custom-title"><i class="fa-solid fa-receipt text-primary me-2"></i> 10 Pesanan Terbaru Cabang Ini</h5>
        <a href="{{ route('store.backend.orders.index', $store->slug) }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Order</th>
                    <th>Pelanggan</th>
                    <th>Menu Dipesan</th>
                    <th>Total Bayar</th>
                    <th>Metode & Status Bayar</th>
                    <th>Status Pengerjaan</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $ord)
                    <tr>
                        <td>
                            <a href="{{ route('store.backend.orders.show', ['store_slug' => $store->slug, 'order' => $ord->id]) }}" 
                               class="fw-bold font-mono text-primary text-decoration-none">
                                {{ $ord->order_number }}
                            </a>
                            <small class="text-muted d-block">{{ $ord->created_at->diffForHumans() }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $ord->client->name ?? '-' }}</div>
                            <small class="text-muted font-mono">{{ $ord->client->whatsapp ?? '-' }}</small>
                        </td>
                        <td>
                            <small class="text-truncate d-inline-block" style="max-width: 220px;">
                                @foreach($ord->items as $it)
                                    {{ $it->menu_name }} (x{{ $it->quantity }}){{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            </small>
                        </td>
                        <td class="font-mono fw-bold text-success">
                            Rp {{ number_format($ord->total_amount, 0, ',', '.') }}
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $ord->payment_method }}</span>
                            <span class="badge {{ $ord->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }} ms-1">
                                {{ $ord->payment_status }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $statusColors[$ord->status] ?? 'secondary' }}">
                                {{ $ord->status }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('store.backend.orders.show', ['store_slug' => $store->slug, 'order' => $ord->id]) }}" 
                               class="btn btn-sm btn-outline-primary">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Belum ada pesanan masuk untuk cabang ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
