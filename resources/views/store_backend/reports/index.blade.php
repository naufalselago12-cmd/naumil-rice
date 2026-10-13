@extends('store_backend.layouts.app')

@section('title', 'Laporan Penjualan - ' . $store->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1 text-dark">Laporan Penjualan & Status: {{ $store->name }}</h2>
        <p class="text-muted mb-0">Rekapitulasi omset, performa porsi, dan pergerakan order pada periode tertentu.</p>
    </div>

    <!-- Date Range Filter -->
    <form method="GET" action="{{ route('store.backend.reports', $store->slug) }}" class="d-flex align-items-center gap-2">
        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
        <span class="text-muted">s/d</span>
        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter me-1"></i> Terapkan</button>
    </form>
</div>

<!-- Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="metric-box-light">
            <div class="lbl">Total Pendapatan</div>
            <div class="val text-success">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            <div class="icon"><i class="fa-solid fa-rupiah-sign"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-box-light">
            <div class="lbl">Total Order Masuk</div>
            <div class="val text-primary">{{ $totalOrders }}</div>
            <div class="icon"><i class="fa-solid fa-bag-shopping"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-box-light">
            <div class="lbl">Pesanan Selesai</div>
            <div class="val text-info">{{ $readyOrders }}</div>
            <div class="icon"><i class="fa-solid fa-circle-check"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-box-light">
            <div class="lbl">Pesanan Dibatalkan</div>
            <div class="val text-danger">{{ $rejectOrders }}</div>
            <div class="icon"><i class="fa-solid fa-ban"></i></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Top Selling Menus in this store -->
    <div class="col-lg-6">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-fire text-danger me-2"></i> Menu Terlaris di Cabang Ini</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Menu</th>
                            <th class="text-center">Porsi Terjual</th>
                            <th class="text-end">Total Omset</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topItems as $item)
                            <tr>
                                <td><strong class="text-dark">{{ $item->menu_name }}</strong></td>
                                <td class="text-center font-mono fw-bold fs-6">{{ $item->total_qty }} porsi</td>
                                <td class="text-end font-mono text-success fw-bold">Rp {{ number_format($item->total_sales, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">Belum ada penjualan di periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Status Breakdown -->
    <div class="col-lg-6">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-pie-chart text-primary me-2"></i> Rekap Status Pengerjaan</h5>
            </div>
            <div class="p-4">
                <ul class="list-group list-group-flush">
                    @foreach($statusBreakdown as $statusName => $count)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="fw-semibold text-dark">{{ $statusName }}</span>
                            <span class="badge bg-secondary font-mono fs-6">{{ $count }} Pesanan</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
