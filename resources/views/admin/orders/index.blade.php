@extends('layouts.admin')

@section('title', 'Daftar Pesanan Pre-Order')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1">Daftar Pesanan & Status Pre-Order</h2>
        <p class="text-muted mb-0">Pantau seluruh pesanan masuk dari scan QR maupun web, periksa pembayaran dan proses dapur.</p>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('admin.orders.index') }}" class="d-flex flex-wrap align-items-center gap-2">
        <select name="store_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 170px;">
            <option value="">Semua Cabang</option>
            @foreach($stores as $st)
                <option value="{{ $st->id }}" {{ $selectedStoreId == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
            @endforeach
        </select>

        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 170px;">
            <option value="">Semua Status</option>
            @foreach(\App\Models\Order::ALL_STATUSES as $stt)
                <option value="{{ $stt }}" {{ $status === $stt ? 'selected' : '' }}>{{ $stt }}</option>
            @endforeach
        </select>

        <div class="input-group input-group-sm" style="max-width: 200px;">
            <input type="text" name="search" class="form-control" placeholder="Cari No. Order/Nama..." value="{{ $search }}">
            <button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-search"></i></button>
        </div>
    </form>
</div>

<div class="card-custom">
    <div class="card-custom-header">
        <h5 class="card-custom-title"><i class="fa-solid fa-receipt me-2 text-primary"></i> Data Pesanan</h5>
        <a href="{{ route('admin.kds') }}" class="btn btn-sm btn-outline-danger">
            <i class="fa-solid fa-tv me-1"></i> Buka Kitchen Display (KDS)
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Order</th>
                    <th>Waktu Masuk</th>
                    <th>Pelanggan</th>
                    <th>Cabang</th>
                    <th>Metode Bayar</th>
                    <th>Total Belanja</th>
                    <th>Status Pesanan</th>
                    <th style="width: 100px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $ord)
                    @php
                        $badgeClass = match($ord->status) {
                            'Order Konfirmed' => 'bg-secondary',
                            'Order On Proses' => 'bg-primary',
                            'Order On Packaging' => 'bg-warning text-dark',
                            'Order On Delivery' => 'bg-info text-dark',
                            'Order Ready' => 'bg-success',
                            'Order Reject' => 'bg-danger',
                            default => 'bg-light text-dark'
                        };
                    @endphp
                    <tr>
                        <td class="fw-bold font-monospace text-primary">
                            <a href="{{ route('admin.orders.show', $ord->id) }}" class="text-decoration-none">
                                {{ $ord->order_number }}
                            </a>
                        </td>
                        <td class="text-muted small">
                            {{ $ord->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td>
                            <div class="fw-bold">{{ $ord->client->name }}</div>
                            <small class="text-muted"><i class="fa-brands fa-whatsapp text-success me-1"></i> {{ $ord->client->whatsapp }}</small>
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $ord->store->code }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $ord->payment_method === 'QRIS Dinamis' ? 'bg-info text-dark' : 'bg-warning text-dark' }}">
                                {{ $ord->payment_method }}
                            </span>
                        </td>
                        <td class="fw-bold">
                            Rp {{ number_format($ord->total_amount, 0, ',', '.') }}
                        </td>
                        <td>
                            <span class="badge {{ $badgeClass }} p-2">
                                {{ $ord->status }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.orders.show', $ord->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fa-solid fa-eye me-1"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">Belum ada data pesanan yang sesuai filter.</td>
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
