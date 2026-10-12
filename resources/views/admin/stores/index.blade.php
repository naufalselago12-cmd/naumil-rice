@extends('layouts.admin')

@section('title', 'Daftar Cabang / Toko')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Manajemen Cabang & Toko</h2>
        <p class="text-muted mb-0">Kelola informasi cabang, logo, alamat, dan setting QRIS Dinamis per toko.</p>
    </div>
    <a href="{{ route('admin.stores.create') }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i> Tambah Cabang Baru
    </a>
</div>

<div class="row g-4">
    @forelse($stores as $store)
        <div class="col-md-6 col-lg-4">
            <div class="card-custom h-100 d-flex flex-column">
                <div class="card-custom-header">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary font-monospace">{{ $store->code }}</span>
                        <h6 class="mb-0 fw-bold">{{ $store->name }}</h6>
                    </div>
                    <span class="badge {{ $store->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                        {{ ucfirst($store->status) }}
                    </span>
                </div>
                
                <div class="p-3 flex-grow-1">
                    <div class="d-flex gap-3 mb-3">
                        <img src="{{ $store->logo ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=120&q=80' }}" 
                             alt="{{ $store->name }}" 
                             class="rounded-3 border object-fit-cover" 
                             style="width: 72px; height: 72px;">
                        <div>
                            <div class="text-muted small mb-1"><i class="fa-solid fa-location-dot me-1 text-danger"></i> {{ $store->address }}</div>
                            <div class="text-muted small"><i class="fa-solid fa-phone me-1 text-success"></i> {{ $store->phone ?: '-' }}</div>
                        </div>
                    </div>

                    <!-- Dynamic QRIS Info -->
                    <div class="p-2 rounded bg-light border mb-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="fw-semibold small"><i class="fa-solid fa-qrcode me-1 text-primary"></i> QRIS Dinamis Toko</span>
                            @if($store->qris_image)
                                <a href="{{ $store->qris_image }}" target="_blank" class="badge bg-primary text-decoration-none">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i> Lihat QR
                                </a>
                            @else
                                <span class="badge bg-warning text-dark">Belum Disetting</span>
                            @endif
                        </div>
                    </div>

                    <!-- Statistics count -->
                    <div class="row g-2 text-center text-muted small">
                        <div class="col-4 border-end">
                            <span class="d-block fw-bold text-dark fs-6">{{ $store->menus_count }}</span> Menu
                        </div>
                        <div class="col-4 border-end">
                            <span class="d-block fw-bold text-dark fs-6">{{ $store->campaigns_count }}</span> Campaign PO
                        </div>
                        <div class="col-4">
                            <span class="d-block fw-bold text-dark fs-6">{{ $store->orders_count }}</span> Orders
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-transparent border-top p-3 d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.stores.edit', $store->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit & QRIS
                    </a>
                    <form action="{{ route('admin.stores.destroy', $store->id) }}" method="POST" onsubmit="return confirm('Hapus cabang ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card-custom p-5 text-center text-muted">
                <i class="fa-solid fa-store fa-3x mb-3 text-secondary"></i>
                <h5>Belum ada cabang terdaftar</h5>
                <p>Klik tombol tambah cabang di atas untuk mulai menambahkan cabang.</p>
            </div>
        </div>
    @endforelse
</div>
@endsection
