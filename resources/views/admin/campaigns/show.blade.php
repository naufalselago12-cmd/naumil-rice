@extends('layouts.admin')

@section('title', 'Detail QR Campaign - ' . $campaign->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Detail QR Code Open Pre-Order</h2>
        <p class="text-muted mb-0">{{ $campaign->store->name }} &bull; Token: <span class="font-monospace text-primary fw-bold">{{ $campaign->qr_token }}</span></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('admin.campaigns.print', $campaign->id) }}" target="_blank" class="btn btn-dark shadow-sm">
            <i class="fa-solid fa-print me-1"></i> Cetak Bill Rekap PO
        </a>
        <a href="{{ route('admin.campaigns.print', ['campaign' => $campaign->id, 'mode' => 'all_orders']) }}" target="_blank" class="btn btn-outline-dark shadow-sm">
            <i class="fa-solid fa-receipt me-1"></i> Cetak Semua Struk Order
        </a>
        <a href="{{ route('admin.campaigns.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
        </a>
        <button onclick="window.print()" class="btn btn-outline-primary">
            <i class="fa-solid fa-qrcode me-1"></i> Cetak QR Banner
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- QR Standee Card -->
    <div class="col-lg-5">
        <div class="card-custom text-center p-4">
            <div class="mb-3">
                <span class="badge bg-secondary font-monospace">{{ $campaign->store->code }}</span>
                <h4 class="fw-bold mt-2 mb-1">{{ $campaign->title }}</h4>
                <p class="text-muted small mb-0">{{ $campaign->store->name }}</p>
            </div>

            <!-- Big High-Res QR Code Frame -->
            <div class="p-3 border rounded-4 bg-white shadow-sm d-inline-block position-relative my-2">
                <img src="{{ $qrCodeApiUrl }}" alt="QR Code Open PO" class="img-fluid rounded" style="width: 280px; height: 280px;">
                @if($campaign->is_expired)
                    <div class="position-absolute top-50 start-50 translate-middle badge bg-danger fs-6 p-3 shadow-lg">
                        <i class="fa-solid fa-ban me-1"></i> QR CODE EXPIRED<br>
                        <small style="font-weight: normal; font-size: 0.75rem;">Close Order Berakhir</small>
                    </div>
                @endif
            </div>

            <!-- Expiry Status Banner -->
            <div class="mt-3">
                @if($campaign->is_expired)
                    <div class="alert alert-danger py-2 mb-3">
                        <i class="fa-solid fa-circle-exclamation me-1"></i> <strong>Periode Pre-Order Ditutup</strong> (Expired)
                    </div>
                @else
                    <div class="alert alert-success py-2 mb-3">
                        <i class="fa-solid fa-circle-check me-1"></i> <strong>QR Code Aktif & Siap Discan Pelanggan</strong>
                    </div>
                @endif
            </div>

            <!-- Link URL Input & Copy -->
            <label class="form-label small text-muted fw-bold d-block text-start mb-1">Link Langsung Menuju Frontend:</label>
            <div class="input-group mb-3">
                <input type="text" id="frontendLinkInput" class="form-control font-monospace small" value="{{ $frontendCampaignUrl }}" readonly>
                <button class="btn btn-outline-primary" type="button" onclick="copyFrontendLink()">
                    <i class="fa-solid fa-copy me-1"></i> Salin
                </button>
                <a href="{{ $frontendCampaignUrl }}" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
            </div>

            <small class="text-muted d-block">
                Saat pelanggan scan QR ini dengan kamera HP / WA, sistem otomatis mengarahkan ke halaman frontend Pre-Order.
            </small>
        </div>
    </div>

    <!-- Campaign Details & Menu List -->
    <div class="col-lg-7">
        <div class="card-custom mb-4">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-circle-info me-2 text-info"></i> Parameter Periode PO</h5>
                <a href="{{ route('admin.campaigns.edit', $campaign->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                </a>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 rounded bg-light border">
                            <span class="text-muted small d-block mb-1"><i class="fa-solid fa-hourglass-end text-danger me-1"></i> Batas Close Order:</span>
                            <div class="fw-bold fs-6">{{ $campaign->close_order_at->format('d M Y, H:i') }} WIB</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded bg-light border">
                            <span class="text-muted small d-block mb-1"><i class="fa-solid fa-calendar-check text-success me-1"></i> Estimasi Makanan Ready:</span>
                            <div class="fw-bold fs-6">{{ $campaign->ready_at->format('d M Y, H:i') }} WIB</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded bg-light border">
                            <span class="text-muted small d-block mb-1"><i class="fa-solid fa-boxes-stacked text-primary me-1"></i> Kuota / Maks. Stok Batch:</span>
                            <div class="fw-bold fs-6 text-primary">
                                {{ $campaign->max_quota ? $campaign->max_quota . ' Porsi' : 'Tak Terbatas' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 rounded bg-light border">
                            <span class="text-muted small d-block mb-1"><i class="fa-solid fa-chart-pie text-success me-1"></i> Terpesan vs Sisa:</span>
                            <div class="fw-bold fs-6 font-mono">
                                {{ $campaign->total_ordered }} Porsi Terpesan
                                @if($campaign->max_quota)
                                    <span class="badge {{ $campaign->remaining_quota > 0 ? 'bg-success' : 'bg-danger' }} ms-1">
                                        Sisa: {{ $campaign->remaining_quota }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 rounded bg-light border">
                            <span class="text-muted small d-block mb-1"><i class="fa-solid fa-tags text-warning me-1"></i> Ketentuan Diskon:</span>
                            <div class="fw-bold">
                                @if($campaign->discount_type === 'min_qty')
                                    Diskon Promo Min. {{ $campaign->min_order_qty ?? 15 }} Porsi All Menu (Diskon {{ $campaign->discount_value }}% all menu atau sesuai diskon setting per menu)
                                @elseif($campaign->discount_type === 'fixed')
                                    Potongan Rp {{ number_format($campaign->discount_value, 0, ',', '.') }} per transaksi
                                @elseif($campaign->discount_type === 'percentage')
                                    Diskon {{ $campaign->discount_value }}% all menu
                                @else
                                    Harga normal / diskon khusus per masing-masing menu
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menus in this Campaign -->
        <div class="card-custom mb-4">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-bowl-food me-2 text-primary"></i> Menu yang Diikutsertakan ({{ $campaign->menus->count() }})</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Menu</th>
                            <th>Harga Standard</th>
                            <th>Diskon Menu</th>
                            <th>Harga PO</th>
                            <th>Maks. Kuota</th>
                            <th>Terpesan</th>
                            <th>Sisa</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($campaign->menus as $menu)
                            @php
                                $pivot = $menu->pivot;
                                $base = $pivot->custom_price ?? $menu->price;
                                $disc = $pivot->discount_price ?? 0;
                                $final = max(0, $base - $disc);
                                $ordered = $campaign->getMenuOrderedQuantity($menu->id);
                                $remaining = $campaign->getMenuRemainingQuota($menu->id, $pivot->max_quota);
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $menu->image }}" alt="" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <div class="fw-bold">{{ $menu->name }}</div>
                                            <small class="text-muted">{{ $menu->category?->name ?: 'Umum' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>Rp {{ number_format($menu->price, 0, ',', '.') }}</td>
                                <td>
                                    @if($disc > 0)
                                        <span class="badge bg-danger">- Rp {{ number_format($disc, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="fw-bold text-success fs-6 font-mono">Rp {{ number_format($final, 0, ',', '.') }}</td>
                                <td>{{ $pivot->max_quota ? $pivot->max_quota . ' Porsi' : 'Tak Terbatas' }}</td>
                                <td class="font-mono">{{ $ordered }} Porsi</td>
                                <td>
                                    @if($pivot->max_quota !== null)
                                        <span class="badge {{ $remaining > 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ $remaining > 0 ? 'Sisa ' . $remaining : 'Habis' }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Orders in this Campaign -->
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-receipt me-2 text-info"></i> Pesanan Masuk ({{ $campaign->orders->count() }})</h5>
            </div>
            <div class="p-3">
                @if($campaign->orders->isEmpty())
                    <p class="text-muted mb-0 text-center py-2">Belum ada pesanan masuk untuk campaign ini.</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($campaign->orders->take(5) as $ord)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <span class="fw-bold font-monospace text-primary">{{ $ord->order_number }}</span>
                                    <span class="text-muted small ms-2">&bull; {{ $ord->client->name }} ({{ $ord->client->whatsapp }})</span>
                                </div>
                                <div>
                                    <span class="badge bg-primary me-2">{{ $ord->status }}</span>
                                    <span class="fw-bold">Rp {{ number_format($ord->total_amount, 0, ',', '.') }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function copyFrontendLink() {
        const input = document.getElementById('frontendLinkInput');
        input.select();
        navigator.clipboard.writeText(input.value);
        alert('Link QR Campaign berhasil disalin: ' + input.value);
    }
</script>
@endpush
