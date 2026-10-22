<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Order #{{ $order->order_number }} - {{ $store->name }}</title>
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
            background: #1f2937;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .action-bar .brand-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .action-bar .brand-title {
            font-weight: 700;
            font-size: 15px;
            color: #f9fafb;
        }

        .action-bar .brand-badge {
            background: #374151;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: #9ca3af;
            font-family: monospace;
        }

        .action-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-ui {
            border: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
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

        .width-selector {
            display: flex;
            align-items: center;
            background: #374151;
            border-radius: 6px;
            padding: 2px;
        }

        .width-btn {
            background: transparent;
            border: none;
            color: #9ca3af;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .width-btn.active {
            background: #111827;
            color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        /* Screen Wrapper */
        .pos-screen-container {
            padding: 70px 15px 40px;
            display: flex;
            justify-content: center;
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
            width: 320px;
            font-size: 12px;
        }

        .pos-receipt.paper-58mm {
            width: 250px;
            font-size: 10.5px;
            padding: 12px 10px;
        }

        /* Receipt Inner Content */
        .receipt-inner {
            width: 100%;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .font-mono { font-family: monospace; }
        .text-muted { color: #444; }

        .store-name {
            font-size: 1.25em;
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
            font-size: 0.92em;
        }

        .receipt-row-block {
            margin-top: 3px;
            margin-bottom: 3px;
            font-size: 0.88em;
        }

        .receipt-row-block .label {
            display: block;
            font-weight: bold;
        }

        .receipt-row-block .val {
            display: block;
            word-break: break-word;
        }

        .receipt-callout {
            background: #f4f4f4;
            border: 1px dashed #000;
            padding: 4px 6px;
            font-size: 0.88em;
            margin: 4px 0;
        }

        .note-block {
            background: #fafafa;
            border-left: 2px solid #000;
            padding-left: 5px;
            margin-top: 4px;
        }

        /* Table Header */
        .receipt-table-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 0.85em;
            padding-bottom: 2px;
        }

        .col-item { flex: 1.8; text-align: left; }
        .col-qty { flex: 0.5; text-align: center; }
        .col-price { flex: 0.9; text-align: right; }
        .col-total { flex: 1; text-align: right; }

        /* Items */
        .receipt-item {
            margin-bottom: 6px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .item-name {
            font-size: 0.95em;
            word-break: break-word;
        }

        .item-option {
            font-size: 0.82em;
            padding-left: 8px;
            color: #111;
        }

        .item-note {
            font-size: 0.8em;
            padding-left: 8px;
            font-style: italic;
        }

        .item-calc {
            font-size: 0.88em;
            margin-top: 1px;
        }

        /* Totals */
        .grand-total {
            font-size: 1.15em;
            font-weight: 900;
            margin: 4px 0;
        }

        .text-discount {
            font-weight: bold;
        }

        /* Status Badges */
        .badge-status-paid {
            font-weight: 900;
        }

        .badge-status-unpaid {
            font-weight: 900;
        }

        /* Footer */
        .qr-container {
            margin: 8px auto 4px;
            display: inline-block;
        }

        .qr-code-img {
            width: 100px;
            height: 100px;
            image-rendering: pixelated;
        }

        .paper-58mm .qr-code-img {
            width: 80px;
            height: 80px;
        }

        .qr-label {
            font-size: 0.75em;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .trace-link {
            font-size: 0.7em;
            word-break: break-all;
            margin-bottom: 4px;
        }

        .thank-you {
            font-size: 0.95em;
            margin-bottom: 2px;
        }

        .tagline {
            font-size: 0.82em;
            margin-bottom: 2px;
        }

        .pos-credit {
            font-size: 0.75em;
            color: #333;
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

            .receipt-item {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Bar for Desktop / POS Screen -->
    <header class="action-bar no-print">
        <div class="brand-info">
            <span class="brand-title"><i class="fa-solid fa-receipt me-1"></i> Cetak Bill POS</span>
            <span class="brand-badge">#{{ $order->order_number }}</span>
            <span class="brand-badge">{{ $store->name }}</span>
        </div>

        <div class="action-controls">
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
            <button type="button" class="btn-ui btn-success" onclick="triggerPrint()">
                <i class="fa-solid fa-print"></i> Cetak Struk (Print)
            </button>

            <!-- Close / Back Button -->
            <button type="button" class="btn-ui btn-secondary" onclick="window.close(); if(history.length > 1) history.back();">
                <i class="fa-solid fa-xmark"></i> Tutup
            </button>
        </div>
    </header>

    <!-- Receipt Center Container -->
    <main class="pos-screen-container">
        <div class="pos-receipt paper-80mm" id="posReceipt">
            @include('print.partials.order_receipt_content', ['order' => $order, 'store' => $store])
        </div>
    </main>

    <script>
        function setPaperWidth(width) {
            const receipt = document.getElementById('posReceipt');
            const btn58 = document.getElementById('btnWidth58');
            const btn80 = document.getElementById('btnWidth80');

            if (width === '58mm') {
                receipt.classList.remove('paper-80mm');
                receipt.classList.add('paper-58mm');
                btn58.classList.add('active');
                btn80.classList.remove('active');
                localStorage.setItem('pos_preferred_width', '58mm');
            } else {
                receipt.classList.remove('paper-58mm');
                receipt.classList.add('paper-80mm');
                btn80.classList.add('active');
                btn58.classList.remove('active');
                localStorage.setItem('pos_preferred_width', '80mm');
            }
        }

        function triggerPrint() {
            window.print();
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
