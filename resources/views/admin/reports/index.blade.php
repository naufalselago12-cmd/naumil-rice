@extends('layouts.admin')

@section('title', 'Laporan Status & Aktivitas Pesanan')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1">Laporan Aktivitas & Penjualan Pre-Order</h2>
        <p class="text-muted mb-0">Pantau performa order makanan, rasio penyelesaian dapur, dan menu terlaris per cabang.</p>
    </div>

    <!-- Date Range & Store Filters -->
    <form method="GET" action="{{ route('admin.reports') }}" class="d-flex flex-wrap align-items-center gap-2">
        <select name="store_id" class="form-select form-select-sm" style="min-width: 170px;">
            <option value="">Semua Cabang</option>
            @foreach($stores as $st)
                <option value="{{ $st->id }}" {{ $selectedStoreId == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
            @endforeach
        </select>

        <div class="d-flex align-items-center gap-1">
            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            <span class="text-muted">-</span>
            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
        </div>

        <button type="submit" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-filter me-1"></i> Terapkan
        </button>
        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-print me-1"></i> Cetak Laporan
        </button>
    </form>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card-custom p-3 border-start border-4 border-success">
            <span class="text-muted small fw-semibold d-block">TOTAL OMSET DITERIMA</span>
            <h3 class="fw-bold text-success mb-0 mt-1">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
            <small class="text-muted">Dari pesanan valid (non-reject)</small>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card-custom p-3 border-start border-4 border-primary">
            <span class="text-muted small fw-semibold d-block">TOTAL PESANAN MASUK</span>
            <h3 class="fw-bold text-primary mb-0 mt-1">{{ $totalOrders }} Order</h3>
            <small class="text-muted">Total transaksi periode terpilih</small>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card-custom p-3 border-start border-4 border-info">
            <span class="text-muted small fw-semibold d-block">PESANAN SELESAI (READY)</span>
            <h3 class="fw-bold text-info mb-0 mt-1">{{ $readyOrders }} Selesai</h3>
            <small class="text-success fw-semibold">
                {{ $totalOrders > 0 ? round(($readyOrders / $totalOrders) * 100) : 0 }}% Tingkat Penyelesaian
            </small>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card-custom p-3 border-start border-4 border-danger">
            <span class="text-muted small fw-semibold d-block">DITOLAK / REJECT</span>
            <h3 class="fw-bold text-danger mb-0 mt-1">{{ $rejectOrders }} Dibatalkan</h3>
            <small class="text-muted">Stok habis atau kendala dapur</small>
        </div>
    </div>
</div>

<!-- Stage Breakdown Cards -->
<div class="card-custom mb-4">
    <div class="card-custom-header">
        <h5 class="card-custom-title"><i class="fa-solid fa-bars-staggered me-2 text-primary"></i> Sebaran Status Pesanan</h5>
    </div>
    <div class="p-4">
        <div class="row g-3 text-center">
            <div class="col-md-2 col-4">
                <div class="p-3 bg-light rounded border">
                    <span class="text-muted small d-block">Konfirmasi</span>
                    <h4 class="fw-bold text-secondary mb-0">{{ $statusBreakdown[\App\Models\Order::STATUS_CONFIRMED] }}</h4>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-3 bg-light rounded border">
                    <span class="text-muted small d-block">On Proses Dapur</span>
                    <h4 class="fw-bold text-primary mb-0">{{ $statusBreakdown[\App\Models\Order::STATUS_PROCESSING] }}</h4>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-3 bg-light rounded border">
                    <span class="text-muted small d-block">On Packaging</span>
                    <h4 class="fw-bold text-warning mb-0">{{ $statusBreakdown[\App\Models\Order::STATUS_PACKAGING] }}</h4>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-3 bg-light rounded border">
                    <span class="text-muted small d-block">On Delivery</span>
                    <h4 class="fw-bold text-info mb-0">{{ $statusBreakdown[\App\Models\Order::STATUS_DELIVERY] }}</h4>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-3 bg-light rounded border">
                    <span class="text-muted small d-block">Order Ready</span>
                    <h4 class="fw-bold text-success mb-0">{{ $statusBreakdown[\App\Models\Order::STATUS_READY] }}</h4>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-3 bg-light rounded border">
                    <span class="text-muted small d-block">Order Reject</span>
                    <h4 class="fw-bold text-danger mb-0">{{ $statusBreakdown[\App\Models\Order::STATUS_REJECT] }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Top Selling Menu Items -->
    <div class="col-lg-5">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-trophy me-2 text-warning"></i> Menu Makanan Terlaris</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">Rank</th>
                            <th>Nama Menu</th>
                            <th class="text-center" style="width: 90px;">Porsi</th>
                            <th class="text-end" style="width: 130px;">Total Omset</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topItems as $idx => $item)
                            <tr>
                                <td>
                                    <span class="badge {{ $idx === 0 ? 'bg-warning text-dark' : ($idx === 1 ? 'bg-secondary' : 'bg-light text-dark border') }} rounded-pill">
                                        #{{ $idx + 1 }}
                                    </span>
                                </td>
                                <td class="fw-bold">{{ $item->menu_name }}</td>
                                <td class="text-center fw-bold">{{ $item->total_qty }}x</td>
                                <td class="text-end fw-semibold">Rp {{ number_format($item->total_sales, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Belum ada item terjual.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Detailed Orders Table -->
    <div class="col-lg-7">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-list-check me-2 text-primary"></i> Rekapitulasi Order ({{ $allOrders->count() }})</h5>
            </div>
            <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>No. Order</th>
                            <th>Pelanggan</th>
                            <th>Metode Bayar</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allOrders as $ord)
                            <tr>
                                <td class="font-monospace fw-bold text-primary">
                                    <a href="{{ route('admin.orders.show', $ord->id) }}" class="text-decoration-none">
                                        {{ $ord->order_number }}
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $ord->client->name }}</div>
                                    <small class="text-muted">{{ $ord->client->whatsapp }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $ord->payment_method }}</span>
                                </td>
                                <td class="fw-bold">
                                    Rp {{ number_format($ord->total_amount, 0, ',', '.') }}
                                </td>
                                <td>
                                    <span class="badge {{ $ord->status === 'Order Ready' ? 'bg-success' : ($ord->status === 'Order Reject' ? 'bg-danger' : 'bg-primary') }}">
                                        {{ $ord->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Tidak ada transaksi di rentang tanggal ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
