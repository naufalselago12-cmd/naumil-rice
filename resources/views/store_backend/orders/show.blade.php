@extends('store_backend.layouts.app')

@section('title', 'Detail Pesanan ' . $order->order_number)

@section('content')
<div class="mb-4">
    <a href="{{ route('store.backend.orders.index', $store->slug) }}" class="text-muted text-decoration-none small">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Daftar Pesanan
    </a>
    <div class="d-flex justify-content-between align-items-center mt-2">
        <div>
            <h2 class="fw-bold mb-0 font-mono text-dark">{{ $order->order_number }}</h2>
            <small class="text-muted">Dipesan pada {{ $order->created_at->format('d F Y, H:i:s') }} WIB</small>
        </div>
        <div>
            <span class="badge bg-primary fs-6">{{ $order->status }}</span>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Order Items & Customer -->
    <div class="col-lg-8">
        <!-- Customer Details -->
        <div class="card-custom mb-4">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-user text-primary me-2"></i> Data Pelanggan</h5>
                @if($order->client->whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->client->whatsapp) }}?text=Halo%20{{ urlencode($order->client->name) }},%20kami%20dari%20Naumil%20Rice%20cabang%20{{ urlencode($store->name) }}%20mengenai%20pesanan%20{{ $order->order_number }}." 
                       target="_blank" class="btn btn-sm btn-success">
                        <i class="fa-brands fa-whatsapp me-1"></i> Chat WhatsApp
                    </a>
                @endif
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Nama Pemesan:</small>
                        <strong class="fs-6 text-dark">{{ $order->client->name ?? '-' }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Nomor WhatsApp:</small>
                        <span class="font-mono text-dark">{{ $order->client->whatsapp ?? '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Email:</small>
                        <span>{{ $order->client->email ?? '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Alamat Pengiriman:</small>
                        <span>{{ $order->client->address ?: 'Ambil di Tempat / Sesuai Catatan' }}</span>
                    </div>
                </div>
                @if($order->notes)
                    <div class="mt-3 p-2 bg-light border rounded small">
                        <strong>Catatan Khusus Pelanggan:</strong> {{ $order->notes }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Ordered Items Table -->
        <div class="card-custom mb-4">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-bowl-rice text-primary me-2"></i> Rincian Menu Dipesan</h5>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Menu</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>
                                    <strong class="fs-6 text-dark">{{ $item->menu_name }}</strong>
                                    @if(!empty($item->selected_options))
                                        <div class="small text-muted mt-1">
                                            @foreach($item->selected_options as $opt)
                                                <span class="badge bg-light text-dark border me-1">
                                                    + {{ $opt['name'] ?? '' }} (Rp {{ number_format($opt['price'] ?? 0, 0, ',', '.') }})
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center font-mono fw-bold fs-6">{{ $item->quantity }}x</td>
                                <td class="text-end font-mono">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="text-end font-mono fw-bold text-dark">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Total Pembayaran:</th>
                            <th class="text-end font-mono fs-5 text-success">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Order Logs -->
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Riwayat Status Pesanan</h5>
            </div>
            <div class="p-3">
                <ul class="list-group list-group-flush">
                    @forelse($order->statusLogs as $log)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <span class="badge bg-secondary me-2">{{ $log->status }}</span>
                                <small class="text-muted">{{ $log->note }}</small>
                            </div>
                            <small class="text-muted font-mono">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                        </li>
                    @empty
                        <li class="list-group-item px-0 text-muted">Belum ada catatan log.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Right Column: Status Updater & Payment Info -->
    <div class="col-lg-4">
        <!-- Update Status Card -->
        <div class="card-custom mb-4 border-primary">
            <div class="card-custom-header bg-primary text-white">
                <h5 class="card-custom-title text-white"><i class="fa-solid fa-sliders me-2"></i> Update Tahapan Pesanan</h5>
            </div>
            <div class="p-3">
                <form action="{{ route('store.backend.orders.updateStatus', ['store_slug' => $store->slug, 'order' => $order->id]) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Status Baru <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Order Konfirmed" {{ $order->status === 'Order Konfirmed' ? 'selected' : '' }}>1. Order Konfirmed (Pesanan Diterima)</option>
                            <option value="Order On Proses" {{ $order->status === 'Order On Proses' ? 'selected' : '' }}>2. Order On Proses (Sedang Dimasak di Dapur)</option>
                            <option value="Order On Packaging" {{ $order->status === 'Order On Packaging' ? 'selected' : '' }}>3. Order On Packaging (Sedang Dikemas)</option>
                            <option value="Order On Delivery" {{ $order->status === 'Order On Delivery' ? 'selected' : '' }}>4. Order On Delivery (Dalam Pengiriman Kurir)</option>
                            <option value="Order Ready" {{ $order->status === 'Order Ready' ? 'selected' : '' }}>5. Order Ready (Selesai / Siap Diambil)</option>
                            <option value="Order Reject" {{ $order->status === 'Order Reject' ? 'selected' : '' }}>6. Order Reject (Pesanan Dibatalkan)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan Log / Pesan ke Pelanggan</label>
                        <textarea name="note" rows="2" class="form-control" placeholder="Contoh: Pesanan telah selesai dimasak dan dipacking rapi..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-check me-1"></i> Perbarui Status Sekarang
                    </button>
                </form>
            </div>
        </div>

        <!-- Payment Info Card -->
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-credit-card text-primary me-2"></i> Info Pembayaran</h5>
            </div>
            <div class="p-3">
                <div class="mb-3">
                    <small class="text-muted d-block">Metode Pembayaran:</small>
                    <strong class="fs-6">{{ $order->payment_method }}</strong>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block">Status Pembayaran:</small>
                    @if($order->payment_status === 'paid')
                        <span class="badge bg-success fs-6">
                            <i class="fa-solid fa-circle-check me-1"></i> LUNAS (PAID)
                        </span>
                    @elseif($order->payment_status === 'payment_on_review')
                        <span class="badge bg-info text-white fs-6">
                            <i class="fa-solid fa-hourglass-half me-1"></i> PAYMENT ON REVIEW
                        </span>
                    @else
                        <span class="badge bg-warning text-dark fs-6">
                            <i class="fa-solid fa-clock me-1"></i> UNPAID
                        </span>
                    @endif
                </div>

                @if(strtolower($order->payment_method) === 'mayar' || !empty($order->payment_id))
                <div class="mb-3 p-2.5 bg-light rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted fw-bold"><i class="fa-solid fa-bolt text-warning me-1"></i> Gateway Mayar.id</small>
                        @if($order->payment_status === 'paid')
                            <span class="badge bg-success-subtle text-success border border-success-subtle small">Lunas</span>
                        @endif
                    </div>
                    @if($order->payment_id)
                        <div class="font-mono small text-truncate text-muted mb-2" title="{{ $order->payment_id }}">
                            Ref: {{ $order->payment_id }}
                        </div>
                    @endif
                    @php
                        $mayarUrl = is_array($order->payment_payload) ? ($order->payment_payload['payment_url'] ?? $order->payment_payload['extracted_link'] ?? ($order->payment_payload['data']['link'] ?? null)) : null;
                    @endphp
                    @if($mayarUrl)
                        <a href="{{ $mayarUrl }}" target="_blank" class="btn btn-sm btn-outline-primary w-100 mb-2">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka Link Invoice Mayar
                        </a>
                    @endif
                    @if($order->payment_status !== 'paid')
                        <form action="{{ route('store.backend.orders.syncMayar', ['store_slug' => $store->slug, 'order' => $order->id]) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning w-100 fw-bold">
                                <i class="fa-solid fa-rotate me-1"></i> Cek & Sinkronkan Status Mayar
                            </button>
                        </form>
                    @endif
                </div>
                @endif

                @if($order->payment_proof)
                <div class="mb-3 p-3 bg-light rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted fw-bold"><i class="fa-solid fa-receipt text-primary me-1"></i> Bukti Transfer QRIS:</small>
                        @if($order->payment_status === 'paid')
                            <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Terverifikasi</span>
                        @else
                            <span class="badge bg-info text-white"><i class="fa-solid fa-hourglass-half me-1"></i> Perlu Verifikasi</span>
                        @endif
                    </div>
                    <div class="text-center my-2">
                        <a href="{{ $order->payment_proof_url }}" target="_blank" class="d-inline-block">
                            <img src="{{ $order->payment_proof_url }}" alt="Bukti Transfer QRIS" class="img-fluid rounded border shadow-sm" style="max-height: 200px;">
                        </a>
                        <small class="text-muted d-block mt-1"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Klik untuk melihat ukuran penuh</small>
                    </div>

                    @if($order->payment_status !== 'paid')
                    <div class="mt-3">
                        <form action="{{ route('store.backend.orders.verifyPayment', ['store_slug' => $store->slug, 'order' => $order->id]) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm" onclick="return confirm('Verifikasi pembayaran dan ubah status menjadi PAID?')">
                                <i class="fa-solid fa-circle-check me-1"></i> Verifikasi Pembayaran & Set PAID
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
                @elseif($order->payment_status !== 'paid' && !str_contains(strtolower($order->payment_method), 'mayar'))
                <div class="mb-3">
                    <form action="{{ route('store.backend.orders.verifyPayment', ['store_slug' => $store->slug, 'order' => $order->id]) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-success w-100 btn-sm fw-bold py-2" onclick="return confirm('Tandai pesanan ini sebagai LUNAS (PAID)?')">
                            <i class="fa-solid fa-check me-1"></i> Konfirmasi Lunas Manual (Set PAID)
                        </button>
                    </form>
                </div>
                @endif

                <div class="mb-3">
                    <small class="text-muted d-block">Total Tagihan:</small>
                    <div class="fs-4 font-mono fw-bold text-success">
                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                    </div>
                </div>

                <a href="{{ url('/trace/' . $order->order_number) }}" target="_blank" class="btn btn-outline-info w-100 btn-sm">
                    <i class="fa-solid fa-radar me-1"></i> Buka Layar Lacak Publik
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
