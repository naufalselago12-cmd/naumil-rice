@extends('layouts.admin')

@section('title', 'Detail Pesanan ' . $order->order_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Detail Pesanan: <span class="font-monospace text-primary">{{ $order->order_number }}</span></h2>
        <p class="text-muted mb-0">Cabang: <strong>{{ $order->store->name }}</strong> &bull; Dipesan pada: {{ $order->created_at->format('d M Y, H:i:s') }} WIB</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali
        </a>
        <a href="{{ url('/trace/' . $order->order_number) }}" target="_blank" class="btn btn-outline-info">
            <i class="fa-solid fa-radar me-1"></i> Buka Tracing User
        </a>
    </div>
</div>

<!-- Visual Pipeline Progress Bar -->
<div class="card-custom mb-4">
    <div class="card-custom-header">
        <h5 class="card-custom-title"><i class="fa-solid fa-timeline me-2 text-primary"></i> Tahapan Pemrosesan Makanan</h5>
        <span class="badge {{ $order->status === 'Order Reject' ? 'bg-danger' : 'bg-primary' }} p-2">
            Status Terkini: {{ $order->status }}
        </span>
    </div>
    <div class="p-4">
        @php
            $stages = [
                'Order Konfirmed',
                'Order On Proses',
                'Order On Packaging',
                'Order On Delivery',
                'Order Ready'
            ];
            $currentIndex = array_search($order->status, $stages);
            $isRejected = $order->status === 'Order Reject';
        @endphp

        @if($isRejected)
            <div class="alert alert-danger mb-0">
                <h5 class="fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i> Pesanan Ditolak / Reject</h5>
                <p class="mb-0">Alasan penolakan: <strong>{{ $order->rejection_reason ?: 'Tidak ada alasan khusus.' }}</strong></p>
            </div>
        @else
            <div class="position-relative m-4">
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: {{ $currentIndex !== false ? ($currentIndex / (count($stages) - 1)) * 100 : 0 }}%;"></div>
                </div>

                <div class="d-flex justify-content-between position-relative" style="margin-top: -18px;">
                    @foreach($stages as $idx => $stg)
                        @php
                            $isDone = $currentIndex !== false && $idx <= $currentIndex;
                            $isCurrent = $currentIndex !== false && $idx == $currentIndex;
                        @endphp
                        <div class="text-center" style="width: 140px; margin-left: -40px; margin-right: -40px;">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm {{ $isDone ? 'bg-success text-white' : 'bg-light text-muted border' }}"
                                 style="width: 36px; height: 36px; font-weight: bold; font-size: 0.85rem;">
                                @if($isDone) <i class="fa-solid fa-check"></i> @else {{ $idx + 1 }} @endif
                            </div>
                            <div class="small fw-bold mt-2 {{ $isCurrent ? 'text-primary' : ($isDone ? 'text-success' : 'text-muted') }}">
                                {{ $stg }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<div class="row g-4">
    <!-- Left: Items & Order Details -->
    <div class="col-lg-8">
        <div class="card-custom mb-4">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-utensils me-2 text-warning"></i> Rincian Menu Pesanan</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Menu</th>
                            <th style="width: 130px;">Harga Satuan</th>
                            <th style="width: 80px;" class="text-center">Qty</th>
                            <th style="width: 140px;" class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>
                                    <div class="fw-bold fs-6">{{ $item->menu_name }}</div>
                                    @if(!empty($item->selected_options))
                                        <div class="mt-1 small">
                                            @foreach($item->selected_options as $opt)
                                                <span class="badge bg-light text-dark border me-1">
                                                    + {{ $opt['name'] }} (Rp {{ number_format($opt['price'] ?? 0, 0, ',', '.') }})
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($item->special_notes)
                                        <div class="text-muted small mt-1 fst-italic">
                                            <i class="fa-solid fa-note-sticky text-warning me-1"></i> Catatan: {{ $item->special_notes }}
                                        </div>
                                    @endif
                                </td>
                                <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="text-center fw-bold">{{ $item->quantity }}x</td>
                                <td class="text-end fw-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-semibold">Subtotal:</td>
                            <td class="text-end fw-bold">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @if($order->discount_amount > 0)
                            <tr>
                                <td colspan="3" class="text-end text-danger fw-semibold">Potongan Diskon Promo:</td>
                                <td class="text-end text-danger fw-bold">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="3" class="text-end fs-5 fw-bold text-dark">Total Pembayaran:</td>
                            <td class="text-end fs-5 fw-bold text-success">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($order->notes)
                <div class="p-3 bg-light border-top">
                    <strong>Catatan Pemesan:</strong> {{ $order->notes }}
                </div>
            @endif
        </div>

        <!-- Audit Status Logs Timeline -->
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-clock-rotate-left me-2 text-info"></i> Riwayat Tracing & Status Log</h5>
            </div>
            <div class="p-3">
                <ul class="list-group list-group-flush">
                    @forelse($order->statusLogs as $log)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <span class="badge bg-secondary me-2">{{ $log->status }}</span>
                                <span class="fw-semibold">{{ $log->note }}</span>
                            </div>
                            <small class="text-muted">{{ $log->created_at->format('d M Y, H:i:s') }} WIB</small>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Belum ada log aktivitas.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Right: Update Status Controls & Customer Info -->
    <div class="col-lg-4">
        <!-- Update Status Action Card -->
        <div class="card-custom mb-4">
            <div class="card-custom-header bg-primary text-white">
                <h5 class="card-custom-title text-white"><i class="fa-solid fa-forward-step me-2"></i> Perbarui Status Tahapan</h5>
            </div>
            <form action="{{ route('admin.orders.updateStatus', $order->id) }}" method="POST" class="p-3">
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label class="form-label fw-bold">Pilih Status Baru:</label>
                    <select name="status" class="form-select fw-semibold" id="statusSelect">
                        @foreach(\App\Models\Order::ALL_STATUSES as $stt)
                            <option value="{{ $stt }}" {{ $order->status === $stt ? 'selected' : '' }}>
                                {{ $stt }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Catatan Log Status (Opsional):</label>
                    <input type="text" name="note" class="form-control" placeholder="Contoh: Dapur telah menyelesaikan pesanan...">
                </div>

                <div class="mb-3 d-none" id="rejectionReasonDiv">
                    <label class="form-label fw-bold text-danger">Alasan Penolakan (Reject):</label>
                    <textarea name="rejection_reason" rows="2" class="form-control" placeholder="Contoh: Kuota bahan baku hari ini habis..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                    <i class="fa-solid fa-paper-plane me-1"></i> Update Status & Broadcast Realtime
                </button>
            </form>
        </div>

        <!-- Customer & Payment Details -->
        <div class="card-custom">
            <div class="card-custom-header">
                <h5 class="card-custom-title"><i class="fa-solid fa-user me-2 text-primary"></i> Data Pelanggan & Pembayaran</h5>
            </div>
            <div class="p-3">
                <div class="mb-3">
                    <label class="text-muted small d-block">Nama Pelanggan:</label>
                    <div class="fw-bold fs-6">{{ $order->client->name }}</div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small d-block">WhatsApp:</label>
                    <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $order->client->whatsapp)) }}" 
                       target="_blank" class="fw-bold text-success text-decoration-none">
                        <i class="fa-brands fa-whatsapp me-1"></i> {{ $order->client->whatsapp }}
                    </a>
                </div>

                <div class="mb-3">
                    <label class="text-muted small d-block">Email:</label>
                    <div class="fw-semibold">{{ $order->client->email }}</div>
                </div>

                <div class="mb-3">
                    <label class="text-muted small d-block">Alamat Pengiriman / Catatan:</label>
                    <div>{{ $order->client->address ?: '-' }}</div>
                </div>

                <hr>

                <div class="mb-2">
                    <label class="text-muted small d-block">Metode Pembayaran:</label>
                    <span class="badge {{ $order->payment_method === 'QRIS Dinamis' ? 'bg-info text-dark' : 'bg-warning text-dark' }} fs-6">
                        {{ $order->payment_method }}
                    </span>
                </div>

                @if($order->payment_method === 'QRIS Dinamis' && $order->store->qris_image)
                    <div class="mt-3 p-2 border rounded text-center bg-white">
                        <span class="small fw-bold d-block mb-1">QRIS Dinamis Toko</span>
                        <img src="{{ $order->store->qris_image }}" alt="QRIS" class="img-fluid rounded" style="max-width: 140px;">
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const statusSelect = document.getElementById('statusSelect');
    const rejectDiv = document.getElementById('rejectionReasonDiv');

    function checkStatus() {
        if (statusSelect.value === 'Order Reject') {
            rejectDiv.classList.remove('d-none');
        } else {
            rejectDiv.classList.add('d-none');
        }
    }

    statusSelect.addEventListener('change', checkStatus);
    checkStatus();
</script>
@endpush
