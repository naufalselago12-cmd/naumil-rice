@extends('store_backend.layouts.app')

@section('title', 'Campaign Open PO - ' . $store->name)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1 text-dark">Campaign Open Pre-Order & QR Toko</h2>
        <p class="text-muted mb-0">Kelola periode batch Open PO, tanggal close order, dan QR order khusus cabang {{ $store->name }}.</p>
    </div>

    <a href="{{ route('store.backend.campaigns.create', $store->slug) }}" class="btn btn-primary">
        <i class="fa-solid fa-plus me-1"></i> Buat Campaign PO Baru
    </a>
</div>

<div class="row g-4">
    @forelse($campaigns as $camp)
        @php
            $isExpired = $camp->is_expired;
            $frontendUrl = url('/campaign/' . $camp->qr_token);
            $qrImg = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($frontendUrl);
        @endphp
        <div class="col-lg-6">
            <div class="card-custom h-100 d-flex flex-column">
                <div class="card-custom-header">
                    <div>
                        <span class="badge bg-secondary font-monospace me-1">{{ $store->code }}</span>
                        <strong class="fs-6 text-dark">{{ $camp->title }}</strong>
                    </div>
                    @if($isExpired)
                        <span class="badge bg-danger">
                            <i class="fa-solid fa-ban me-1"></i> EXPIRED (Close Order)
                        </span>
                    @else
                        <span class="badge bg-success">
                            <i class="fa-solid fa-circle-check me-1"></i> OPEN PRE-ORDER
                        </span>
                    @endif
                </div>

                <div class="p-4 flex-grow-1">
                    <div class="row g-3">
                        <div class="col-sm-4 text-center">
                            <!-- QR Code Box -->
                            <div class="p-2 border rounded-3 bg-white shadow-sm mb-2">
                                <img src="{{ $qrImg }}" alt="QR Code" class="img-fluid rounded" style="max-width: 130px;">
                            </div>
                            <a href="{{ $qrImg }}" download="QR-{{ $camp->slug }}.png" target="_blank" class="btn btn-xs btn-outline-secondary w-100 mb-1" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-download me-1"></i> Unduh QR
                            </a>
                            <a href="{{ $frontendUrl }}" target="_blank" class="btn btn-xs btn-outline-primary w-100" style="font-size: 0.75rem;">
                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka PO
                            </a>
                        </div>

                        <div class="col-sm-8">
                            <p class="text-muted small mb-2">{{ $camp->description ?: 'Pre-order batch makanan otentik.' }}</p>

                            <ul class="list-unstyled small mb-3">
                                <li class="mb-1 text-danger">
                                    <i class="fa-solid fa-hourglass-end me-1"></i> <strong>Close Order:</strong> {{ $camp->close_order_at->format('d M Y, H:i') }} WIB
                                </li>
                                <li class="mb-1 text-success">
                                    <i class="fa-solid fa-kitchen-set me-1"></i> <strong>Ready / Kirim:</strong> {{ $camp->ready_at->format('d M Y, H:i') }} WIB
                                </li>
                                <li class="mb-1">
                                    <i class="fa-solid fa-tag me-1 text-warning"></i> <strong>Promo:</strong> 
                                    @if($camp->discount_type === 'min_qty')
                                        Diskon Min. {{ $camp->min_order_qty ?? 15 }} Porsi ({{ $camp->discount_value }}% / Diskon Menu)
                                    @elseif($camp->discount_type === 'percentage')
                                        Diskon {{ $camp->discount_value }}%
                                    @elseif($camp->discount_type === 'fixed')
                                        Potongan Rp {{ number_format($camp->discount_value, 0, ',', '.') }}
                                    @else
                                        Harga Normal / Diskon Menu
                                    @endif
                                </li>
                                <li>
                                    <i class="fa-solid fa-receipt me-1 text-info"></i> <strong>Pesanan Masuk:</strong> {{ $camp->orders_count }} Pesanan
                                </li>
                            </ul>

                            <div class="border-top pt-2">
                                <small class="text-muted d-block mb-1 fw-bold">Menu Dalam Batch Ini:</small>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($camp->menus as $m)
                                        <span class="badge bg-light text-dark border">{{ $m->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                    <span class="small text-muted font-monospace">{{ $camp->qr_token }}</span>
                    <div class="d-flex gap-1">
                        <a href="{{ route('store.backend.campaigns.show', ['store_slug' => $store->slug, 'campaign' => $camp->id]) }}" class="btn btn-sm btn-outline-info">
                            <i class="fa-solid fa-eye me-1"></i> Detail
                        </a>
                        <a href="{{ route('store.backend.campaigns.edit', ['store_slug' => $store->slug, 'campaign' => $camp->id]) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                        </a>
                        <form action="{{ route('store.backend.campaigns.destroy', ['store_slug' => $store->slug, 'campaign' => $camp->id]) }}" method="POST" onsubmit="return confirm('Hapus campaign {{ $camp->title }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center py-5">
            <i class="fa-solid fa-calendar-xmark fa-3x text-muted mb-3"></i>
            <h5>Belum ada batch Open PO untuk cabang {{ $store->name }}</h5>
            <p class="text-muted">Klik tombol di atas untuk membuat campaign dan kode QR pre-order baru.</p>
        </div>
    @endforelse
</div>
@endsection
