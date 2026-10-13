@extends('superadmin.layouts.app')

@section('title', 'Dashboard Master Control')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1 text-white">Dashboard Master Control</h2>
        <p class="text-muted mb-0">Ikhtisar seluruh cabang, performa penjualan global, dan penugasan user toko.</p>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('superadmin.stores.create') }}" class="btn btn-super">
            <i class="fa-solid fa-plus me-1"></i> Tambah Toko Baru
        </a>
        <a href="{{ route('superadmin.users.create') }}" class="btn btn-outline-light">
            <i class="fa-solid fa-user-plus me-1"></i> Tambah User Toko
        </a>
    </div>
</div>

<!-- Metrics Row -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="metric-card">
            <div class="metric-label">Total Omset Global</div>
            <div class="metric-val text-success">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            <small class="text-muted"><i class="fa-solid fa-receipt me-1"></i> Dari {{ $totalOrders }} transaksi seluruh toko</small>
            <div class="metric-icon"><i class="fa-solid fa-rupiah-sign"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="metric-card">
            <div class="metric-label">Cabang Aktif</div>
            <div class="metric-val text-info">{{ $activeStores }} <span class="fs-6 text-muted fw-normal">/ {{ $totalStores }} total</span></div>
            <small class="text-muted"><i class="fa-solid fa-store me-1"></i> Dapur dan outlet beroperasi</small>
            <div class="metric-icon"><i class="fa-solid fa-store"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="metric-card">
            <div class="metric-label">User Admin Toko</div>
            <div class="metric-val text-warning">{{ $totalStoreAdmins }} <span class="fs-6 text-muted fw-normal">pengelola</span></div>
            <small class="text-muted"><i class="fa-solid fa-users me-1"></i> Mengelola backend masing-masing</small>
            <div class="metric-icon"><i class="fa-solid fa-users-gear"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="metric-card">
            <div class="metric-label">Varian Menu Global</div>
            <div class="metric-val text-danger">{{ $totalMenus }}</div>
            <small class="text-muted"><i class="fa-solid fa-fire me-1"></i> {{ $totalCampaigns }} Campaign Open PO aktif</small>
            <div class="metric-icon"><i class="fa-solid fa-bowl-food"></i></div>
        </div>
    </div>
</div>

<!-- Section: Cabang & Direct Backend Access -->
<div class="card-dark mb-4">
    <div class="card-dark-header">
        <h5 class="card-dark-title"><i class="fa-solid fa-store text-danger me-2"></i> Akses Panel Dashboard & Backend Per Toko</h5>
        <a href="{{ route('superadmin.stores.index') }}" class="btn btn-sm btn-outline-light">
            <i class="fa-solid fa-list me-1"></i> Kelola Semua Cabang
        </a>
    </div>
    <div class="p-4">
        <div class="row g-4">
            @forelse($stores as $st)
                <div class="col-lg-6 col-xl-6">
                    <div class="card bg-dark border-secondary p-4 h-100 rounded-3 shadow-sm">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ $st->logo ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=80' }}" 
                                     alt="{{ $st->name }}" class="rounded-3 border border-secondary" style="width: 58px; height: 58px; object-fit: cover;">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h5 class="fw-bold text-white mb-0">{{ $st->name }}</h5>
                                        <span class="badge bg-danger">{{ $st->code }}</span>
                                    </div>
                                    <small class="text-muted font-mono">Slug URL: <span class="text-info">/{{ $st->slug ?: Str::slug($st->name) }}</span></small>
                                </div>
                            </div>
                            <span class="badge {{ $st->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ strtoupper($st->status) }}
                            </span>
                        </div>

                        <p class="text-muted small mb-3">
                            <i class="fa-solid fa-location-dot text-danger me-1"></i> {{ $st->address }}
                        </p>

                        <!-- Store Assigned Managers -->
                        <div class="mb-3 p-2 rounded bg-black bg-opacity-40 border border-secondary">
                            <small class="text-secondary d-block mb-1 fw-bold">
                                <i class="fa-solid fa-user-shield text-warning me-1"></i> Admin Yang Ditugaskan:
                            </small>
                            @if($st->users->count() > 0)
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($st->users as $admin)
                                        <span class="badge bg-secondary text-light">
                                            {{ $admin->name }} (@<span>{{ $admin->username }}</span>)
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-warning small fst-italic">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Belum ada user khusus cabang ini. Super Admin memegang akses penuh.
                                </span>
                            @endif
                        </div>

                        <!-- Stats pills -->
                        <div class="d-flex gap-3 mb-4 text-center">
                            <div class="flex-grow-1 p-2 rounded bg-secondary bg-opacity-20">
                                <div class="fw-bold text-white fs-6">{{ $st->menus_count }}</div>
                                <small class="text-muted" style="font-size: 0.72rem;">Menu Makanan</small>
                            </div>
                            <div class="flex-grow-1 p-2 rounded bg-secondary bg-opacity-20">
                                <div class="fw-bold text-white fs-6">{{ $st->campaigns_count }}</div>
                                <small class="text-muted" style="font-size: 0.72rem;">Open PO</small>
                            </div>
                            <div class="flex-grow-1 p-2 rounded bg-secondary bg-opacity-20">
                                <div class="fw-bold text-white fs-6">{{ $st->orders_count }}</div>
                                <small class="text-muted" style="font-size: 0.72rem;">Pesanan Masuk</small>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2">
                            <!-- Direct Backend Button -->
                            <a href="{{ url('/' . ($st->slug ?: $st->code) . '/backend') }}" 
                               class="btn btn-super flex-grow-1" target="_blank">
                                <i class="fa-solid fa-gauge-simple-high me-1"></i> Buka Backend Cabang
                            </a>
                            <!-- Direct Public Frontend Button -->
                            <a href="{{ url('/' . ($st->slug ?: $st->code)) }}" 
                               class="btn btn-outline-light flex-grow-1" target="_blank">
                                <i class="fa-solid fa-eye me-1"></i> Frontend Toko
                            </a>
                            <!-- Edit Button -->
                            <a href="{{ route('superadmin.stores.edit', $st->id) }}" class="btn btn-outline-secondary" title="Edit Cabang">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Belum ada cabang toko terdaftar.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Section: Recent Orders & Recent Users -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card-dark">
            <div class="card-dark-header">
                <h5 class="card-dark-title"><i class="fa-solid fa-receipt text-info me-2"></i> Transaksi Terkini Seluruh Cabang</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-dark-custom align-middle">
                    <thead>
                        <tr>
                            <th>No. Order</th>
                            <th>Cabang Toko</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $ord)
                            <tr>
                                <td>
                                    <span class="font-mono fw-bold text-info">{{ $ord->order_number }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $ord->store->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-white">{{ $ord->client->name ?? '-' }}</div>
                                    <small class="text-muted font-mono">{{ $ord->client->whatsapp ?? '-' }}</small>
                                </td>
                                <td class="font-mono fw-bold text-success">
                                    Rp {{ number_format($ord->total_amount, 0, ',', '.') }}
                                </td>
                                <td>
                                    <span class="badge bg-primary" style="font-size: 0.72rem;">{{ $ord->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada pesanan terbaru.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-dark">
            <div class="card-dark-header">
                <h5 class="card-dark-title"><i class="fa-solid fa-users text-warning me-2"></i> Admin Terdaftar</h5>
                <a href="{{ route('superadmin.users.index') }}" class="btn btn-sm btn-outline-light">Semua</a>
            </div>
            <div class="p-3">
                @foreach($recentUsers as $usr)
                    <div class="d-flex align-items-center justify-content-between p-2 rounded mb-2 border border-secondary border-opacity-40">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: #334155; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; font-size: 0.85rem;">
                                {{ strtoupper(substr($usr->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-bold text-white small">{{ $usr->name }}</div>
                                <small class="text-muted font-mono" style="font-size: 0.72rem;">@<span>{{ $usr->username }}</span></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge {{ $usr->isSuperAdmin() ? 'bg-danger' : 'bg-primary' }}" style="font-size: 0.68rem;">
                                {{ $usr->isSuperAdmin() ? 'Super Admin' : ($usr->store->code ?? 'Store Admin') }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
