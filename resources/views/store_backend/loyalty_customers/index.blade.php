@extends('store_backend.layouts.app')

@section('title', 'Data Pelanggan Setia - ' . $store->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-crown text-warning me-2"></i> Data Pelanggan Setia Cabang {{ $store->name }}</h4>
        <p class="text-muted small mb-0">Kelola data keanggotaan pelanggan setia, perolehan credit point, dan riwayat pesanan.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('store.backend.loyaltyPromos.index', $store->slug) }}" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-tags me-1"></i> Promo Pelanggan Setia
        </a>
    </div>
</div>

<div class="card-custom">
    <div class="card-custom-header">
        <form method="GET" action="{{ route('store.backend.loyaltyCustomers.index', $store->slug) }}" class="d-flex gap-2 align-items-center" style="max-width: 400px; width: 100%;">
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="Cari nama, email, WA, pekerjaan..." value="{{ $search ?? '' }}">
                <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                @if(!empty($search))
                    <a href="{{ route('store.backend.loyaltyCustomers.index', $store->slug) }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        <span class="badge bg-primary fs-6">{{ $customers->total() }} Pelanggan Terdaftar</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Pelanggan</th>
                    <th>Gender & Pekerjaan</th>
                    <th>Kontak (WA & Email)</th>
                    <th>Credit Point Cabang</th>
                    <th>Total Poin Global</th>
                    <th>Total Pesanan</th>
                    <th>Status Akun</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                    @php
                        $storePivot = $c->stores->first();
                        $storePts = $storePivot ? $storePivot->pivot->points : 0;
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 40px; height: 40px;">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div>
                                    <div class="fw-bold fs-6">{{ $c->name }}</div>
                                    <small class="text-muted">Sejak: {{ $c->created_at ? $c->created_at->format('d M Y') : '-' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <span class="badge {{ $c->gender === 'Laki-laki' ? 'bg-primary' : 'bg-danger' }} rounded-pill" style="font-size: 0.72rem;">
                                    {{ $c->gender ?: '-' }}
                                </span>
                            </div>
                            <small class="text-muted d-block mt-1">
                                <i class="fa-solid fa-briefcase text-secondary me-1"></i> {{ $c->occupation ?: '-' }}
                            </small>
                        </td>
                        <td>
                            <div>
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $c->whatsapp) }}" target="_blank" class="text-decoration-none text-success fw-semibold">
                                    <i class="fa-brands fa-whatsapp me-1"></i> {{ $c->whatsapp }}
                                </a>
                            </div>
                            <small class="text-muted d-block">
                                <i class="fa-regular fa-envelope me-1"></i> {{ $c->email }}
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-warning text-dark font-mono fs-6 px-2 py-1 shadow-sm">
                                <i class="fa-solid fa-coins me-1"></i> {{ number_format($storePts, 0, ',', '.') }} Pts
                            </span>
                        </td>
                        <td>
                            <strong class="font-mono text-primary">{{ number_format($c->loyalty_points, 0, ',', '.') }} Pts</strong>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $c->orders_count }} Transaksi</span>
                        </td>
                        <td>
                            @if($c->is_active && $c->email_verified_at)
                                <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Terverifikasi</span>
                            @else
                                <span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i> Menunggu Verifikasi</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('store.backend.loyaltyCustomers.show', ['store_slug' => $store->slug, 'customer' => $c->id]) }}" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-eye me-1"></i> Detail & Poin
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-users fa-3x mb-3 text-secondary opacity-50"></i>
                            <div class="fw-bold">Belum Ada Pelanggan Setia Terdaftar</div>
                            <small>Pelanggan yang mendaftar keanggotaan setia untuk cabang ini akan otomatis muncul di sini.</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($customers->hasPages())
        <div class="p-3 border-top">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection
