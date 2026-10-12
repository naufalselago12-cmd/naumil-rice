@extends('layouts.admin')

@section('title', 'Campaign Open PO & QR')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="fw-bold mb-1">Campaign Open Pre-Order & QR Toko</h2>
        <p class="text-muted mb-0">Kelola periode Open PO, diskon per menu/campaign, tanggal close order, dan QR link dinamis.</p>
    </div>

    <div class="d-flex align-items-center gap-2">
        <!-- Store Selector Filter -->
        <form method="GET" action="{{ route('admin.campaigns.index') }}" class="d-flex align-items-center gap-2">
            <select name="store_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 200px;">
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ $selectedStoreId == $st->id ? 'selected' : '' }}>
                        {{ $st->name }}
                    </option>
                @endforeach
            </select>
        </form>

        <a href="{{ route('admin.campaigns.create', ['store_id' => $selectedStoreId]) }}" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Buat Campaign PO Baru
        </a>
    </div>
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
                        <span class="badge bg-secondary font-monospace me-1">{{ $camp->store->code }}</span>
                        <span class="fw-bold fs-6">{{ $camp->title }}</span>
                    </div>
                    @if($isExpired)
                        <span class="badge bg-danger">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i> EXPIRED (Close Order)
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
                            <div class="p-2 border rounded-3 bg-white shadow-sm mb-2 position-relative">
                                <img src="{{ $qrImg }}" alt="QR Code" class="img-fluid rounded" style="max-width: 140px;">
                                @if($isExpired)
                                    <div class="position-absolute top-50 start-50 translate-middle badge bg-danger fs-7 shadow">
                                        EXPIRED
                                    </div>
                                @endif
                            </div>
                            <small class="text-muted d-block font-monospace" style="font-size: 0.75rem;">{{ $camp->qr_token }}</small>
                            <a href="{{ route('admin.campaigns.show', $camp->id) }}" class="btn btn-xs btn-outline-primary mt-2 w-100">
                                <i class="fa-solid fa-qrcode me-1"></i> Cetak / QR Besar
                            </a>
                        </div>

                        <div class="col-sm-8">
                            <p class="text-muted small mb-2">{{ Str::limit($camp->description, 130) }}</p>

                            <div class="p-2 rounded bg-light border mb-2 small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><i class="fa-solid fa-hourglass-end text-danger me-1"></i> Close Order:</span>
                                    <span class="fw-bold">{{ $camp->close_order_at->format('d M Y, H:i') }} WIB</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><i class="fa-solid fa-calendar-check text-success me-1"></i> Ready Pickup/Kirim:</span>
                                    <span class="fw-bold">{{ $camp->ready_at->format('d M Y, H:i') }} WIB</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted"><i class="fa-solid fa-tags text-warning me-1"></i> Diskon Promo:</span>
                                    <span class="fw-bold text-success">
                                        @if($camp->discount_type === 'min_purchase')
                                            Potongan Rp {{ number_format($camp->discount_value, 0, ',', '.') }} (Min. Rp {{ number_format($camp->min_purchase_amount, 0, ',', '.') }})
                                        @elseif($camp->discount_type === 'fixed')
                                            Potongan Rp {{ number_format($camp->discount_value, 0, ',', '.') }}
                                        @elseif($camp->discount_type === 'percentage')
                                            Diskon {{ $camp->discount_value }}%
                                        @else
                                            Normal / Diskon Menu
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between text-muted small">
                                <span><i class="fa-solid fa-bowl-food me-1 text-primary"></i> <strong>{{ $camp->menus->count() }}</strong> Menu PO</span>
                                <span><i class="fa-solid fa-bag-shopping me-1 text-success"></i> <strong>{{ $camp->orders_count }}</strong> Pesanan Masuk</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-transparent border-top p-3 d-flex justify-content-between align-items-center">
                    <a href="{{ $frontendUrl }}" target="_blank" class="btn btn-sm btn-outline-info">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka Link PO Pelanggan
                    </a>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.campaigns.edit', $camp->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <form action="{{ route('admin.campaigns.destroy', $camp->id) }}" method="POST" onsubmit="return confirm('Hapus campaign Open PO ini?');">
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
        <div class="col-12">
            <div class="card-custom p-5 text-center text-muted">
                <i class="fa-solid fa-bullhorn fa-3x mb-3 text-secondary"></i>
                <h5>Belum ada campaign Open Pre-Order</h5>
                <p>Mulai buka pre-order makanan dengan menekan tombol "Buat Campaign PO Baru".</p>
            </div>
        </div>
    @endforelse
</div>
@endsection
