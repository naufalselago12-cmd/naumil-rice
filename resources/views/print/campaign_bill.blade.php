<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Rekap PO {{ $campaign->title }} - {{ $store->name }}</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* Base Reset & Variables */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #e5e7eb;
            font-family: 'Courier New', Courier, 'Lucida Console', Monaco, monospace;
            color: #000;
            line-height: 1.35;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Top Action Bar (Hidden on Print) */
        .no-print {
            display: block;
        }

        .action-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #111827;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            z-index: 1000;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .action-bar .brand-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-bar .brand-title {
            font-weight: 700;
            font-size: 14px;
            color: #f9fafb;
        }

        .action-bar .brand-badge {
            background: #374151;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            color: #9ca3af;
            font-family: monospace;
        }

        .action-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-ui {
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #374151;
            color: #e5e7eb;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-success {
            background: #059669;
            color: #fff;
        }

        .btn-success:hover {
            background: #047857;
        }

        .btn-warning {
            background: #d97706;
            color: #fff;
        }

        .btn-warning:hover {
            background: #b45309;
        }

        .mode-selector, .width-selector {
            display: flex;
            align-items: center;
            background: #1f2937;
            border-radius: 6px;
            padding: 2px;
            border: 1px solid #374151;
        }

        .mode-btn, .width-btn {
            background: transparent;
            border: none;
            color: #9ca3af;
            padding: 5px 9px;
            font-size: 11.5px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .mode-btn.active, .width-btn.active {
            background: #3b82f6;
            color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        /* Screen Wrapper */
        .pos-screen-container {
            padding: 70px 15px 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        /* POS Receipt Styling */
        .pos-receipt {
            background: #fff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            color: #000;
            padding: 16px 14px;
            margin: 0 auto;
            position: relative;
            transition: width 0.2s ease;
        }

        /* Paper Sizes on Screen */
        .pos-receipt.paper-80mm {
            width: 330px;
            font-size: 12px;
        }

        .pos-receipt.paper-58mm {
            width: 255px;
            font-size: 10.5px;
            padding: 12px 10px;
        }

        /* Shared Receipt Typography */
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .font-mono { font-family: monospace; }
        .text-muted { color: #444; }

        .store-name {
            font-size: 1.22em;
            font-weight: 900;
            letter-spacing: 0.5px;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .store-sub {
            font-size: 0.85em;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .store-addr, .store-phone {
            font-size: 0.82em;
            line-height: 1.25;
        }

        .bill-title {
            font-size: 1.05em;
            font-weight: 900;
            margin: 3px 0 1px;
            letter-spacing: 0.5px;
        }

        .bill-subtitle {
            font-size: 0.8em;
            font-weight: bold;
        }

        /* Dividers */
        .divider-double {
            border-top: 2px dashed #000;
            margin: 8px 0;
        }

        .divider-dashed {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        /* Rows */
        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 2px;
            font-size: 0.9em;
        }

        .section-header {
            font-weight: 900;
            text-align: center;
            font-size: 0.95em;
            letter-spacing: 0.5px;
            margin: 4px 0;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 0.85em;
            padding-bottom: 2px;
        }

        .menu-row {
            margin-bottom: 4px;
        }

        .manifest-card {
            border: 1px dashed #666;
            padding: 6px 8px;
            margin-bottom: 6px;
            background: #fafafa;
            page-break-inside: avoid;
            break-inside: avoid;
            font-size: 0.88em;
        }

        .manifest-title {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .manifest-items {
            font-size: 0.85em;
            color: #111;
            margin-bottom: 2px;
        }

        .manifest-footer {
            display: flex;
            justify-content: space-between;
            font-size: 0.85em;
            font-weight: bold;
        }

        .manifest-note {
            font-size: 0.8em;
            font-style: italic;
            color: #333;
            margin-top: 2px;
            border-top: 1px dotted #999;
            padding-top: 2px;
        }

        .sig-block {
            display: flex;
            justify-content: space-between;
            text-align: center;
            margin-top: 14px;
            margin-bottom: 4px;
            font-size: 0.85em;
        }

        .sig-space {
            margin-top: 35px;
        }

        /* Batch Orders Page Break */
        .order-batch-item {
            margin-bottom: 20px;
        }

        /* ============================================================
           PRINT MEDIA OPTIMIZATIONS (ESC/POS & THERMAL PRINTERS)
           ============================================================ */
        @media print {
            @page {
                margin: 0;
                size: auto;
            }

            html, body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                color: #000000 !important;
            }

            .no-print {
                display: none !important;
            }

            .pos-screen-container {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }

            .pos-receipt {
                box-shadow: none !important;
                border: none !important;
                margin: 0 auto !important;
                padding: 3mm 4mm !important;
            }

            .pos-receipt.paper-80mm {
                width: 78mm !important;
                max-width: 80mm !important;
                font-size: 11.5px !important;
            }

            .pos-receipt.paper-58mm {
                width: 54mm !important;
                max-width: 58mm !important;
                font-size: 10px !important;
                padding: 2mm 2.5mm !important;
            }

            .divider-double {
                border-top: 2px dashed #000 !important;
            }

            .divider-dashed {
                border-top: 1px dashed #000 !important;
            }

            .manifest-card {
                background: #fff !important;
                border: 1px dashed #000 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .page-break {
                page-break-after: always !important;
                break-after: page !important;
                height: 0 !important;
                display: block !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Bar for Desktop / POS Screen -->
    <header class="action-bar no-print">
        <div class="brand-info">
            <span class="brand-title"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Bill Pre-Order (PO)</span>
            <span class="brand-badge">{{ $campaign->title }}</span>
            <span class="brand-badge">{{ $store->name }}</span>
        </div>

        <div class="action-controls">
            <!-- Mode Switcher: Rekap PO vs Semua Struk Order -->
            <div class="mode-selector">
                <a href="{{ request()->fullUrlWithQuery(['mode' => 'rekap']) }}" 
                   class="mode-btn {{ ($mode ?? 'rekap') === 'rekap' ? 'active' : '' }}" 
                   title="Tampilkan Struk Rekapitulasi Konsolidasi PO">
                    <i class="fa-solid fa-list-check"></i> Struk Rekap PO
                </a>
                <a href="{{ request()->fullUrlWithQuery(['mode' => 'all_orders']) }}" 
                   class="mode-btn {{ ($mode ?? 'rekap') === 'all_orders' ? 'active' : '' }}" 
                   title="Cetak Semua Struk Pemesan PO Sekaligus (Batch)">
                    <i class="fa-solid fa-receipt"></i> Semua Struk Order ({{ $orders->count() }})
                </a>
            </div>

            <!-- Paper Width Selector -->
            <div class="width-selector" title="Pilih Ukuran Kertas Thermal">
                <button type="button" class="width-btn" id="btnWidth58" onclick="setPaperWidth('58mm')">
                    58mm
                </button>
                <button type="button" class="width-btn active" id="btnWidth80" onclick="setPaperWidth('80mm')">
                    80mm
                </button>
            </div>

            <!-- Print Trigger Button -->
            <button type="button" class="btn-ui btn-success" onclick="window.print()">
                <i class="fa-solid fa-print"></i> 
                {{ ($mode ?? 'rekap') === 'all_orders' ? 'Cetak Semua Struk (' . $orders->count() . ')' : 'Cetak Rekap PO' }}
            </button>

            <!-- Close / Back Button -->
            <button type="button" class="btn-ui btn-secondary" onclick="window.close(); if(history.length > 1) history.back();">
                <i class="fa-solid fa-xmark"></i> Tutup
            </button>
        </div>
    </header>

    <main class="pos-screen-container">

        @if(($mode ?? 'rekap') === 'all_orders')
            <!-- ============================================================
                 MODE 2: BATCH PRINT ALL ORDERS IN THIS PO
                 ============================================================ -->
            @forelse($orders as $index => $order)
                <div class="pos-receipt paper-80mm order-batch-item pos-receipt-target" id="posReceipt_{{ $order->id }}">
                    <div class="no-print text-center mb-2 pb-1 border-bottom small bold text-muted">
                        STRUK PESANAN {{ $index + 1 }} DARI {{ $orders->count() }} ({{ $campaign->title }})
                    </div>
                    @include('print.partials.order_receipt_content', ['order' => $order, 'store' => $store])
                </div>
                @if(!$loop->last)
                    <div class="page-break"></div>
                @endif
            @empty
                <div class="pos-receipt paper-80mm text-center py-5">
                    <p class="bold">Belum ada pesanan masuk untuk PO ini.</p>
                </div>
            @endforelse

        @else
            <!-- ============================================================
                 MODE 1: CONSOLIDATED PO BILL (STRUK REKAPITULASI PRE-ORDER)
                 ============================================================ -->
            <div class="pos-receipt paper-80mm pos-receipt-target" id="posReceipt">
                <!-- Store Header -->
                <div class="text-center receipt-header">
                    @if(!empty($store->logo))
                        <div class="receipt-logo mb-1">
                            <img src="{{ $store->logo }}" alt="{{ $store->name }}" style="max-height: 46px; max-width: 120px; object-fit: contain; filter: grayscale(100%);">
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

                    <div class="divider-double"></div>

                    <div class="bill-title">STRUK REKAP PRE-ORDER (PO)</div>
                    <div class="bill-subtitle">KONSOLIDASI BATCH DAPUR & KEUANGAN</div>
                </div>

                <div class="divider-double"></div>

                <!-- PO Batch Metadata -->
                <div class="receipt-meta">
                    <div class="receipt-row bold">
                        <span>NAMA PO</span>
                        <span>{{ $campaign->title }}</span>
                    </div>
                    <div class="receipt-row">
                        <span>KODE BATCH</span>
                        <span class="font-mono bold">{{ $campaign->qr_token }}</span>
                    </div>
                    <div class="receipt-row">
                        <span>STATUS BATCH</span>
                        <span class="bold">{{ $campaign->is_expired ? 'EXPIRED (CLOSE ORDER)' : 'OPEN PRE-ORDER' }}</span>
                    </div>
                    <div class="receipt-row">
                        <span>CLOSE ORDER</span>
                        <span>{{ $campaign->close_order_at->format('d/m/Y H:i') }} WIB</span>
                    </div>
                    <div class="receipt-row bold">
                        <span>JADWAL SIAP</span>
                        <span>{{ $campaign->ready_at->format('d/m/Y H:i') }} WIB</span>
                    </div>
                    <div class="receipt-row">
                        <span>KUOTA BATCH</span>
                        <span>{{ $campaign->max_quota ? $campaign->max_quota . ' Porsi' : 'Tak Terbatas' }}</span>
                    </div>
                </div>

                <div class="divider-double"></div>

                <!-- 1. KITCHEN PRODUCTION RECAP (REKAP MENU & JUMLAH PORSI) -->
                <div class="section-header">REKAP MENU & TOTAL PRODUKSI</div>
                <div class="divider-dashed"></div>

                <div class="table-header">
                    <span>MENU MAKANAN</span>
                    <span style="width: 50px; text-align: center;">QTY</span>
                    <span style="width: 80px; text-align: right;">TOTAL</span>
                </div>
                <div class="divider-dashed"></div>

                <div class="menu-list">
                    @forelse($menuSummary as $menu)
                        <div class="menu-row">
                            <div class="receipt-row bold">
                                <span>{{ $menu['name'] }}</span>
                                <span class="font-mono">{{ $menu['quantity'] }}x</span>
                            </div>
                            @if(!empty($menu['options']))
                                @foreach($menu['options'] as $optName => $optQty)
                                    <div class="receipt-row small text-muted" style="padding-left: 8px;">
                                        <span>+ {{ $optName }}</span>
                                        <span class="font-mono">{{ $optQty }}x</span>
                                    </div>
                                @endforeach
                            @endif
                            <div class="receipt-row small text-muted">
                                <span>Subtotal Nilai Menu:</span>
                                <span class="font-mono">Rp {{ number_format($menu['subtotal'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-2 text-muted">Belum ada pesanan makanan masuk.</div>
                    @endforelse
                </div>

                <div class="divider-dashed"></div>

                <div class="receipt-row bold" style="font-size: 1em;">
                    <span>TOTAL PORSI PRODUKSI:</span>
                    <span class="font-mono">{{ $totalPorsi }} PORSI</span>
                </div>

                <div class="divider-double"></div>

                <!-- 2. FINANCIAL SUMMARY (RINGKASAN KEUANGAN PO) -->
                <div class="section-header">RINGKASAN KEUANGAN PO</div>
                <div class="divider-dashed"></div>

                <div class="receipt-row">
                    <span>Total Transaksi Masuk:</span>
                    <span class="bold">{{ $totalOrdersCount }} Order</span>
                </div>
                <div class="receipt-row">
                    <span>Total Nilai Kotor (Gross):</span>
                    <span class="font-mono">Rp {{ number_format($grossTotal, 0, ',', '.') }}</span>
                </div>
                @if($totalDiscounts > 0)
                    <div class="receipt-row bold">
                        <span>Total Diskon Promo / PO:</span>
                        <span class="font-mono">- Rp {{ number_format($totalDiscounts, 0, ',', '.') }}</span>
                    </div>
                @endif

                <div class="divider-double"></div>

                <div class="receipt-row grand-total bold" style="font-size: 1.1em;">
                    <span>TOTAL OMSET BERSIH PO</span>
                    <span class="font-mono">Rp {{ number_format($netTotal, 0, ',', '.') }}</span>
                </div>

                <div class="divider-double"></div>

                <div class="receipt-meta mt-1">
                    <div class="receipt-row bold">
                        <span>REALISASI PEMBAYARAN:</span>
                    </div>
                    <div class="receipt-row" style="color: #047857;">
                        <span>&bull; LUNAS (PAID):</span>
                        <span class="font-mono bold">{{ $paidOrdersCount }} Order (Rp {{ number_format($paidTotal, 0, ',', '.') }})</span>
                    </div>
                    <div class="receipt-row" style="color: #b45309;">
                        <span>&bull; BELUM LUNAS (UNPAID):</span>
                        <span class="font-mono bold">{{ $unpaidOrdersCount }} Order (Rp {{ number_format($unpaidTotal, 0, ',', '.') }})</span>
                    </div>
                </div>

                <div class="divider-double"></div>

                <!-- 3. ORDER MANIFEST / CHECKLIST DAFTAR PEMESAN -->
                <div class="section-header">MANIFEST DAFTAR PEMESAN ({{ $orders->count() }})</div>
                <div class="text-center small text-muted mb-2">Gunakan kotak [ ] untuk checklist packaging/serah terima</div>
                <div class="divider-dashed"></div>

                <div class="manifest-list">
                    @forelse($orders as $idx => $ord)
                        <div class="manifest-card">
                            <div class="manifest-title">
                                <span>[ ] {{ $idx + 1 }}. {{ $ord->client->name ?? '-' }}</span>
                                <span class="font-mono bold">#{{ $ord->order_number }}</span>
                            </div>

                            <div class="receipt-row small">
                                <span>No. WA: {{ $ord->client->whatsapp ?? '-' }}</span>
                                <span class="bold font-mono">{{ $ord->items->sum('quantity') }} Porsi</span>
                            </div>

                            <div class="manifest-items">
                                <strong>Menu:</strong> 
                                @foreach($ord->items as $it)
                                    {{ $it->quantity }}x {{ $it->menu_name }}{{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            </div>

                            @if(!empty($ord->client->address))
                                <div class="small">
                                    <strong>Alamat:</strong> {{ $ord->client->address }}
                                </div>
                            @endif

                            @if(!empty($ord->notes))
                                <div class="manifest-note">
                                    <strong>Catatan:</strong> {{ $ord->notes }}
                                </div>
                            @endif

                            <div class="manifest-footer mt-1 pt-1 border-top">
                                <span>Total: Rp {{ number_format($ord->total_amount, 0, ',', '.') }}</span>
                                <span>
                                    @if($ord->payment_status === 'paid')
                                        [LUNAS - {{ $ord->payment_method }}]
                                    @else
                                        [BELUM LUNAS]
                                    @endif
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-2 text-muted">Belum ada pemesan dalam PO ini.</div>
                    @endforelse
                </div>

                <div class="divider-double"></div>

                <!-- Signatures -->
                <div class="sig-block">
                    <div>
                        <div>Petugas Dapur</div>
                        <div class="sig-space">(..................)</div>
                    </div>
                    <div>
                        <div>Kasir / Admin</div>
                        <div class="sig-space">(..................)</div>
                    </div>
                </div>

                <div class="divider-double mt-3"></div>

                <!-- System Footer -->
                <div class="text-center small" style="font-size: 0.78em;">
                    <div>Waktu Cetak: {{ now()->format('d/m/Y H:i:s') }} WIB</div>
                    <div class="font-mono">Naumil POS Pre-Order Management System</div>
                </div>
            </div>
        @endif

    </main>

    <script>
        function setPaperWidth(width) {
            const receipts = document.querySelectorAll('.pos-receipt-target');
            const btn58 = document.getElementById('btnWidth58');
            const btn80 = document.getElementById('btnWidth80');

            receipts.forEach(r => {
                if (width === '58mm') {
                    r.classList.remove('paper-80mm');
                    r.classList.add('paper-58mm');
                } else {
                    r.classList.remove('paper-58mm');
                    r.classList.add('paper-80mm');
                }
            });

            if (width === '58mm') {
                btn58.classList.add('active');
                btn80.classList.remove('active');
                localStorage.setItem('pos_preferred_width', '58mm');
            } else {
                btn80.classList.add('active');
                btn58.classList.remove('active');
                localStorage.setItem('pos_preferred_width', '80mm');
            }
        }

        // Restore user's preferred thermal width
        document.addEventListener('DOMContentLoaded', function () {
            const savedWidth = localStorage.getItem('pos_preferred_width');
            if (savedWidth) {
                setPaperWidth(savedWidth);
            }

            // Auto print if URL has ?autoprint=1
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoprint') === '1') {
                setTimeout(function () {
                    window.print();
                }, 500);
            }
        });
    </script>
</body>
</html>
