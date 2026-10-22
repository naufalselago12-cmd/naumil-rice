@php
    $store = $order->store ?? $store;
    $traceUrl = url('/trace/' . $order->order_number);
    $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=" . urlencode($traceUrl);
@endphp

<div class="receipt-inner">
    <!-- Store Header -->
    <div class="text-center receipt-header">
        @if(!empty($store->logo))
            <div class="receipt-logo mb-1">
                <img src="{{ $store->logo }}" alt="{{ $store->name }}" style="max-height: 48px; max-width: 120px; object-fit: contain; filter: grayscale(100%);">
            </div>
        @endif
        <h1 class="store-name">{{ strtoupper($store->name) }}</h1>
        @if(!empty($store->code))
            <div class="store-sub">CABANG: {{ strtoupper($store->code) }}</div>
        @endif
        @if(!empty($store->address))
            <div class="store-addr">{{ $store->address }}</div>
        @endif
        @if(!empty($store->phone))
            <div class="store-phone">Telp/WA: {{ $store->phone }}</div>
        @endif
    </div>

    <div class="divider-double"></div>

    <!-- Receipt / Order Meta -->
    <div class="receipt-meta">
        <div class="receipt-row bold">
            <span>NO. STRUK</span>
            <span class="font-mono">#{{ $order->order_number }}</span>
        </div>
        <div class="receipt-row">
            <span>TANGGAL</span>
            <span>{{ $order->created_at->format('d/m/Y H:i') }} WIB</span>
        </div>
        <div class="receipt-row">
            <span>KASIR/CABANG</span>
            <span>{{ $store->code ?: 'Dapur Cabang' }}</span>
        </div>
        <div class="receipt-row">
            <span>TIPE PESANAN</span>
            <span class="bold">{{ $order->campaign_id ? 'PRE-ORDER (PO)' : 'DINE IN / TAKE AWAY' }}</span>
        </div>

        @if($order->campaign)
            <div class="receipt-callout mt-1 mb-1">
                <div class="receipt-row bold">
                    <span>NAMA PO</span>
                    <span>{{ $order->campaign->title }}</span>
                </div>
                <div class="receipt-row">
                    <span>JADWAL SIAP</span>
                    <span>{{ $order->campaign->ready_at->format('d/m/Y H:i') }} WIB</span>
                </div>
            </div>
        @endif

        <div class="divider-dashed my-1"></div>

        <div class="receipt-row">
            <span>PELANGGAN</span>
            <span class="bold">{{ $order->client->name ?? '-' }}</span>
        </div>
        @if(!empty($order->client->whatsapp))
            <div class="receipt-row">
                <span>NO. WHATSAPP</span>
                <span class="font-mono">{{ $order->client->whatsapp }}</span>
            </div>
        @endif
        @if(!empty($order->client->address))
            <div class="receipt-row-block">
                <span class="label">ALAMAT KIRIM:</span>
                <span class="val">{{ $order->client->address }}</span>
            </div>
        @endif
        @if(!empty($order->notes))
            <div class="receipt-row-block note-block">
                <span class="label">CATATAN PEMESAN:</span>
                <span class="val">{{ $order->notes }}</span>
            </div>
        @endif
    </div>

    <div class="divider-double"></div>

    <!-- Items Header -->
    <div class="receipt-table-header">
        <span class="col-item">ITEM / MENU</span>
        <span class="col-qty">QTY</span>
        <span class="col-price">HARGA</span>
        <span class="col-total">TOTAL</span>
    </div>
    <div class="divider-dashed"></div>

    <!-- Item Rows -->
    <div class="receipt-items">
        @foreach($order->items as $item)
            <div class="receipt-item">
                <div class="item-name bold">{{ $item->menu_name }}</div>
                
                @if(!empty($item->selected_options) && is_array($item->selected_options))
                    @foreach($item->selected_options as $opt)
                        <div class="item-option">
                            + {{ $opt['name'] ?? '' }} 
                            @if(!empty($opt['price']) && $opt['price'] > 0)
                                (@Rp {{ number_format($opt['price'], 0, ',', '.') }})
                            @endif
                        </div>
                    @endforeach
                @endif

                @if(!empty($item->special_notes))
                    <div class="item-note">* Catatan: {{ $item->special_notes }}</div>
                @endif

                <div class="item-calc receipt-row">
                    <span>{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                    <span class="font-mono bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="divider-dashed"></div>

    <!-- Calculation Totals -->
    <div class="receipt-totals">
        <div class="receipt-row">
            <span>Total Qty Porsi:</span>
            <span class="bold">{{ $order->items->sum('quantity') }} Porsi</span>
        </div>
        <div class="receipt-row">
            <span>Subtotal:</span>
            <span class="font-mono">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
        </div>
        
        @if($order->discount_amount > 0)
            <div class="receipt-row text-discount">
                <span>Diskon Promo / PO:</span>
                <span class="font-mono">- Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
            </div>
        @endif

        @if(!empty($order->points_used) && $order->points_used > 0)
            <div class="receipt-row text-discount">
                <span>Tukar Poin ({{ $order->points_used }} Pts):</span>
                <span class="font-mono">- Rp {{ number_format($order->points_used * 1000, 0, ',', '.') }}</span>
            </div>
        @endif

        <div class="divider-double"></div>

        <div class="receipt-row grand-total">
            <span>TOTAL TAGIHAN</span>
            <span class="font-mono">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
        </div>

        <div class="divider-double"></div>
    </div>

    <!-- Payment & Status Section -->
    <div class="receipt-payment">
        <div class="receipt-row">
            <span>Metode Pembayaran:</span>
            <span class="bold">{{ $order->payment_method ?: 'QRIS' }}</span>
        </div>
        <div class="receipt-row">
            <span>Status Pembayaran:</span>
            @if($order->payment_status === 'paid')
                <span class="badge-status-paid bold">*** LUNAS (PAID) ***</span>
            @elseif($order->payment_status === 'payment_on_review')
                <span class="badge-status-review bold">*** REVIEW BUKTI ***</span>
            @else
                <span class="badge-status-unpaid bold">*** BELUM LUNAS ***</span>
            @endif
        </div>
        <div class="receipt-row">
            <span>Status Pesanan:</span>
            <span class="bold">{{ $order->status }}</span>
        </div>
        @if(!empty($order->payment_id))
            <div class="receipt-row small text-muted">
                <span>Ref / Invoice:</span>
                <span class="font-mono">{{ $order->payment_id }}</span>
            </div>
        @endif
        @if(!empty($order->points_earned) && $order->points_earned > 0)
            <div class="receipt-row small">
                <span>Poin Didapat:</span>
                <span class="bold">+{{ $order->points_earned }} Poin Loyalty</span>
            </div>
        @endif
    </div>

    <div class="divider-dashed"></div>

    <!-- QR Code Trace & Footer -->
    <div class="receipt-footer text-center">
        <div class="qr-container my-2">
            <img src="{{ $qrApiUrl }}" alt="QR Code Trace" class="qr-code-img">
        </div>
        <div class="qr-label font-mono">SCAN UNTUK LACAK STATUS PESANAN</div>
        <div class="trace-link font-mono">{{ $traceUrl }}</div>

        <div class="divider-double my-2"></div>

        <div class="thank-you bold">TERIMA KASIH ATAS KUNJUNGAN ANDA!</div>
        <div class="tagline">Selamat Menikmati Hidangan Kami</div>
        <div class="pos-credit font-mono">~ Naumil Rice POS System ~</div>
    </div>
</div>
