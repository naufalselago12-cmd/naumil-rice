@extends('store_backend.layouts.app')

@section('title', 'Pesanan Cabang - ' . $store->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1 text-dark">Pesanan Masuk: {{ $store->name }}</h2>
        <p class="text-muted mb-0">Kelola dan proses status tahapan memasak, packaging, hingga pengiriman.</p>
    </div>

    <a href="{{ route('store.backend.kds', $store->slug) }}" class="btn btn-danger">
        <i class="fa-solid fa-desktop me-1"></i> Buka Layar KDS Dapur
    </a>
</div>

<!-- Filters -->
<div class="card p-3 border mb-4 bg-white rounded-3 shadow-sm">
    <form method="GET" action="{{ route('store.backend.orders.index', $store->slug) }}" class="row g-2 align-items-center">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Cari No. Order, Nama, atau No. WA..." 
                   value="{{ $search }}">
        </div>

        <div class="col-md-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Status Pesanan</option>
                <option value="Order Konfirmed" {{ $status === 'Order Konfirmed' ? 'selected' : '' }}>Order Konfirmed</option>
                <option value="Order On Proses" {{ $status === 'Order On Proses' ? 'selected' : '' }}>Order On Proses (Dapur)</option>
                <option value="Order On Packaging" {{ $status === 'Order On Packaging' ? 'selected' : '' }}>Order On Packaging</option>
                <option value="Order On Delivery" {{ $status === 'Order On Delivery' ? 'selected' : '' }}>Order On Delivery</option>
                <option value="Order Ready" {{ $status === 'Order Ready' ? 'selected' : '' }}>Order Ready (Selesai)</option>
                <option value="Order Reject" {{ $status === 'Order Reject' ? 'selected' : '' }}>Order Reject (Batal)</option>
            </select>
        </div>

        <div class="col-auto">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i> Cari</button>
            @if($status || $search)
                <a href="{{ route('store.backend.orders.index', $store->slug) }}" class="btn btn-outline-secondary ms-1">Reset</a>
            @endif
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="card-custom">
    <div class="card-custom-header">
        <h5 class="card-custom-title"><i class="fa-solid fa-receipt text-primary me-2"></i> Daftar Pesanan (Total {{ $orders->total() }})</h5>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Order & Waktu</th>
                    <th>Pelanggan</th>
                    <th>Items & Porsi</th>
                    <th>Total Belanja</th>
                    <th>Pembayaran</th>
                    <th>Status Pesanan</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $ord)
                    @php
                        $statusBadgeClass = match($ord->status) {
                            'Order Konfirmed' => 'bg-info text-dark',
                            'Order On Proses' => 'bg-warning text-dark',
                            'Order On Packaging' => 'bg-secondary',
                            'Order On Delivery' => 'bg-primary',
                            'Order Ready' => 'bg-success',
                            'Order Reject' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('store.backend.orders.show', ['store_slug' => $store->slug, 'order' => $ord->id]) }}" 
                               class="fw-bold font-mono text-primary text-decoration-none fs-6">
                                {{ $ord->order_number }}
                            </a>
                            <small class="text-muted d-block">{{ $ord->created_at->format('d M Y, H:i') }} WIB</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $ord->client->name ?? '-' }}</div>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $ord->client->whatsapp ?? '') }}" 
                               target="_blank" class="small text-success text-decoration-none font-mono">
                                <i class="fa-brands fa-whatsapp"></i> {{ $ord->client->whatsapp ?? '-' }}
                            </a>
                        </td>
                        <td>
                            <small class="text-truncate d-inline-block" style="max-width: 250px;">
                                @foreach($ord->items as $it)
                                    <strong>{{ $it->quantity }}x</strong> {{ $it->menu_name }}{{ !$loop->last ? '; ' : '' }}
                                @endforeach
                            </small>
                        </td>
                        <td class="font-mono fw-bold text-success">
                            Rp {{ number_format($ord->total_amount, 0, ',', '.') }}
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $ord->payment_method }}</span>
                            @if($ord->payment_status === 'paid')
                                <span class="badge bg-success d-block mt-1">PAID</span>
                            @elseif($ord->payment_status === 'payment_on_review')
                                <span class="badge bg-info text-white d-block mt-1"><i class="fa-solid fa-hourglass-half me-1"></i> ON REVIEW</span>
                            @else
                                <span class="badge bg-warning text-dark d-block mt-1">UNPAID</span>
                            @endif
                            @if($ord->payment_proof)
                                <a href="{{ $ord->payment_proof_url }}" target="_blank" class="badge bg-secondary text-white text-decoration-none d-block mt-1">
                                    <i class="fa-solid fa-image me-1"></i> Bukti QRIS
                                </a>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $statusBadgeClass }}">{{ $ord->status }}</span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('store.backend.orders.print', ['store_slug' => $store->slug, 'order' => $ord->id]) }}" 
                               target="_blank" class="btn btn-sm btn-outline-dark me-1" title="Cetak Struk POS">
                                <i class="fa-solid fa-print"></i>
                            </a>
                            <a href="{{ route('store.backend.orders.show', ['store_slug' => $store->slug, 'order' => $ord->id]) }}" 
                               class="btn btn-sm btn-primary">
                                Kelola
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Tidak ada pesanan yang sesuai filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
        <div class="p-3 border-top">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection
