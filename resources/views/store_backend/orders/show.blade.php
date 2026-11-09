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
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('store.backend.orders.print', ['store_slug' => $store->slug, 'order' => $order->id]) }}" 
               target="_blank" class="btn btn-dark btn-sm shadow-sm">
                <i class="fa-solid fa-print me-1"></i> Cetak Struk POS
            </a>
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
        @if($order->status === 'Order Reject' || $order->is_cancelled)
        <!-- Cancellation Details Card -->
        <div class="card-custom mb-4 border-danger">
            <div class="card-custom-header bg-danger text-white">
                <h5 class="card-custom-title text-white"><i class="fa-solid fa-ban me-2"></i> Pesanan Dibatalkan</h5>
            </div>
            <div class="p-3">
                <div class="mb-2">
                    <small class="text-muted d-block">Dibatalkan Oleh:</small>
                    <span class="badge {{ $order->cancelled_by === 'customer' ? 'bg-info text-white' : 'bg-dark' }}">
                        {{ $order->cancelled_by === 'customer' ? 'Pelanggan (Customer)' : 'Admin Toko' }}
                    </span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Alasan Pembatalan:</small>
                    <strong class="text-danger">{{ $order->cancellation_reason ?: ($order->rejection_reason ?: 'Tidak dicantumkan') }}</strong>
                </div>
                @if($order->cancellation_remark)
                <div class="mb-2 p-2 bg-light rounded border small">
                    <strong>Catatan Tambahan:</strong> {{ $order->cancellation_remark }}
                </div>
                @endif
                @if($order->cancelled_at)
                <div class="mb-2">
                    <small class="text-muted d-block">Waktu Pembatalan:</small>
                    <span class="font-mono small">{{ $order->cancelled_at->format('d/m/Y H:i:s') }} WIB</span>
                </div>
                @endif

                @if($order->refund_bank_name || $order->refund_bank_account_number)
                <div class="mt-3 p-3 bg-warning-subtle border border-warning rounded">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-solid fa-money-bill-transfer text-warning-emphasis fs-5"></i>
                        <strong class="text-dark small">Pengajuan Pengembalian Dana (Refund H+1)</strong>
                    </div>
                    <div class="small">
                        <div><strong>Bank:</strong> {{ $order->refund_bank_name }}</div>
                        <div><strong>No. Rekening:</strong> <span class="font-mono">{{ $order->refund_bank_account_number }}</span></div>
                        <div><strong>Atas Nama:</strong> {{ $order->refund_bank_account_name }}</div>
                        <div><strong>Email Notifikasi:</strong> {{ $order->refund_email ?: $order->client?->email }}</div>
                        <div><strong>Status Refund:</strong> <span class="badge bg-warning text-dark">{{ strtoupper($order->refund_status ?: 'PENDING') }}</span></div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @else
        <!-- Update Status Card -->
        <div class="card-custom mb-4 border-primary">
            <div class="card-custom-header bg-primary text-white">
                <h5 class="card-custom-title text-white"><i class="fa-solid fa-sliders me-2"></i> Update Tahapan Pesanan</h5>
            </div>
            <div class="p-3">
                <form action="{{ route('store.backend.orders.updateStatus', ['store_slug' => $store->slug, 'order' => $order->id]) }}" method="POST" id="formUpdateStatus">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Status Baru <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" id="statusSelect" required>
                            <option value="Order Konfirmed" {{ $order->status === 'Order Konfirmed' ? 'selected' : '' }}>1. Order Konfirmed (Pesanan Diterima)</option>
                            <option value="Order On Proses" {{ $order->status === 'Order On Proses' ? 'selected' : '' }}>2. Order On Proses (Sedang Dimasak di Dapur)</option>
                            <option value="Order On Packaging" {{ $order->status === 'Order On Packaging' ? 'selected' : '' }}>3. Order On Packaging (Sedang Dikemas)</option>
                            <option value="Order On Delivery" {{ $order->status === 'Order On Delivery' ? 'selected' : '' }}>4. Order On Delivery (Dalam Pengiriman Kurir)</option>
                            <option value="Order Ready" {{ $order->status === 'Order Ready' ? 'selected' : '' }}>5. Order Ready (Selesai / Siap Diambil)</option>
                            <option value="Order Reject" {{ $order->status === 'Order Reject' ? 'selected' : '' }}>6. Order Reject (Pesanan Dibatalkan)</option>
                        </select>
                    </div>

                    <div id="rejectReasonBox" style="display: none;" class="p-3 mb-3 bg-danger-subtle border border-danger-subtle rounded">
                        <label class="form-label fw-bold text-danger">Pilih Alasan Pembatalan <span class="text-danger">*</span></label>
                        <select name="cancellation_reason_id" id="cancelReasonSelect" class="form-select mb-2">
                            <option value="">-- Pilih Alasan Pembatalan --</option>
                            @foreach($cancellationReasons ?? [] as $reason)
                                <option value="{{ $reason->id }}" data-text="{{ $reason->reason }}" data-requires-remark="{{ $reason->requires_remark ? '1' : '0' }}">
                                    {{ $reason->reason }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="cancellation_reason" id="cancellationReasonInput" value="">

                        <div id="cancelRemarkBox" style="display: none;">
                            <label class="form-label fw-bold small text-danger">Catatan Detail (Wajib untuk 'Lainnya') <span class="text-danger">*</span></label>
                            <textarea name="cancellation_remark" id="cancelRemarkInput" rows="2" class="form-control" placeholder="Tuliskan alasan pembatalan..."></textarea>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Catatan Log / Pesan ke Pelanggan</label>
                        <textarea name="note" rows="2" class="form-control" placeholder="Contoh: Pesanan telah selesai dimasak dan dipacking rapi..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-check me-1"></i> Perbarui Status Sekarang
                    </button>
                </form>

                <hr class="my-3">
                <button type="button" class="btn btn-outline-danger w-100 fw-bold btn-sm" data-bs-toggle="modal" data-bs-target="#modalCancelOrderAdmin">
                    <i class="fa-solid fa-ban me-1"></i> Batalkan Pesanan Ini
                </button>
            </div>
        </div>
        @endif

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

                {{-- Gateway Midtrans Section --}}
                @if($order->payment_gateway_provider === 'midtrans' || !empty($order->snap_token) || str_contains(strtolower($order->payment_method), 'midtrans'))
                <div class="mb-3 p-2.5 bg-light rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-primary fw-bold"><i class="fa-solid fa-credit-card me-1"></i> Gateway Midtrans Snap</small>
                        @if($order->payment_status === 'paid')
                            <span class="badge bg-success-subtle text-success border border-success-subtle small">Lunas</span>
                        @endif
                    </div>
                    @if($order->snap_token)
                        <div class="font-mono small text-truncate text-muted mb-1" title="{{ $order->snap_token }}">
                            Token: {{ $order->snap_token }}
                        </div>
                    @endif
                    @if($order->payment_url)
                        <a href="{{ $order->payment_url }}" target="_blank" class="btn btn-sm btn-outline-primary w-100 mb-2">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Buka Link Pembayaran Midtrans
                        </a>
                    @endif
                    @if($order->payment_status !== 'paid')
                        <form action="{{ route('store.backend.orders.syncMidtrans', ['store_slug' => $store->slug, 'order' => $order->id]) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">
                                <i class="fa-solid fa-rotate me-1"></i> Cek & Sinkronkan Status Midtrans
                            </button>
                        </form>
                    @endif
                </div>
                {{-- Gateway Mayar Section --}}
                @elseif(strtolower($order->payment_method) === 'mayar' || $order->payment_gateway_provider === 'mayar' || !empty($order->payment_id))
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

{{-- Modal Cancel Order Admin --}}
<div class="modal fade" id="modalCancelOrderAdmin" tabindex="-1" aria-labelledby="modalCancelOrderAdminLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="modalCancelOrderAdminLabel">
                    <i class="fa-solid fa-ban me-2"></i> Batalkan Pesanan #{{ $order->order_number }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('store.backend.orders.cancel', ['store_slug' => $store->slug, 'order' => $order->id]) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Apakah Anda yakin ingin membatalkan pesanan ini? Stok menu akan otomatis dikembalikan ke dapur dan status pesanan berubah menjadi <strong>Order Reject</strong>.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Alasan Pembatalan <span class="text-danger">*</span></label>
                        <select name="cancellation_reason_id" id="modalCancelReasonSelect" class="form-select" required>
                            <option value="">-- Pilih Alasan --</option>
                            @foreach($cancellationReasons ?? [] as $reason)
                                <option value="{{ $reason->id }}" data-text="{{ $reason->reason }}" data-requires-remark="{{ $reason->requires_remark ? '1' : '0' }}">
                                    {{ $reason->reason }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="reason" id="modalReasonTextInput" value="">
                    </div>

                    <div class="mb-3" id="modalRemarkGroup" style="display: none;">
                        <label class="form-label fw-bold">Catatan Tambahan (Remark) <span class="text-danger">*</span></label>
                        <textarea name="remark" id="modalRemarkText" rows="3" class="form-control" placeholder="Tuliskan keterangan detail alasan pembatalan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger fw-bold" id="btnSubmitCancelAdmin">
                        <i class="fa-solid fa-ban me-1"></i> Ya, Batalkan Pesanan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. In-page update status reject toggle
    const statusSelect = document.getElementById('statusSelect');
    const rejectReasonBox = document.getElementById('rejectReasonBox');
    const cancelReasonSelect = document.getElementById('cancelReasonSelect');
    const cancellationReasonInput = document.getElementById('cancellationReasonInput');
    const cancelRemarkBox = document.getElementById('cancelRemarkBox');
    const cancelRemarkInput = document.getElementById('cancelRemarkInput');

    if (statusSelect && rejectReasonBox) {
        statusSelect.addEventListener('change', function () {
            if (this.value === 'Order Reject') {
                rejectReasonBox.style.display = 'block';
                if (cancelReasonSelect) cancelReasonSelect.required = true;
            } else {
                rejectReasonBox.style.display = 'none';
                if (cancelReasonSelect) cancelReasonSelect.required = false;
            }
        });

        if (cancelReasonSelect) {
            cancelReasonSelect.addEventListener('change', function () {
                const opt = this.options[this.selectedIndex];
                const text = opt ? opt.getAttribute('data-text') : '';
                const req = opt ? opt.getAttribute('data-requires-remark') === '1' : false;
                if (cancellationReasonInput) cancellationReasonInput.value = text || '';

                if (req || (text && text.toLowerCase() === 'lainnya')) {
                    if (cancelRemarkBox) cancelRemarkBox.style.display = 'block';
                    if (cancelRemarkInput) cancelRemarkInput.required = true;
                } else {
                    if (cancelRemarkBox) cancelRemarkBox.style.display = 'none';
                    if (cancelRemarkInput) cancelRemarkInput.required = false;
                }
            });
        }
    }

    // 2. Modal cancel order toggle
    const modalSelect = document.getElementById('modalCancelReasonSelect');
    const modalReasonInput = document.getElementById('modalReasonTextInput');
    const modalRemarkGroup = document.getElementById('modalRemarkGroup');
    const modalRemarkText = document.getElementById('modalRemarkText');

    if (modalSelect) {
        modalSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const text = opt ? opt.getAttribute('data-text') : '';
            const req = opt ? opt.getAttribute('data-requires-remark') === '1' : false;
            if (modalReasonInput) modalReasonInput.value = text || '';

            if (req || (text && text.toLowerCase() === 'lainnya')) {
                if (modalRemarkGroup) modalRemarkGroup.style.display = 'block';
                if (modalRemarkText) modalRemarkText.required = true;
            } else {
                if (modalRemarkGroup) modalRemarkGroup.style.display = 'none';
                if (modalRemarkText) modalRemarkText.required = false;
            }
        });
    }
});
</script>
@endpush
@endsection
