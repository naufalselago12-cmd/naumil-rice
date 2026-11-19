<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=3">
    <link rel="icon" type="image/png" sizes="192x192" href="/favicon-192x192.png?v=3">
    <title>Kasir POS - {{ $store->name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    @if(!empty($catalog['store']['has_midtrans']) && !empty($catalog['store']['midtrans_client_key']))
        <script src="{{ $catalog['store']['midtrans_snap_js_url'] }}"
                data-client-key="{{ $catalog['store']['midtrans_client_key'] }}" defer></script>
    @endif

    <style>
        :root {
            --pos-bg: #f1f5f9;
            --pos-surface: #ffffff;
            --pos-dark: #0f172a;
            --pos-accent: #7c3aed;
            --pos-accent-soft: #ede9fe;
            --pos-cash: #059669;
            --pos-cash-soft: #d1fae5;
            --pos-qris: #0284c7;
            --pos-qris-soft: #e0f2fe;
            --pos-pack: #d97706;
            --pos-done: #16a34a;
            --pos-border: #e2e8f0;
            --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * { box-sizing: border-box; }
        body {
            font-family: var(--font-sans);
            background: var(--pos-bg);
            color: var(--pos-dark);
            margin: 0;
            min-height: 100vh;
            padding-bottom: 84px;
        }

        /* Full-Icon + Small Caption Button Pattern */
        .ico-btn {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            padding: 7px 11px;
            border-radius: 13px;
            border: 1.5px solid var(--pos-border);
            background: #fff;
            color: #334155;
            text-decoration: none;
            cursor: pointer;
            transition: all .15s ease;
            min-width: 60px;
            position: relative;
            line-height: 1;
        }
        .ico-btn i { font-size: 1.15rem; line-height: 1.1; }
        .ico-btn .ico-lbl { font-size: 10px; font-weight: 700; letter-spacing: .01em; white-space: nowrap; }
        .ico-btn:hover { transform: translateY(-1px); border-color: #cbd5e1; color: var(--pos-dark); }
        .ico-btn.active {
            background: var(--pos-accent);
            border-color: var(--pos-accent);
            color: #fff;
            box-shadow: 0 4px 12px rgba(124, 58, 237, .28);
        }
        .ico-btn.btn-cash { border-color: #a7f3d0; background: #ecfdf5; color: #065f46; }
        .ico-btn.btn-cash.selected, .ico-btn.btn-cash-solid {
            background: linear-gradient(135deg, #10b981, #059669);
            border-color: #059669;
            color: #fff;
            box-shadow: 0 4px 14px rgba(5, 150, 105, .3);
        }
        .ico-btn.btn-qris { border-color: #bae6fd; background: #f0f9ff; color: #075985; }
        .ico-btn.btn-qris.selected, .ico-btn.btn-qris-solid {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            border-color: #0284c7;
            color: #fff;
            box-shadow: 0 4px 14px rgba(2, 132, 199, .3);
        }
        .ico-btn.btn-pack {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-color: #d97706;
            color: #fff;
            box-shadow: 0 4px 12px rgba(217, 119, 6, .28);
        }
        .ico-btn.btn-done {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-color: #16a34a;
            color: #fff;
            box-shadow: 0 4px 12px rgba(22, 163, 74, .28);
        }
        .ico-btn.btn-danger-soft { border-color: #fecaca; background: #fef2f2; color: #dc2626; }
        .ico-btn .ico-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 19px;
            height: 19px;
            padding: 0 5px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
        }

        /* Top Bar */
        .pos-header {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: #0f172a;
            color: #fff;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 3px 16px rgba(15, 23, 42, .18);
        }
        .pos-header .ico-btn {
            background: rgba(255,255,255,.08);
            border-color: rgba(255,255,255,.14);
            color: #e2e8f0;
        }
        .pos-header .ico-btn:hover { background: rgba(255,255,255,.16); color: #fff; }
        .pos-header .ico-btn.active { background: var(--pos-accent); border-color: var(--pos-accent); color: #fff; }
        .pos-header .ico-btn.btn-logout { background: rgba(239,68,68,.18); border-color: rgba(239,68,68,.4); color: #fca5a5; }
        .pos-store-logo { width: 40px; height: 40px; border-radius: 11px; object-fit: cover; border: 2px solid rgba(255,255,255,.25); }

        /* Main Grid */
        .pos-layout {
            display: grid;
            grid-template-columns: 1fr 370px;
            gap: 16px;
            max-width: 1480px;
            margin: 0 auto;
            padding: 14px 16px;
        }
        @media (max-width: 992px) {
            .pos-layout { grid-template-columns: 1fr; }
            .pos-cart-panel { display: none; }
            .pos-cart-panel.mobile-open {
                display: flex !important;
                position: fixed;
                inset: 0;
                z-index: 1060;
                border-radius: 0;
                max-height: 100vh;
            }
        }

        /* Category Strip */
        .cat-strip {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 6px;
            scrollbar-width: none;
        }
        .cat-strip::-webkit-scrollbar { display: none; }

        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(156px, 1fr));
            gap: 12px;
        }
        .menu-card {
            background: #fff;
            border: 1.5px solid var(--pos-border);
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: all .15s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .menu-card:hover { transform: translateY(-2px); border-color: var(--pos-accent); box-shadow: 0 8px 20px rgba(15,23,42,.08); }
        .menu-card:active { transform: scale(.98); }
        .menu-thumb {
            height: 108px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .menu-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .menu-qty-pill {
            position: absolute;
            top: 8px;
            right: 8px;
            background: var(--pos-accent);
            color: #fff;
            font-weight: 800;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 999px;
            box-shadow: 0 2px 8px rgba(124,58,237,.4);
        }
        .menu-opt-pill {
            position: absolute;
            bottom: 6px;
            left: 6px;
            background: rgba(15,23,42,.75);
            color: #fff;
            font-size: 9.5px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 6px;
        }
        .menu-body { padding: 10px; display: flex; flex-direction: column; flex: 1; }
        .menu-title { font-weight: 700; font-size: .86rem; line-height: 1.25; margin-bottom: 4px; color: #0f172a; }
        .menu-price { font-family: var(--font-mono); font-weight: 700; font-size: .86rem; color: var(--pos-accent); margin-top: auto; }

        /* Cart Panel */
        .pos-cart-panel {
            background: #fff;
            border: 1px solid var(--pos-border);
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 96px);
            position: sticky;
            top: 78px;
            box-shadow: 0 6px 24px rgba(15,23,42,.06);
            overflow: hidden;
        }
        .cart-items-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 12px 14px;
        }
        .cart-row {
            border: 1px solid #f1f5f9;
            background: #f8fafc;
            border-radius: 13px;
            padding: 10px;
            margin-bottom: 8px;
        }

        /* Payment Selector Cards */
        .pay-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .pay-grid .ico-btn {
            padding: 12px 10px;
            border-width: 2px;
        }
        .pay-grid .ico-btn i { font-size: 1.55rem; margin-bottom: 2px; }
        .pay-grid .ico-btn .ico-lbl { font-size: 11px; }

        /* KDS Cards */
        .kds-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 14px;
        }
        .kds-card {
            background: #fff;
            border-radius: 18px;
            border: 1.5px solid var(--pos-border);
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(15,23,42,.05);
            display: flex;
            flex-direction: column;
        }
        .kds-card.stage-packaging { border-color: #fbbf24; }
        .kds-card-top {
            padding: 11px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            background: #f8fafc;
            border-bottom: 1px solid var(--pos-border);
        }
        .kds-card.stage-packaging .kds-card-top { background: #fffbeb; }

        /* Mobile Bottom Cart Bar */
        .mobile-cart-fab {
            display: none;
            position: fixed;
            left: 14px;
            right: 14px;
            bottom: 14px;
            z-index: 1040;
            background: #0f172a;
            color: #fff;
            border-radius: 16px;
            padding: 10px 14px;
            box-shadow: 0 10px 28px rgba(15,23,42,.35);
            align-items: center;
            justify-content: space-between;
        }
        @media (max-width: 992px) {
            .mobile-cart-fab.visible { display: flex; }
        }

        /* Toast */
        .pos-toast-box {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1100;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .pos-toast {
            background: #0f172a;
            color: #fff;
            padding: 10px 16px;
            border-radius: 12px;
            font-size: .84rem;
            font-weight: 600;
            box-shadow: 0 8px 24px rgba(15,23,42,.25);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pos-toast.ok { background: #059669; }
        .pos-toast.err { background: #dc2626; }
    </style>
</head>
<body>

{{-- HEADER: Full-Icon + Small Caption Navigation --}}
<header class="pos-header">
    <div class="d-flex align-items-center gap-2" style="min-width:0;">
        <img src="{{ $store->logo ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=80' }}"
             alt="{{ $store->name }}" class="pos-store-logo">
        <div style="min-width:0;">
            <div class="fw-bold text-truncate" style="font-size:.95rem;">{{ $store->name }}</div>
            <div class="d-flex align-items-center gap-1" style="font-size:.72rem;color:#94a3b8;">
                <span class="badge" style="background:#7c3aed;font-size:.64rem;">KASIR</span>
                <span class="text-truncate">{{ $user->name }}</span>
            </div>
        </div>
    </div>

    <nav class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
        <button type="button" class="ico-btn active" data-tab="pos" onclick="switchTab('pos')">
            <i class="fa-solid fa-cash-register"></i>
            <span class="ico-lbl">Kasir</span>
        </button>
        <button type="button" class="ico-btn" data-tab="kds" onclick="switchTab('kds')">
            <i class="fa-solid fa-boxes-packing"></i>
            <span class="ico-lbl">KDS</span>
            <span class="ico-badge" id="kdsBadge" style="display:none;">0</span>
        </button>
        <button type="button" class="ico-btn" data-tab="recent" onclick="switchTab('recent')">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span class="ico-lbl">Riwayat</span>
        </button>
        <button type="button" class="ico-btn" onclick="refreshAll()" title="Muat Ulang Data">
            <i class="fa-solid fa-arrows-rotate" id="refreshIcon"></i>
            <span class="ico-lbl">Refresh</span>
        </button>
        @if($user && $user->canManageStore($store->id))
            <a href="{{ route('store.backend.dashboard', $store->slug) }}" class="ico-btn" title="Kembali ke Panel Admin">
                <i class="fa-solid fa-gauge"></i>
                <span class="ico-lbl">Admin</span>
            </a>
        @endif
        <form action="{{ route('store.backend.logout', $store->slug) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="ico-btn btn-logout" title="Keluar Akun">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span class="ico-lbl">Keluar</span>
            </button>
        </form>
    </nav>
</header>

{{-- TAB 1: POS MENU KASIR --}}
<section id="tabPos">
    <div class="pos-layout">
        {{-- Left: Search + Categories + Active Menus --}}
        <div>
            <div class="bg-white border rounded-4 p-3 mb-3 shadow-sm">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="search" id="menuSearchInput" class="form-control border-start-0"
                               placeholder="Cari menu aktif..." oninput="renderMenus()">
                    </div>
                    <button type="button" class="ico-btn" onclick="clearSearch()" title="Hapus Pencarian">
                        <i class="fa-solid fa-eraser"></i>
                        <span class="ico-lbl">Hapus</span>
                    </button>
                </div>

                {{-- Full-Icon Category Pills --}}
                <div class="cat-strip" id="categoryStrip"></div>
            </div>

            <div id="menuGrid" class="menu-grid"></div>
        </div>

        {{-- Right: Cart & Payment Panel (Tanpa Data Customer) --}}
        <aside class="pos-cart-panel" id="cartPanel">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-light">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
                         style="width:36px;height:36px;background:var(--pos-accent);">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>
                    <div>
                        <div class="fw-bold small">Keranjang Kasir</div>
                        <div class="text-muted" style="font-size:11px;" id="cartSubtitle">0 item · Walk-in</div>
                    </div>
                </div>
                <div class="d-flex gap-1">
                    <button type="button" class="ico-btn btn-danger-soft" onclick="clearCart()" title="Kosongkan Keranjang">
                        <i class="fa-solid fa-trash-can"></i>
                        <span class="ico-lbl">Reset</span>
                    </button>
                    <button type="button" class="ico-btn d-lg-none" onclick="toggleMobileCart(false)">
                        <i class="fa-solid fa-xmark"></i>
                        <span class="ico-lbl">Tutup</span>
                    </button>
                </div>
            </div>

            <div class="cart-items-scroll" id="cartItemsList"></div>

            <div class="p-3 border-top bg-white" style="padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0px)) !important;">
                <div class="mb-2">
                    <input type="text" id="orderNotesInput" class="form-control form-control-sm"
                           placeholder="Catatan pesanan (opsional, misal: tanpa pedas)">
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-semibold text-muted">Total Tagihan</span>
                    <span class="fs-5 fw-bold font-mono text-dark" id="cartTotalText">Rp 0</span>
                </div>

                {{-- Langsung 2 tombol checkout: Cash (Uang+Tangan) & QRIS (QR saja) --}}
                <div class="pay-grid">
                    <button type="button" class="ico-btn btn-cash-solid w-100" id="btnPayCash" onclick="submitCheckout('cash')">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                        <span class="ico-lbl">Cash</span>
                    </button>
                    <button type="button" class="ico-btn btn-qris-solid w-100" id="btnPayQris" onclick="submitCheckout('qris')">
                        <i class="fa-solid fa-qrcode"></i>
                        <span class="ico-lbl">QRIS</span>
                    </button>
                </div>
            </div>
        </aside>
    </div>
</section>

{{-- TAB 2: KDS KASIR (RINGKAS: LANGSUNG PACKAGING -> DONE) --}}
<section id="tabKds" style="display:none;max-width:1480px;margin:0 auto;padding:14px 16px;">
    <div class="bg-white border rounded-4 p-3 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
                 style="width:40px;height:40px;background:linear-gradient(135deg,#f59e0b,#d97706);">
                <i class="fa-solid fa-boxes-packing"></i>
            </div>
            <div>
                <div class="fw-bold">KDS Kasir (Packaging &rarr; Done)</div>
                <div class="small text-muted">Alur ringkas kasir: langsung klik <strong>Packaging</strong>, lalu klik <strong>Done</strong> saat pesanan diterima pelanggan.</div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="ico-btn" onclick="fetchKds()">
                <i class="fa-solid fa-arrows-rotate"></i>
                <span class="ico-lbl">Segarkan</span>
            </button>
        </div>
    </div>

    <div id="kdsGrid" class="kds-grid"></div>
</section>

{{-- TAB 3: RIWAYAT TRANSAKSI KASIR --}}
<section id="tabRecent" style="display:none;max-width:1280px;margin:0 auto;padding:14px 16px;">
    <div class="bg-white border rounded-4 p-3 mb-3 d-flex align-items-center justify-content-between shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
                 style="width:40px;height:40px;background:var(--pos-accent);">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div>
                <div class="fw-bold">Transaksi Kasir Terbaru</div>
                <div class="small text-muted">Klik Lunas untuk transaksi Cash atau buka ulang QRIS yang belum terbayar.</div>
            </div>
        </div>
        <button type="button" class="ico-btn" onclick="fetchRecent()">
            <i class="fa-solid fa-arrows-rotate"></i>
            <span class="ico-lbl">Segarkan</span>
        </button>
    </div>

    <div id="recentList" class="row g-3"></div>
</section>

{{-- Mobile Floating Cart Bar --}}
<div class="mobile-cart-fab" id="mobileCartFab" onclick="toggleMobileCart(true)">
    <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-bag-shopping fs-5 text-warning"></i>
        <div>
            <div class="fw-bold small" id="mobileCartCount">0 item</div>
            <div class="font-mono fw-bold" id="mobileCartTotal">Rp 0</div>
        </div>
    </div>
    <span class="ico-btn btn-cash-solid py-1 px-3">
        <i class="fa-solid fa-cash-register"></i>
        <span class="ico-lbl">Bayar</span>
    </span>
</div>

{{-- MODAL: PILIH OPSI / TOPPING MENU --}}
<div class="modal fade" id="modalMenuOptions" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom">
                <div>
                    <h6 class="modal-title fw-bold mb-0" id="optModalTitle">Pilih Opsi Menu</h6>
                    <div class="small font-mono text-primary fw-bold" id="optModalBasePrice">Rp 0</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="optModalList" class="d-flex flex-column gap-2 mb-3"></div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Catatan Item (Opsional)</label>
                    <input type="text" id="optModalNote" class="form-control form-control-sm" placeholder="Contoh: sambal pisah, ekstra nasi">
                </div>
                <div class="d-flex align-items-center justify-content-between bg-light rounded-3 p-2">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="ico-btn" style="min-width:44px;padding:6px;" onclick="stepOptQty(-1)">
                            <i class="fa-solid fa-minus"></i>
                            <span class="ico-lbl">Kurang</span>
                        </button>
                        <span class="fw-bold fs-5 font-mono px-2" id="optModalQty">1</span>
                        <button type="button" class="ico-btn" style="min-width:44px;padding:6px;" onclick="stepOptQty(1)">
                            <i class="fa-solid fa-plus"></i>
                            <span class="ico-lbl">Tambah</span>
                        </button>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">Subtotal</div>
                        <div class="fw-bold font-mono fs-5 text-dark" id="optModalSubtotal">Rp 0</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top justify-content-between">
                <button type="button" class="ico-btn" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark"></i>
                    <span class="ico-lbl">Batal</span>
                </button>
                <button type="button" class="ico-btn active px-4" onclick="confirmAddOptionMenu()">
                    <i class="fa-solid fa-cart-plus"></i>
                    <span class="ico-lbl">Tambah</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: PEMBAYARAN CASH (KASIR KLIK LUNAS) --}}
<div class="modal fade" id="modalCashPay" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header text-white" style="background:linear-gradient(135deg,#10b981,#059669);">
                <div>
                    <h6 class="modal-title fw-bold mb-0"><i class="fa-solid fa-hand-holding-dollar me-2"></i>Konfirmasi Cash (Tunai)</h6>
                    <div class="small opacity-75 font-mono" id="cashModalOrderNo">#POS</div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center">
                <div class="small text-muted text-uppercase fw-bold">Total Pembayaran</div>
                <div class="fs-2 fw-extrabold font-mono text-dark mb-3" id="cashModalTotal">Rp 0</div>

                <div class="text-start mb-2">
                    <label class="form-label small fw-semibold">Uang Diterima (Opsional untuk hitung kembalian)</label>
                    <input type="number" id="cashTenderInput" class="form-control font-mono fw-bold" placeholder="Masukkan nominal uang..." oninput="calcCashChange()">
                </div>

                <div class="d-flex flex-wrap gap-2 justify-content-center mb-3" id="cashQuickBtns"></div>

                <div class="rounded-3 p-2 bg-light border mb-2">
                    <div class="small text-muted">Kembalian</div>
                    <div class="fs-5 fw-bold font-mono text-success" id="cashChangeText">Rp 0</div>
                </div>
                <div class="small text-muted">
                    Setelah uang diterima, klik <strong>Lunas</strong> agar notifikasi pembelian terkirim ke Admin Toko.
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="ico-btn" data-bs-dismiss="modal">
                    <i class="fa-solid fa-clock"></i>
                    <span class="ico-lbl">Nanti</span>
                </button>
                <button type="button" class="ico-btn btn-cash-solid px-4" id="btnConfirmCashLunas" onclick="confirmCashLunas()">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <span class="ico-lbl" style="font-size:12px;">Lunas</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL: PEMBAYARAN QRIS MIDTRANS --}}
<div class="modal fade" id="modalQrisPay" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header text-white" style="background:linear-gradient(135deg,#0ea5e9,#0284c7);">
                <div>
                    <h6 class="modal-title fw-bold mb-0"><i class="fa-solid fa-qrcode me-2"></i>QRIS Midtrans</h6>
                    <div class="small opacity-75 font-mono" id="qrisModalOrderNo">#POS</div>
                </div>
                <button type="button" class="btn-close btn-close-white" onclick="closeQrisModal()" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center">
                <div class="small text-muted text-uppercase fw-bold">Total Tagihan QRIS</div>
                <div class="fs-2 fw-extrabold font-mono text-dark mb-2" id="qrisModalTotal">Rp 0</div>

                <div id="qrisImageWrap" class="my-3 d-flex flex-column align-items-center justify-content-center"></div>

                <div id="qrisStatusBanner" class="alert alert-info py-2 px-3 small mb-0">
                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Menunggu pelanggan scan &amp; bayar QRIS...
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="ico-btn" onclick="closeQrisModal()">
                    <i class="fa-solid fa-xmark"></i>
                    <span class="ico-lbl">Tutup</span>
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="ico-btn btn-qris" id="btnOpenQrisSnap" style="display:none;" onclick="openQrisExternal()">
                        <i class="fa-solid fa-qrcode"></i>
                        <span class="ico-lbl">Buka QR</span>
                    </button>
                    <button type="button" class="ico-btn btn-qris-solid px-3" onclick="pollQrisStatus(true)">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        <span class="ico-lbl">Cek Status</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="pos-toast-box" id="toastBox"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const ROUTES = {
        menus: @json(route('store.backend.cashier.menus', $store->slug)),
        createOrder: @json(route('store.backend.cashier.orders.store', $store->slug)),
        recent: @json(route('store.backend.cashier.orders.recent', $store->slug)),
        kds: @json(route('store.backend.cashier.kds', $store->slug)),
        markPaid: (id) => @json(route('store.backend.cashier.orders.markPaid', [$store->slug, '__ID__'])).replace('__ID__', id),
        syncMidtrans: (id) => @json(route('store.backend.cashier.orders.syncMidtrans', [$store->slug, '__ID__'])).replace('__ID__', id),
        kdsStatus: (id) => @json(route('store.backend.cashier.kds.status', [$store->slug, '__ID__'])).replace('__ID__', id),
    };

    let catalog = @json($catalog);
    let kdsOrders = @json($initialKds);
    let recentOrders = @json($initialRecent);

    let activeTab = 'pos';
    let selectedCategoryId = 'all';
    let paymentMode = 'cash';
    let cart = [];

    let optionDraft = null;
    let activeCashOrder = null;
    let activeQrisOrder = null;
    let qrisPollTimer = null;

    const modalOptions = new bootstrap.Modal(document.getElementById('modalMenuOptions'));
    const modalCash = new bootstrap.Modal(document.getElementById('modalCashPay'));
    const modalQris = new bootstrap.Modal(document.getElementById('modalQrisPay'));

    function rupiah(n) {
        return 'Rp ' + Math.round(n || 0).toLocaleString('id-ID');
    }

    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    function toast(msg, type = 'ok') {
        const box = document.getElementById('toastBox');
        const el = document.createElement('div');
        el.className = 'pos-toast ' + type;
        el.innerHTML = `<i class="fa-solid ${type === 'err' ? 'fa-circle-exclamation' : 'fa-circle-check'}"></i><span>${esc(msg)}</span>`;
        box.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    }

    function categoryIcon(name) {
        const lower = (name || '').toLowerCase();
        if (lower.includes('minum') || lower.includes('drink') || lower.includes('teh') || lower.includes('kopi') || lower.includes('es')) return 'fa-mug-hot';
        if (lower.includes('snack') || lower.includes('cemil') || lower.includes('side') || lower.includes('goreng')) return 'fa-cookie-bite';
        if (lower.includes('paket') || lower.includes('combo') || lower.includes('box')) return 'fa-box-open';
        if (lower.includes('bowl') || lower.includes('nasi') || lower.includes('rice')) return 'fa-bowl-rice';
        return 'fa-utensils';
    }

    /* ==================== NAVIGATION ==================== */
    window.switchTab = function (tab) {
        activeTab = tab;
        document.getElementById('tabPos').style.display = tab === 'pos' ? '' : 'none';
        document.getElementById('tabKds').style.display = tab === 'kds' ? '' : 'none';
        document.getElementById('tabRecent').style.display = tab === 'recent' ? '' : 'none';

        document.querySelectorAll('[data-tab]').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));

        if (tab === 'kds') fetchKds();
        if (tab === 'recent') fetchRecent();
        updateMobileFab();
    };

    window.refreshAll = async function () {
        const ico = document.getElementById('refreshIcon');
        ico.classList.add('fa-spin');
        try {
            await Promise.all([fetchCatalog(), fetchKds(), fetchRecent()]);
            toast('Data kasir diperbarui');
        } finally {
            setTimeout(() => ico.classList.remove('fa-spin'), 350);
        }
    };

    async function fetchCatalog() {
        const r = await fetch(ROUTES.menus, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (r.ok) {
            const j = await r.json();
            if (j.data) {
                catalog = j.data;
                renderCategories();
                renderMenus();
            }
        }
    }

    /* ==================== CATEGORIES & ACTIVE MENUS ==================== */
    function renderCategories() {
        const strip = document.getElementById('categoryStrip');
        const cats = catalog.categories || [];
        let html = `<button type="button" class="ico-btn ${selectedCategoryId === 'all' ? 'active' : ''}" onclick="selectCategory('all')">
            <i class="fa-solid fa-layer-group"></i>
            <span class="ico-lbl">Semua</span>
        </button>`;
        cats.forEach(c => {
            const active = String(selectedCategoryId) === String(c.id);
            html += `<button type="button" class="ico-btn ${active ? 'active' : ''}" onclick="selectCategory(${c.id})">
                <i class="fa-solid ${categoryIcon(c.name)}"></i>
                <span class="ico-lbl">${esc(c.name.length > 12 ? c.name.slice(0, 12) + '…' : c.name)}</span>
            </button>`;
        });
        strip.innerHTML = html;
    }

    window.selectCategory = function (id) {
        selectedCategoryId = id;
        renderCategories();
        renderMenus();
    };

    window.clearSearch = function () {
        document.getElementById('menuSearchInput').value = '';
        renderMenus();
    };

    window.renderMenus = function () {
        const grid = document.getElementById('menuGrid');
        const q = (document.getElementById('menuSearchInput').value || '').trim().toLowerCase();
        const menus = (catalog.menus || []).filter(m => {
            if (!m.is_active) return false;
            if (selectedCategoryId !== 'all' && String(m.category_id) !== String(selectedCategoryId)) return false;
            if (q && !m.name.toLowerCase().includes(q) && !(m.category_name || '').toLowerCase().includes(q)) return false;
            return true;
        });

        if (!menus.length) {
            grid.innerHTML = `<div class="col-12 bg-white border rounded-4 p-5 text-center text-muted" style="grid-column:1/-1;">
                <i class="fa-solid fa-bowl-food fa-2x mb-2 opacity-25 d-block"></i>
                <div class="fw-bold">Tidak ada menu aktif yang cocok</div>
            </div>`;
            return;
        }

        const qtyByMenu = {};
        cart.forEach(it => { qtyByMenu[it.menu_id] = (qtyByMenu[it.menu_id] || 0) + it.quantity; });

        grid.innerHTML = menus.map(m => {
            const qty = qtyByMenu[m.id] || 0;
            const hasOpts = (m.options || []).length > 0;
            const thumb = m.image_url
                ? `<img src="${esc(m.image_url)}" alt="${esc(m.name)}" loading="lazy">`
                : `<i class="fa-solid fa-bowl-food fa-2x text-secondary opacity-25"></i>`;

            return `<div class="menu-card" onclick="onMenuTap(${m.id})">
                <div class="menu-thumb">
                    ${thumb}
                    ${qty > 0 ? `<span class="menu-qty-pill">${qty}x</span>` : ''}
                    ${hasOpts ? `<span class="menu-opt-pill"><i class="fa-solid fa-sliders me-1"></i>Opsi</span>` : ''}
                </div>
                <div class="menu-body">
                    <div class="menu-title">${esc(m.name)}</div>
                    <div class="d-flex align-items-center justify-content-between mt-auto pt-1">
                        <div class="menu-price">${rupiah(m.price)}</div>
                        <span class="badge rounded-pill" style="background:var(--pos-accent-soft);color:var(--pos-accent);">
                            <i class="fa-solid fa-plus"></i>
                        </span>
                    </div>
                </div>
            </div>`;
        }).join('');
    };

    window.onMenuTap = function (menuId) {
        const menu = (catalog.menus || []).find(m => m.id === menuId);
        if (!menu) return;

        if ((menu.options || []).length > 0) {
            openOptionModal(menu);
            return;
        }

        addToCart({
            key: `${menu.id}::`,
            menu_id: menu.id,
            name: menu.name,
            base_price: menu.price,
            unit_price: menu.price,
            quantity: 1,
            selected_options: [],
            special_notes: '',
        });
    };

    /* ==================== OPTION MODAL ==================== */
    function openOptionModal(menu) {
        optionDraft = { menu, selectedIds: new Set(), qty: 1 };
        document.getElementById('optModalTitle').textContent = menu.name;
        document.getElementById('optModalBasePrice').textContent = rupiah(menu.price);
        document.getElementById('optModalNote').value = '';
        document.getElementById('optModalQty').textContent = '1';

        const list = document.getElementById('optModalList');
        list.innerHTML = (menu.options || []).map(o => `
            <label class="d-flex align-items-center justify-content-between p-2 border rounded-3 bg-light" style="cursor:pointer;">
                <div class="d-flex align-items-center gap-2">
                    <input type="checkbox" class="form-check-input mt-0" onchange="toggleOpt(${o.id}, this.checked)">
                    <div>
                        <div class="fw-semibold small">${esc(o.name)}</div>
                        <div class="text-muted" style="font-size:10px;text-transform:uppercase;">${esc(o.type || 'opsi')}</div>
                    </div>
                </div>
                <span class="font-mono small fw-bold text-primary">${o.price > 0 ? '+' + rupiah(o.price) : 'Gratis'}</span>
            </label>
        `).join('');

        updateOptSubtotal();
        modalOptions.show();
    }

    window.toggleOpt = function (optId, checked) {
        if (!optionDraft) return;
        if (checked) optionDraft.selectedIds.add(optId);
        else optionDraft.selectedIds.delete(optId);
        updateOptSubtotal();
    };

    window.stepOptQty = function (delta) {
        if (!optionDraft) return;
        optionDraft.qty = Math.max(1, optionDraft.qty + delta);
        document.getElementById('optModalQty').textContent = String(optionDraft.qty);
        updateOptSubtotal();
    };

    function updateOptSubtotal() {
        if (!optionDraft) return;
        const opts = (optionDraft.menu.options || []).filter(o => optionDraft.selectedIds.has(o.id));
        const unit = optionDraft.menu.price + opts.reduce((s, o) => s + (o.price || 0), 0);
        document.getElementById('optModalSubtotal').textContent = rupiah(unit * optionDraft.qty);
    }

    window.confirmAddOptionMenu = function () {
        if (!optionDraft) return;
        const m = optionDraft.menu;
        const opts = (m.options || []).filter(o => optionDraft.selectedIds.has(o.id));
        const note = (document.getElementById('optModalNote').value || '').trim();
        const unit = m.price + opts.reduce((s, o) => s + (o.price || 0), 0);
        const key = `${m.id}::${opts.map(o => o.id).sort().join(',')}::${note}`;

        addToCart({
            key,
            menu_id: m.id,
            name: m.name,
            base_price: m.price,
            unit_price: unit,
            quantity: optionDraft.qty,
            selected_options: opts.map(o => ({ id: o.id, name: o.name, price: o.price, type: o.type })),
            special_notes: note,
        });
        modalOptions.hide();
    };

    /* ==================== CART ==================== */
    function addToCart(entry) {
        const existing = cart.find(c => c.key === entry.key);
        if (existing) existing.quantity += entry.quantity;
        else cart.push(entry);
        renderCart();
        renderMenus();
    }

    window.stepCartQty = function (idx, delta) {
        if (!cart[idx]) return;
        cart[idx].quantity += delta;
        if (cart[idx].quantity <= 0) cart.splice(idx, 1);
        renderCart();
        renderMenus();
    };

    window.clearCart = function () {
        cart = [];
        document.getElementById('orderNotesInput').value = '';
        renderCart();
        renderMenus();
    };

    window.toggleMobileCart = function (open) {
        document.getElementById('cartPanel').classList.toggle('mobile-open', !!open);
    };

    function cartTotal() {
        return cart.reduce((s, it) => s + (it.unit_price * it.quantity), 0);
    }

    function cartCount() {
        return cart.reduce((s, it) => s + it.quantity, 0);
    }

    function updateMobileFab() {
        const fab = document.getElementById('mobileCartFab');
        const count = cartCount();
        fab.classList.toggle('visible', activeTab === 'pos' && count > 0);
        document.getElementById('mobileCartCount').textContent = `${count} item`;
        document.getElementById('mobileCartTotal').textContent = rupiah(cartTotal());
    }

    function renderCart() {
        const list = document.getElementById('cartItemsList');
        const count = cartCount();
        const total = cartTotal();

        document.getElementById('cartSubtitle').textContent = `${count} item · Walk-in Kasir`;
        document.getElementById('cartTotalText').textContent = rupiah(total);
        updateMobileFab();

        if (!cart.length) {
            list.innerHTML = `<div class="text-center py-5 text-muted">
                <i class="fa-solid fa-basket-shopping fa-2x mb-2 opacity-25 d-block"></i>
                <div class="small fw-semibold">Keranjang masih kosong</div>
                <div style="font-size:11px;">Ketuk menu aktif untuk menambahkan</div>
            </div>`;
            return;
        }

        list.innerHTML = cart.map((it, idx) => {
            const optsText = (it.selected_options || []).map(o => o.name).join(', ');
            return `<div class="cart-row">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-bold small text-dark">${esc(it.name)}</div>
                        ${optsText ? `<div class="text-muted" style="font-size:11px;">+ ${esc(optsText)}</div>` : ''}
                        ${it.special_notes ? `<div class="text-primary" style="font-size:11px;">Catatan: ${esc(it.special_notes)}</div>` : ''}
                    </div>
                    <div class="font-mono fw-bold small">${rupiah(it.unit_price * it.quantity)}</div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="text-muted font-mono" style="font-size:11px;">${rupiah(it.unit_price)} / porsi</span>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="ico-btn" style="min-width:34px;padding:4px;" onclick="stepCartQty(${idx}, -1)">
                            <i class="fa-solid ${it.quantity === 1 ? 'fa-trash-can text-danger' : 'fa-minus'}" style="font-size:.8rem;"></i>
                        </button>
                        <span class="fw-bold font-mono px-2 small">${it.quantity}</span>
                        <button type="button" class="ico-btn" style="min-width:34px;padding:4px;" onclick="stepCartQty(${idx}, 1)">
                            <i class="fa-solid fa-plus" style="font-size:.8rem;"></i>
                        </button>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    /* ==================== DIRECT 2-BUTTON CHECKOUT (CASH / QRIS) ==================== */
    window.submitCheckout = async function (mode) {
        paymentMode = mode || 'cash';
        if (!cart.length) {
            toast('Pilih menu terlebih dahulu', 'err');
            return;
        }
        if (paymentMode === 'qris' && !catalog.store?.has_midtrans) {
            toast('QRIS Midtrans belum dikonfigurasi pada Pengaturan Toko.', 'err');
            return;
        }

        const btnCash = document.getElementById('btnPayCash');
        const btnQris = document.getElementById('btnPayQris');
        if (btnCash) btnCash.disabled = true;
        if (btnQris) btnQris.disabled = true;
        try {
            const payload = {
                payment_mode: paymentMode,
                mark_paid: false,
                notes: (document.getElementById('orderNotesInput').value || '').trim() || null,
                items: cart.map(it => ({
                    menu_id: it.menu_id,
                    quantity: it.quantity,
                    selected_options: it.selected_options,
                    special_notes: it.special_notes || null,
                })),
            };

            const res = await fetch(ROUTES.createOrder, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            const json = await res.json();
            if (!res.ok || !json.success) {
                toast(json.message || 'Gagal memproses pesanan kasir.', 'err');
                return;
            }

            const order = json.order;
            clearCart();
            toggleMobileCart(false);
            fetchKds();
            fetchRecent();

            if (paymentMode === 'cash') {
                openCashModal(order);
            } else {
                openQrisModal(order, json.qris || null);
            }
        } catch (e) {
            toast('Kendala koneksi ke server.', 'err');
        } finally {
            if (btnCash) btnCash.disabled = false;
            if (btnQris) btnQris.disabled = false;
        }
    };

    function findOrderById(id) {
        return kdsOrders.find(x => x.id === id) || recentOrders.find(x => x.id === id) || null;
    }

    window.openCashById = function (id) {
        const o = findOrderById(id);
        if (o) openCashModal(o);
    };

    window.openQrisById = function (id) {
        const o = findOrderById(id);
        if (o) openQrisModal(o, null);
    };

    /* ==================== CASH MODAL (KLIK LUNAS) ==================== */
    window.openCashModal = function (order) {
        activeCashOrder = order;
        document.getElementById('cashModalOrderNo').textContent = '#' + order.order_number;
        document.getElementById('cashModalTotal').textContent = rupiah(order.total_amount);
        document.getElementById('cashTenderInput').value = Math.round(order.total_amount || 0);

        const total = Math.round(order.total_amount || 0);
        const candidates = [total, 20000, 50000, 100000].filter((v, i, arr) => v >= total && arr.indexOf(v) === i);
        document.getElementById('cashQuickBtns').innerHTML = candidates.map((amt, i) => `
            <button type="button" class="ico-btn" onclick="setCashTender(${amt})">
                <i class="fa-solid fa-money-bill-wave text-success"></i>
                <span class="ico-lbl">${i === 0 ? 'Uang Pas' : (amt / 1000) + 'rb'}</span>
            </button>
        `).join('');

        calcCashChange();
        modalCash.show();
    };

    window.setCashTender = function (amt) {
        document.getElementById('cashTenderInput').value = amt;
        calcCashChange();
    };

    window.calcCashChange = function () {
        if (!activeCashOrder) return;
        const paid = parseFloat(document.getElementById('cashTenderInput').value || '0') || 0;
        const diff = paid - (activeCashOrder.total_amount || 0);
        const el = document.getElementById('cashChangeText');
        el.textContent = diff >= 0 ? rupiah(diff) : 'Kurang ' + rupiah(Math.abs(diff));
        el.className = 'fs-5 fw-bold font-mono ' + (diff >= 0 ? 'text-success' : 'text-danger');
    };

    window.confirmCashLunas = async function () {
        if (!activeCashOrder) return;
        const btn = document.getElementById('btnConfirmCashLunas');
        btn.disabled = true;
        try {
            const ok = await markOrderCashPaid(activeCashOrder.id);
            if (ok) modalCash.hide();
        } finally {
            btn.disabled = false;
        }
    };

    window.markOrderCashPaid = async function (orderId) {
        try {
            const res = await fetch(ROUTES.markPaid(orderId), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                credentials: 'same-origin',
            });
            const j = await res.json();
            if (!res.ok || !j.success) {
                toast(j.message || 'Gagal menandai lunas.', 'err');
                return false;
            }
            toast('Cash Lunas! Notifikasi terkirim ke Admin Toko.');
            fetchKds();
            fetchRecent();
            return true;
        } catch (e) {
            toast('Kendala koneksi saat konfirmasi Lunas.', 'err');
            return false;
        }
    };

    /* ==================== QRIS MIDTRANS MODAL ==================== */
    window.openQrisModal = function (order, qrisInfo) {
        activeQrisOrder = order;
        stopQrisPolling();

        document.getElementById('qrisModalOrderNo').textContent = '#' + order.order_number;
        document.getElementById('qrisModalTotal').textContent = rupiah(order.total_amount);

        const wrap = document.getElementById('qrisImageWrap');
        const snapBtn = document.getElementById('btnOpenQrisSnap');
        const banner = document.getElementById('qrisStatusBanner');

        const qrisUrl = (qrisInfo && qrisInfo.qris_url) || order.qris_url;
        const snapToken = (qrisInfo && qrisInfo.snap_token) || order.snap_token;
        const paymentUrl = (qrisInfo && qrisInfo.payment_url) || order.payment_url;

        banner.className = 'alert alert-info py-2 px-3 small mb-0';
        banner.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Menunggu pelanggan scan &amp; bayar QRIS Midtrans...';

        if (qrisUrl) {
            wrap.innerHTML = `<div class="p-3 bg-white border rounded-4 shadow-sm d-inline-block">
                <img src="${esc(qrisUrl)}" alt="QRIS Midtrans" style="width:240px;height:240px;object-fit:contain;">
            </div>
            <div class="small text-muted mt-2"><i class="fa-solid fa-qrcode me-1 text-primary"></i>Scan menggunakan GoPay, OVO, DANA, ShopeePay, atau Mobile Banking</div>`;
            snapBtn.style.display = 'none';
        } else {
            wrap.innerHTML = `<div class="p-4 bg-light border rounded-4 text-center">
                <i class="fa-solid fa-qrcode fa-4x text-primary mb-2 d-block"></i>
                <div class="fw-bold small">Jendela QRIS Midtrans Siap</div>
                <div class="small text-muted">Gunakan tombol <strong>Buka QR</strong> bila jendela Midtrans tidak terbuka otomatis.</div>
            </div>`;
            snapBtn.style.display = (snapToken || paymentUrl) ? '' : 'none';

            if (snapToken && window.snap && typeof window.snap.pay === 'function') {
                try {
                    window.snap.pay(snapToken, {
                        onSuccess: () => pollQrisStatus(true),
                        onPending: () => pollQrisStatus(false),
                        onClose: () => pollQrisStatus(false),
                    });
                } catch (e) {}
            } else if (paymentUrl) {
                window.open(paymentUrl, '_blank', 'noopener');
            }
        }

        modalQris.show();
        qrisPollTimer = setInterval(() => pollQrisStatus(false), 3000);
    };

    window.openQrisExternal = function () {
        if (!activeQrisOrder) return;
        if (activeQrisOrder.snap_token && window.snap && typeof window.snap.pay === 'function') {
            window.snap.pay(activeQrisOrder.snap_token, {
                onSuccess: () => pollQrisStatus(true),
                onPending: () => pollQrisStatus(false),
            });
        } else if (activeQrisOrder.payment_url) {
            window.open(activeQrisOrder.payment_url, '_blank', 'noopener');
        }
    };

    function stopQrisPolling() {
        if (qrisPollTimer) {
            clearInterval(qrisPollTimer);
            qrisPollTimer = null;
        }
    }

    window.closeQrisModal = function () {
        stopQrisPolling();
        modalQris.hide();
    };

    window.pollQrisStatus = async function (showFeedback) {
        if (!activeQrisOrder) return;
        try {
            const res = await fetch(ROUTES.syncMidtrans(activeQrisOrder.id), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                credentials: 'same-origin',
            });
            const j = await res.json();
            if (j.is_paid) {
                stopQrisPolling();
                const banner = document.getElementById('qrisStatusBanner');
                banner.className = 'alert alert-success py-2 px-3 small fw-bold mb-0';
                banner.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i> PEMBAYARAN QRIS LUNAS! Notifikasi terkirim ke Admin Toko.';
                toast('Pembayaran QRIS Midtrans Lunas!');
                fetchKds();
                fetchRecent();
                setTimeout(() => modalQris.hide(), 1800);
            } else if (showFeedback) {
                toast(j.message || 'Belum ada pembayaran masuk.', 'err');
            }
        } catch (e) {
            if (showFeedback) toast('Gagal memeriksa status Midtrans.', 'err');
        }
    };

    /* ==================== KDS KASIR (PACKAGING -> DONE) ==================== */
    window.fetchKds = async function () {
        try {
            const r = await fetch(ROUTES.kds, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (r.ok) {
                const j = await r.json();
                kdsOrders = j.data || [];
                renderKds();
            }
        } catch (e) {}
    };

    function renderKds() {
        const badge = document.getElementById('kdsBadge');
        badge.textContent = kdsOrders.length > 99 ? '99+' : String(kdsOrders.length);
        badge.style.display = kdsOrders.length > 0 ? '' : 'none';

        const grid = document.getElementById('kdsGrid');
        if (!kdsOrders.length) {
            grid.innerHTML = `<div class="bg-white border rounded-4 p-5 text-center text-muted" style="grid-column:1/-1;">
                <i class="fa-solid fa-circle-check fa-3x text-success opacity-25 mb-2 d-block"></i>
                <div class="fw-bold">Semua pesanan sudah selesai!</div>
                <div class="small">Pesanan aktif dari Kasir maupun Online akan langsung tampil di sini.</div>
            </div>`;
            return;
        }

        grid.innerHTML = kdsOrders.map(o => {
            const isPackaging = o.status === 'Order On Packaging';
            const isPaid = o.payment_status === 'paid';
            const itemsHtml = (o.items || []).map(it => {
                const opts = (it.selected_options || []).map(x => typeof x === 'object' ? x.name : x).filter(Boolean).join(', ');
                return `<div class="d-flex justify-content-between py-1 border-bottom border-light small">
                    <div>
                        <span class="fw-bold">${it.quantity}x</span> ${esc(it.menu_name)}
                        ${opts ? `<div class="text-muted" style="font-size:11px;">+ ${esc(opts)}</div>` : ''}
                        ${it.special_notes ? `<div class="text-primary" style="font-size:11px;">Catatan: ${esc(it.special_notes)}</div>` : ''}
                    </div>
                </div>`;
            }).join('');

            return `<div class="kds-card ${isPackaging ? 'stage-packaging' : ''}">
                <div class="kds-card-top">
                    <div>
                        <div class="font-mono fw-bold small">#${esc(o.order_number)}</div>
                        <div style="font-size:11px;" class="text-muted">${esc(o.customer_name)} · ${esc(o.created_human || '')}</div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1">
                        <span class="badge ${isPackaging ? 'bg-warning text-dark' : 'bg-secondary'}">${isPackaging ? 'PACKAGING' : 'ANTRIAN'}</span>
                        <span class="badge ${isPaid ? 'bg-success' : 'bg-danger'}">${isPaid ? 'LUNAS' : 'BELUM LUNAS'}</span>
                    </div>
                </div>
                <div class="p-3 flex-grow-1">
                    ${itemsHtml}
                    ${o.notes ? `<div class="small text-muted mt-2"><i class="fa-solid fa-note-sticky me-1"></i>${esc(o.notes)}</div>` : ''}
                </div>
                <div class="p-2 px-3 bg-light border-top d-flex align-items-center justify-content-between gap-2">
                    <div class="font-mono fw-bold small">${rupiah(o.total_amount)}</div>
                    <div class="d-flex gap-2">
                        ${(!isPaid && o.is_cashier_cash) ? `
                            <button type="button" class="ico-btn btn-cash" onclick="openCashById(${o.id})">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                                <span class="ico-lbl">Lunas</span>
                            </button>` : ''}
                        ${(!isPaid && o.is_cashier_qris) ? `
                            <button type="button" class="ico-btn btn-qris" onclick="openQrisById(${o.id})">
                                <i class="fa-solid fa-qrcode"></i>
                                <span class="ico-lbl">QRIS</span>
                            </button>` : ''}
                        ${!isPackaging ? `
                            <button type="button" class="ico-btn btn-pack" onclick="advanceKds(${o.id}, 'Order On Packaging')">
                                <i class="fa-solid fa-box-open"></i>
                                <span class="ico-lbl">Packaging</span>
                            </button>` : `
                            <button type="button" class="ico-btn btn-done" onclick="advanceKds(${o.id}, 'Order Ready')">
                                <i class="fa-solid fa-circle-check"></i>
                                <span class="ico-lbl">Done</span>
                            </button>`}
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    window.advanceKds = async function (orderId, targetStatus) {
        try {
            const res = await fetch(ROUTES.kdsStatus(orderId), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ status: targetStatus }),
            });
            const j = await res.json();
            if (!res.ok || !j.success) {
                toast(j.message || 'Gagal memperbarui status KDS.', 'err');
                return;
            }
            toast(targetStatus === 'Order Ready' ? 'Pesanan selesai (Done)!' : 'Pesanan masuk Packaging!');
            fetchKds();
            fetchRecent();
        } catch (e) {
            toast('Kendala koneksi KDS.', 'err');
        }
    };

    /* ==================== RIWAYAT KASIR ==================== */
    window.fetchRecent = async function () {
        try {
            const r = await fetch(ROUTES.recent, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (r.ok) {
                const j = await r.json();
                recentOrders = j.data || [];
                renderRecent();
            }
        } catch (e) {}
    };

    function renderRecent() {
        const box = document.getElementById('recentList');
        if (!recentOrders.length) {
            box.innerHTML = `<div class="col-12"><div class="bg-white border rounded-4 p-5 text-center text-muted">
                <i class="fa-solid fa-receipt fa-2x opacity-25 mb-2 d-block"></i>
                <div class="fw-bold">Belum ada transaksi kasir hari ini</div>
            </div></div>`;
            return;
        }

        box.innerHTML = recentOrders.map(o => {
            const isPaid = o.payment_status === 'paid';
            const summary = (o.items || []).map(i => `${i.quantity}x ${i.menu_name}`).join(', ');
            return `<div class="col-md-6">
                <div class="bg-white border rounded-4 p-3 shadow-sm h-100 d-flex flex-column justify-content-between gap-2">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="font-mono fw-bold small">#${esc(o.order_number)}</div>
                            <div class="text-muted" style="font-size:11px;">${esc(o.created_human || '')} · ${esc(o.payment_method || '')}</div>
                        </div>
                        <span class="badge ${isPaid ? 'bg-success' : 'bg-warning text-dark'}">${isPaid ? 'LUNAS' : 'BELUM LUNAS'}</span>
                    </div>
                    <div class="small text-secondary">${esc(summary)}</div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                        <div>
                            <div class="font-mono fw-bold">${rupiah(o.total_amount)}</div>
                            <div style="font-size:11px;" class="text-muted">Status: ${esc(o.status)}</div>
                        </div>
                        <div class="d-flex gap-2">
                            ${(!isPaid && o.is_cashier_cash) ? `
                                <button type="button" class="ico-btn btn-cash-solid" onclick="openCashById(${o.id})">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                    <span class="ico-lbl">Lunas</span>
                                </button>` : ''}
                            ${(!isPaid && o.is_cashier_qris) ? `
                                <button type="button" class="ico-btn btn-qris-solid" onclick="openQrisById(${o.id})">
                                    <i class="fa-solid fa-qrcode"></i>
                                    <span class="ico-lbl">QRIS</span>
                                </button>` : ''}
                            ${isPaid ? `
                                <span class="ico-btn btn-cash" style="pointer-events:none;">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span class="ico-lbl">Lunas</span>
                                </span>` : ''}
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    /* ==================== INIT ==================== */
    renderCategories();
    renderMenus();
    renderCart();
    renderKds();
    renderRecent();

    setInterval(() => {
        if (!document.hidden && activeTab === 'kds') fetchKds();
    }, 10000);
})();
</script>
</body>
</html>
