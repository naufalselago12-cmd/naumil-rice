@extends('store_backend.layouts.app')

@section('title', 'Detail Campaign - ' . $campaign->title)

@section('content')
@php
    $frontendUrl = url('/campaign/' . $campaign->qr_token);
    $qrImg = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($frontendUrl);
@endphp

<div class="mb-4">
    <a href="{{ route('store.backend.campaigns.index', $store->slug) }}" class="text-muted text-decoration-none small">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar Campaign
    </a>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <h2 class="fw-bold mb-0 text-dark">{{ $campaign->title }}</h2>
        <div class="d-flex gap-2">
            <a href="{{ $frontendUrl }}" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka Halaman Pesan Pembeli
            </a>
            <a href="{{ route('store.backend.campaigns.edit', ['store_slug' => $store->slug, 'campaign' => $campaign->id]) }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-pen-to-square me-1"></i> Edit Campaign
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <!-- QR Display Card -->
        <div class="card-custom text-center p-4">
            <h5 class="fw-bold mb-3">QR Code Pre-Order</h5>
            <div class="p-3 bg-white border rounded-3 d-inline-block shadow-sm mb-3">
                <img src="{{ $qrImg }}" alt="QR Code" class="img-fluid" style="max-width: 220px;">
            </div>
            <div class="mb-3">
                <small class="text-muted d-block">Token QR Khusus Toko:</small>
                <code class="fs-6 font-mono text-dark fw-bold">{{ $campaign->qr_token }}</code>
            </div>
            <div class="d-grid gap-2">
                <a href="{{ $qrImg }}" download="QR-{{ $campaign->slug }}.png" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-download me-1"></i> Download File QR Cetak
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Campaign Details -->
        <div class="card-custom mb-4">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-circle-info text-primary me-2"></i> Parameter Periode & Ketentuan</h5>
                <div class="d-flex gap-2 align-items-center">
                    @if($campaign->max_quota && $campaign->is_sold_out)
                        <span class="badge bg-danger">SOLD OUT (KUOTA HABIS)</span>
                    @endif
                    <span class="badge {{ $campaign->is_expired ? 'bg-danger' : 'bg-success' }}">
                        {{ $campaign->is_expired ? 'EXPIRED' : 'AKTIF BERJALAN' }}
                    </span>
                </div>
            </div>
            <div class="p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="text-muted small d-block">Batas Waktu Close Order</label>
                        <strong class="text-danger fs-6">{{ $campaign->close_order_at->format('d F Y, H:i') }} WIB</strong>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small d-block">Jadwal Makanan Siap / Dikirim</label>
                        <strong class="text-success fs-6">{{ $campaign->ready_at->format('d F Y, H:i') }} WIB</strong>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small d-block">Maks. Kuota Campaign</label>
                        <strong class="text-primary fs-6">{{ $campaign->max_quota ? $campaign->max_quota . ' Porsi' : 'Tak Terbatas' }}</strong>
                    </div>
                    <div class="col-md-3">
                        <label class="text-muted small d-block">Status Kuota Batch</label>
                        <strong class="text-dark fs-6 font-mono">
                            Terpesan: {{ $campaign->total_ordered }} {{ $campaign->max_quota ? '/ ' . $campaign->max_quota : '' }}
                            @if($campaign->max_quota)
                                <span class="badge {{ $campaign->remaining_quota > 0 ? 'bg-success' : 'bg-danger' }} ms-1">
                                    Sisa: {{ $campaign->remaining_quota }}
                                </span>
                            @endif
                        </strong>
                    </div>
                </div>

                <p class="text-muted">{{ $campaign->description }}</p>

                <h6 class="fw-bold mt-4 mb-2"><i class="fa-solid fa-utensils text-primary me-1"></i> Menu Yang Terbuka Pada Batch Ini ({{ $campaign->menus->count() }}):</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Menu Makanan</th>
                                <th>Harga Standard</th>
                                <th>Harga PO</th>
                                <th>Maks. Kuota Menu</th>
                                <th>Terpesan</th>
                                <th>Sisa Kuota</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaign->menus as $m)
                                @php
                                    $pivot = $m->pivot;
                                    $base = $pivot->custom_price ?? $m->price;
                                    $disc = $pivot->discount_price ?? 0;
                                    $final = max(0, $base - $disc);
                                    $ordered = $campaign->getMenuOrderedQuantity($m->id);
                                    $remaining = $campaign->getMenuRemainingQuota($m->id, $pivot->max_quota);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $m->image }}" alt="" class="rounded border" style="width: 36px; height: 36px; object-fit: cover;">
                                            <strong class="text-dark">{{ $m->name }}</strong>
                                        </div>
                                    </td>
                                    <td class="font-mono text-muted">Rp {{ number_format($m->price, 0, ',', '.') }}</td>
                                    <td class="font-mono fw-bold text-success">Rp {{ number_format($final, 0, ',', '.') }}</td>
                                    <td>{{ $pivot->max_quota ? $pivot->max_quota . ' Porsi' : 'Tak Terbatas' }}</td>
                                    <td class="font-mono">{{ $ordered }} Porsi</td>
                                    <td>
                                        @if($pivot->max_quota !== null)
                                            <span class="badge {{ $remaining > 0 ? 'bg-success' : 'bg-danger' }}">
                                                {{ $remaining > 0 ? 'Sisa ' . $remaining . ' Porsi' : 'Habis' }}
                                            </span>
                                        @else
                                            <span class="text-muted">Mengikuti kuota batch</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
