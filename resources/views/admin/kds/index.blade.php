<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=2">
    <title>Kitchen Display System (KDS) - Naumil Rice</title>

    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Pusher JS for Real-Time WebSocket (Laravel Reverb) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.3.0/pusher.min.js"></script>

    <style>
        :root {
            --bg-kds: #080d19;
            --panel-bg: rgba(16, 24, 40, 0.88);
            --card-kds: #152033;
            --border-kds: #1e293b;
            --header-kds: #0e1626;
            --font-family: 'Plus Jakarta Sans', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;

            --col-confirmed: #6366f1;
            --col-processing: #0284c7;
            --col-packaging: #d97706;
            --col-ready: #10b981;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            height: 100vh;
            height: 100dvh;
            margin: 0;
            padding: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            user-select: none;
            -webkit-user-select: none;
            background-color: var(--bg-kds);
            color: #f1f5f9;
            font-family: var(--font-family);
        }

        /* Top Bar */
        .kds-header {
            background-color: var(--header-kds);
            border-bottom: 2px solid var(--border-kds);
            padding: 0.55rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 100;
            flex: 0 0 auto;
            gap: 1rem;
        }

        /* Mobile & Tablet Stage Navigation Tabs */
        .kds-stage-nav {
            display: none;
            gap: 0.5rem;
            padding: 0.45rem 0.85rem;
            background: rgba(14, 22, 38, 0.98);
            border-bottom: 1px solid var(--border-kds);
            overflow-x: auto;
            flex: 0 0 auto;
            scrollbar-width: none;
        }
        .kds-stage-nav::-webkit-scrollbar {
            display: none;
        }
        .stage-nav-btn {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #cbd5e1;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
        }
        .stage-nav-btn:hover, .stage-nav-btn.active {
            background: #0284c7;
            color: #ffffff;
            border-color: #38bdf8;
        }

        /* Responsive Kanban Board (Rigidly Locked to Screen Height) */
        .kds-board {
            flex: 1 1 0;
            min-height: 0;
            height: 0;
            display: grid;
            grid-template-columns: repeat(4, minmax(260px, 1fr));
            grid-template-rows: minmax(0, 1fr);
            gap: 0.85rem;
            padding: 0.75rem 1rem 0.85rem 1rem;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-behavior: smooth;
        }

        /* Individual Stage Panel */
        .kds-panel {
            background: var(--panel-bg);
            backdrop-filter: blur(14px);
            border: 1px solid var(--border-kds);
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
            min-height: 0;
            height: 100%;
            max-height: 100%;
            transition: border-color 0.2s;
        }

        .kds-panel-header {
            padding: 0.65rem 0.95rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid;
            flex: 0 0 auto;
        }

        /* Specific Panel Header Themes */
        .panel-confirmed { border-top: 3px solid var(--col-confirmed); }
        .panel-confirmed .kds-panel-header {
            border-color: rgba(99, 102, 241, 0.4);
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.12) 0%, rgba(16, 24, 40, 0.95) 100%);
        }
        .panel-confirmed .panel-title-icon { color: #818cf8; }

        .panel-processing { border-top: 3px solid var(--col-processing); }
        .panel-processing .kds-panel-header {
            border-color: rgba(2, 132, 199, 0.4);
            background: linear-gradient(180deg, rgba(2, 132, 199, 0.12) 0%, rgba(16, 24, 40, 0.95) 100%);
        }
        .panel-processing .panel-title-icon { color: #38bdf8; }

        .panel-packaging { border-top: 3px solid var(--col-packaging); }
        .panel-packaging .kds-panel-header {
            border-color: rgba(217, 119, 6, 0.4);
            background: linear-gradient(180deg, rgba(217, 119, 6, 0.12) 0%, rgba(16, 24, 40, 0.95) 100%);
        }
        .panel-packaging .panel-title-icon { color: #fbbf24; }

        .panel-ready { border-top: 3px solid var(--col-ready); }
        .panel-ready .kds-panel-header {
            border-color: rgba(16, 185, 129, 0.4);
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.12) 0%, rgba(16, 24, 40, 0.95) 100%);
        }
        .panel-ready .panel-title-icon { color: #34d399; }

        /* Panel Scrollable Body (Always scrolls smoothly inside its panel) */
        .kds-panel-body {
            padding: 0.75rem 0.65rem;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            overscroll-behavior-y: contain;
            flex: 1 1 0;
            min-height: 0;
            height: 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            transition: background-color 0.2s, border 0.2s;

            /* Firefox Scrollbar Support */
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.35) rgba(0, 0, 0, 0.25);
        }

        /* WebKit Scrollbar Support (Chrome, Edge, Safari) */
        .kds-panel-body::-webkit-scrollbar {
            width: 7px;
        }
        .kds-panel-body::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.25);
            border-radius: 4px;
        }
        .kds-panel-body::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.32);
            border-radius: 4px;
            border: 1px solid rgba(0, 0, 0, 0.2);
        }
        .kds-panel-body::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.55);
        }

        /* Drag Over Highlight for Kanban Panels */
        .kds-panel-body.drag-over {
            background: rgba(56, 189, 248, 0.12) !important;
            border: 2px dashed #38bdf8 !important;
            border-radius: 12px;
        }
        .panel-confirmed .kds-panel-body.drag-over {
            background: rgba(99, 102, 241, 0.15) !important;
            border-color: #818cf8 !important;
        }
        .panel-processing .kds-panel-body.drag-over {
            background: rgba(2, 132, 199, 0.15) !important;
            border-color: #38bdf8 !important;
        }
        .panel-packaging .kds-panel-body.drag-over {
            background: rgba(217, 119, 6, 0.15) !important;
            border-color: #fbbf24 !important;
        }
        .panel-ready .kds-panel-body.drag-over {
            background: rgba(16, 185, 129, 0.15) !important;
            border-color: #34d399 !important;
        }

        /* Elegant Order Card */
        .kds-order-card {
            background: var(--card-kds);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.2s ease, opacity 0.2s ease;
            cursor: grab;
            touch-action: pan-y;
            flex-shrink: 0;
        }

        .kds-order-card:active {
            cursor: grabbing;
        }

        .kds-order-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5);
            border-color: rgba(255, 255, 255, 0.22);
            transform: translateY(-2px);
        }

        .kds-order-card.is-dragging {
            opacity: 0.45;
            transform: scale(0.96) rotate(1.5deg);
            border: 2px dashed #38bdf8;
            box-shadow: 0 14px 30px rgba(0, 0, 0, 0.6);
        }

        /* Top accent line based on status */
        .kds-order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }
        .card-confirmed::before { background: var(--col-confirmed); }
        .card-processing::before { background: var(--col-processing); }
        .card-packaging::before { background: var(--col-packaging); }
        .card-delivery::before { background: #06b6d4; }
        .card-ready::before { background: var(--col-ready); }

        /* Drag Handle Grip Icon */
        .drag-handle-grip {
            cursor: grab;
            color: rgba(255, 255, 255, 0.35);
            padding: 2px 4px;
            border-radius: 4px;
            transition: color 0.15s, background-color 0.15s;
        }
        .drag-handle-grip:hover {
            color: #38bdf8;
            background-color: rgba(56, 189, 248, 0.15);
        }

        /* Badge Nomor Antrean di Kiri Atas (#1, #2 tanpa tulisan Antrean) */
        .kds-queue-pill {
            background: #ef4444;
            color: #ffffff;
            font-family: var(--font-mono);
            font-weight: 800;
            font-size: 0.9rem;
            padding: 2px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.45);
            letter-spacing: -0.02em;
            flex-shrink: 0;
        }

        /* Card Header */
        .kds-card-head {
            padding: 0.6rem 0.8rem 0.5rem 0.8rem;
            background: rgba(0, 0, 0, 0.22);
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.5rem;
        }

        /* Card Content - FULL TEXT, NEVER TRUNCATED */
        .kds-card-content {
            padding: 0.65rem 0.8rem;
            flex: 1;
            font-size: 0.88rem;
            line-height: 1.4;
            word-break: break-word;
            white-space: normal;
        }

        /* Items Container - If order has many items, scrolls smoothly */
        .kds-items-scrollable {
            max-height: 190px;
            overflow-y: auto;
            overscroll-behavior-y: contain;
            padding-right: 4px;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.25) transparent;
        }
        .kds-items-scrollable::-webkit-scrollbar {
            width: 4px;
        }
        .kds-items-scrollable::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 4px;
        }

        .kds-menu-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 0.4rem 0;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
        }
        .kds-menu-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .kds-menu-qty {
            background: #0284c7;
            color: #ffffff;
            font-family: var(--font-mono);
            font-weight: 800;
            font-size: 0.85rem;
            padding: 2px 6px;
            border-radius: 5px;
            min-width: 26px;
            text-align: center;
            flex-shrink: 0;
            box-shadow: 0 2px 5px rgba(2, 132, 199, 0.4);
        }

        .kds-menu-name {
            font-weight: 700;
            color: #ffffff;
            font-size: 0.92rem;
            line-height: 1.35;
            word-break: break-word;
            white-space: normal;
        }

        .kds-card-actions {
            padding: 0.6rem 0.8rem;
            background-color: rgba(0, 0, 0, 0.25);
            border-top: 1px solid rgba(255, 255, 255, 0.07);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        /* Smooth Movement Transitions */
        @keyframes cardSlideOut {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(0.96) translateX(15px); opacity: 0.4; }
            100% { transform: scale(0.9) translateX(35px); opacity: 0; max-height: 0; padding: 0; margin: 0; }
        }

        @keyframes cardSlideIn {
            0% { transform: scale(0.92) translateY(-15px); opacity: 0; }
            60% { transform: scale(1.02) translateY(2px); opacity: 0.9; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }

        @keyframes cardPulseGlow {
            0% { box-shadow: 0 0 0 3px #38bdf8; }
            100% { box-shadow: 0 4px 18px rgba(0, 0, 0, 0.35); }
        }

        .anim-slide-out {
            animation: cardSlideOut 0.26s forwards cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
        }

        .anim-slide-in {
            animation: cardSlideIn 0.32s forwards cubic-bezier(0.16, 1, 0.3, 1), cardPulseGlow 1.2s ease-out;
        }

        /* Empty State */
        .kds-empty-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            color: #64748b;
            border: 2px dashed rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            text-align: center;
            margin: auto 0;
            font-size: 0.88rem;
        }

        .live-dot {
            width: 10px;
            height: 10px;
            background-color: #22c55e;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 10px #22c55e;
            animation: pulse-live 1.5s infinite;
        }

        @keyframes pulse-live {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }

        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .kds-stage-nav {
                display: flex;
            }
            .kds-board {
                display: flex;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                -webkit-overflow-scrolling: touch;
                gap: 0.85rem;
                padding: 0.65rem 0.75rem 0.75rem 0.75rem;
            }
            .kds-panel {
                flex: 0 0 310px;
                min-width: 310px;
                scroll-snap-align: start;
                height: 100%;
                max-height: 100%;
            }
        }

        @media (max-width: 768px) {
            .kds-header {
                flex-direction: column;
                align-items: stretch;
                gap: 0.45rem;
                padding: 0.5rem 0.75rem;
            }
            .kds-board {
                padding: 0.5rem;
                gap: 0.65rem;
            }
            .kds-panel {
                flex: 0 0 calc(100vw - 1.5rem);
                min-width: calc(100vw - 1.5rem);
                scroll-snap-align: center;
            }
            .kds-card-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- Top Navigation Bar -->
<header class="kds-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary text-white" title="Dashboard">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-fire-burner text-danger fs-4"></i>
            <div>
                <h5 class="mb-0 fw-bold font-monospace text-uppercase" style="letter-spacing: 0.05em;">KITCHEN DISPLAY SYSTEM</h5>
                <small class="text-secondary font-mono">Pusat Antrean Dapur & Pengemasan</small>
            </div>
        </div>
    </div>

    <!-- Center: Store Selector -->
    <div class="d-flex align-items-center gap-2">
        <select id="storeSelector" class="form-select form-select-sm bg-dark text-light border-secondary" style="min-width: 240px;">
            <option value="">Semua Cabang Dapur</option>
            @foreach($stores as $st)
                <option value="{{ $st->id }}" {{ ($selectedStore && $selectedStore->id == $st->id) ? 'selected' : '' }}>
                    {{ $st->name }} ({{ $st->code }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="d-flex align-items-center gap-3 flex-wrap">
        <!-- WebSocket Realtime Status Indicator -->
        <span id="wsStatusBadge" class="badge bg-secondary font-mono px-2 py-1">
            <i class="fa-solid fa-circle-notch fa-spin me-1"></i> Menghubungkan WS...
        </span>

        <div class="d-flex align-items-center gap-2">
            <span class="live-dot"></span>
            <span class="badge bg-danger fs-6 px-3 py-1 font-mono" id="queueCountBadge">0 Total</span>
        </div>

        <button id="soundToggleBtn" class="btn btn-sm btn-outline-warning" title="Toggle Suara Notifikasi">
            <i class="fa-solid fa-volume-high" id="soundIcon"></i>
        </button>

        <button id="fullscreenBtn" class="btn btn-sm btn-outline-light" title="Layar Penuh">
            <i class="fa-solid fa-expand"></i> Fullscreen
        </button>

        <div class="font-monospace fw-bold fs-5 text-warning" id="liveClock">00:00:00</div>
    </div>
</header>

<!-- Stage Navigation Bar for Mobile / Tablet Viewports -->
<nav class="kds-stage-nav" id="kdsStageNav">
    <button class="stage-nav-btn active" onclick="scrollToStage('confirmed')" id="tabBtn-confirmed">
        <i class="fa-solid fa-receipt me-1"></i>1. Diterima <span class="badge ms-1 px-2" id="tabCount-confirmed" style="background-color: #4f46e5;">0</span>
    </button>
    <button class="stage-nav-btn" onclick="scrollToStage('processing')" id="tabBtn-processing">
        <i class="fa-solid fa-fire-burner me-1"></i>2. Dimasak <span class="badge ms-1 px-2" id="tabCount-processing" style="background-color: #0284c7;">0</span>
    </button>
    <button class="stage-nav-btn" onclick="scrollToStage('packaging')" id="tabBtn-packaging">
        <i class="fa-solid fa-box-open me-1"></i>3. Kemas <span class="badge ms-1 px-2" id="tabCount-packaging" style="background-color: #d97706;">0</span>
    </button>
    <button class="stage-nav-btn" onclick="scrollToStage('ready')" id="tabBtn-ready">
        <i class="fa-solid fa-bell-concierge me-1"></i>4. Siap <span class="badge ms-1 px-2" id="tabCount-ready" style="background-color: #10b981;">0</span>
    </button>
</nav>

<!-- Global Notification Toast Banner -->
<div id="kdsToast" class="position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg px-4 py-2 rounded-pill font-mono fw-bold fs-7" style="display: none; z-index: 99999;"></div>

<!-- 4-Stage Kanban Board with Drag & Drop Dropzones -->
<main class="kds-board" id="kdsBoard">
    <!-- Panel 1: Diterima / Antrean Baru -->
    <div class="kds-panel panel-confirmed" id="panel-confirmed">
        <div class="kds-panel-header">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-receipt fs-5 panel-title-icon"></i>
                <div>
                    <h6 class="mb-0 fw-bold font-monospace text-uppercase text-white" style="letter-spacing: 0.05em;">1. DITERIMA</h6>
                    <small class="text-white-50" style="font-size: 0.72rem;">Tarik ke sini untuk antrean baru</small>
                </div>
            </div>
            <span class="badge bg-indigo fs-6 font-mono px-2 py-1" id="count-confirmed" style="background-color: #4f46e5;">0</span>
        </div>
        <div class="kds-panel-body" 
             id="panelBody-confirmed" 
             data-status="Order Konfirmed"
             ondragover="handlePanelDragOver(event, this)"
             ondragenter="handlePanelDragEnter(event, this)"
             ondragleave="handlePanelDragLeave(event, this)"
             ondrop="handlePanelDrop(event, this, 'Order Konfirmed')">
            <!-- Cards injected via JavaScript -->
        </div>
    </div>

    <!-- Panel 2: Sedang Dimasak -->
    <div class="kds-panel panel-processing" id="panel-processing">
        <div class="kds-panel-header">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-fire-burner fs-5 panel-title-icon"></i>
                <div>
                    <h6 class="mb-0 fw-bold font-monospace text-uppercase text-white" style="letter-spacing: 0.05em;">2. SEDANG DIMASAK</h6>
                    <small class="text-white-50" style="font-size: 0.72rem;">Tarik ke sini untuk mulai masak</small>
                </div>
            </div>
            <span class="badge fs-6 font-mono px-2 py-1" id="count-processing" style="background-color: #0284c7;">0</span>
        </div>
        <div class="kds-panel-body" 
             id="panelBody-processing" 
             data-status="Order On Proses"
             ondragover="handlePanelDragOver(event, this)"
             ondragenter="handlePanelDragEnter(event, this)"
             ondragleave="handlePanelDragLeave(event, this)"
             ondrop="handlePanelDrop(event, this, 'Order On Proses')">
            <!-- Cards injected via JavaScript -->
        </div>
    </div>

    <!-- Panel 3: Pengemasan (Packaging) -->
    <div class="kds-panel panel-packaging" id="panel-packaging">
        <div class="kds-panel-header">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-box-open fs-5 panel-title-icon"></i>
                <div>
                    <h6 class="mb-0 fw-bold font-monospace text-uppercase text-white" style="letter-spacing: 0.05em;">3. PENGEMASAN</h6>
                    <small class="text-white-50" style="font-size: 0.72rem;">Tarik ke sini untuk packing</small>
                </div>
            </div>
            <span class="badge fs-6 font-mono px-2 py-1" id="count-packaging" style="background-color: #d97706;">0</span>
        </div>
        <div class="kds-panel-body" 
             id="panelBody-packaging" 
             data-status="Order On Packaging"
             ondragover="handlePanelDragOver(event, this)"
             ondragenter="handlePanelDragEnter(event, this)"
             ondragleave="handlePanelDragLeave(event, this)"
             ondrop="handlePanelDrop(event, this, 'Order On Packaging')">
            <!-- Cards injected via JavaScript -->
        </div>
    </div>

    <!-- Panel 4: Siap / Pengantaran (Ready / Delivery) -->
    <div class="kds-panel panel-ready" id="panel-ready">
        <div class="kds-panel-header">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-check fs-5 panel-title-icon"></i>
                <div>
                    <h6 class="mb-0 fw-bold font-monospace text-uppercase text-white" style="letter-spacing: 0.05em;">4. SIAP / DELIVERY</h6>
                    <small class="text-white-50" style="font-size: 0.72rem;">Tarik ke sini untuk selesai</small>
                </div>
            </div>
            <span class="badge fs-6 font-mono px-2 py-1" id="count-ready" style="background-color: #10b981;">0</span>
        </div>
        <div class="kds-panel-body" 
             id="panelBody-ready" 
             data-status="Order Ready"
             ondragover="handlePanelDragOver(event, this)"
             ondragenter="handlePanelDragEnter(event, this)"
             ondragleave="handlePanelDragLeave(event, this)"
             ondrop="handlePanelDrop(event, this, 'Order Ready')">
            <!-- Cards injected via JavaScript -->
        </div>
    </div>
</main>

<script>
    let soundEnabled = true;
    let audioCtx = null;
    let toastTimeout = null;

    // Toast helper
    function showKdsToast(message, type = 'info') {
        const toast = document.getElementById('kdsToast');
        if (!toast) return;
        if (toastTimeout) clearTimeout(toastTimeout);

        let bgClass = 'bg-primary text-white';
        if (type === 'success') bgClass = 'bg-success text-white border border-light';
        if (type === 'warning') bgClass = 'bg-warning text-dark border border-dark';
        if (type === 'danger') bgClass = 'bg-danger text-white border border-light';
        if (type === 'info') bgClass = 'bg-info text-dark border border-dark';

        toast.className = `position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg px-4 py-2 rounded-pill font-mono fw-bold fs-7 ${bgClass}`;
        toast.innerHTML = message;
        toast.style.display = 'block';

        toastTimeout = setTimeout(() => {
            toast.style.display = 'none';
        }, 4000);
    }

    // Audio synthesizer for audio alert
    function playBeep() {
        if (!soundEnabled) return;
        try {
            if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.3);
        } catch (e) {
            console.error('Audio play error', e);
        }
    }

    // Live Clock
    function updateClock() {
        const now = new Date();
        document.getElementById('liveClock').innerText = now.toLocaleTimeString('id-ID');
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Fullscreen Toggle
    document.getElementById('fullscreenBtn').addEventListener('click', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            if (document.exitFullscreen) document.exitFullscreen();
        }
    });

    // Sound Toggle
    document.getElementById('soundToggleBtn').addEventListener('click', () => {
        soundEnabled = !soundEnabled;
        const icon = document.getElementById('soundIcon');
        if (soundEnabled) {
            icon.className = 'fa-solid fa-volume-high';
            playBeep();
        } else {
            icon.className = 'fa-solid fa-volume-xmark';
        }
    });

    // In-memory orders store
    let allOrders = [];
    let isInitialLoad = true;

    // Helper: Map status to panel container ID
    function formatWaitingMinutes(minutes) {
        if (minutes === undefined || minutes === null) return 'Baru saja';
        const m = Math.abs(parseInt(minutes, 10));
        if (isNaN(m) || m === 0) return 'Baru saja';
        if (m < 60) return `${m}m lalu`;
        const hours = Math.floor(m / 60);
        const rem = m % 60;
        return rem > 0 ? `${hours}j ${rem}m lalu` : `${hours}j lalu`;
    }

    function scrollToStage(stage) {
        const el = document.getElementById(`panel-${stage}`);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
            document.querySelectorAll('.stage-nav-btn').forEach(b => b.classList.remove('active'));
            const activeBtn = document.getElementById(`tabBtn-${stage}`);
            if (activeBtn) activeBtn.classList.add('active');
        }
    }

    function getPanelBodyId(status) {
        switch (status) {
            case 'Order Konfirmed': return 'panelBody-confirmed';
            case 'Order On Proses': return 'panelBody-processing';
            case 'Order On Packaging': return 'panelBody-packaging';
            case 'Order On Delivery':
            case 'Order Ready': return 'panelBody-ready';
            default: return null;
        }
    }

    // Helper: Map status to card class
    function getCardStageClass(status) {
        switch (status) {
            case 'Order Konfirmed': return 'card-confirmed';
            case 'Order On Proses': return 'card-processing';
            case 'Order On Packaging': return 'card-packaging';
            case 'Order On Delivery': return 'card-delivery';
            case 'Order Ready': return 'card-ready';
            default: return '';
        }
    }

    // Generate Action Buttons HTML according to status
    function getActionButtonsHtml(order) {
        switch (order.status) {
            case 'Order Konfirmed':
                return `
                    <button class="btn btn-sm btn-primary fw-bold" onclick="handleMoveStatus(this, ${order.id}, 'Order On Proses', 'Mulai Masak')">
                        <i class="fa-solid fa-fire-burner me-1"></i> Mulai Masak
                    </button>
                    <button class="btn btn-sm btn-outline-danger fw-bold" onclick="handleRejectClick(this, ${order.id}, '${order.order_number}')">
                        <i class="fa-solid fa-ban me-1"></i> Tolak
                    </button>
                `;
            case 'Order On Proses':
                return `
                    <button class="btn btn-sm btn-warning text-dark fw-bold" style="grid-column: span 2;" onclick="handleMoveStatus(this, ${order.id}, 'Order On Packaging', 'Masuk Packaging')">
                        <i class="fa-solid fa-box-open me-1"></i> Masuk Kemasan
                    </button>
                `;
            case 'Order On Packaging':
                return `
                    <button class="btn btn-sm btn-info text-dark fw-bold" onclick="handleMoveStatus(this, ${order.id}, 'Order On Delivery', 'Kirim Delivery')">
                        <i class="fa-solid fa-motorcycle me-1"></i> Kirim
                    </button>
                    <button class="btn btn-sm btn-success fw-bold" onclick="handleMoveStatus(this, ${order.id}, 'Order Ready', 'Selesai Ready')">
                        <i class="fa-solid fa-check-double me-1"></i> Siap (Ready)
                    </button>
                `;
            case 'Order On Delivery':
                return `
                    <button class="btn btn-sm btn-success fw-bold" style="grid-column: span 2;" onclick="handleMoveStatus(this, ${order.id}, 'Order Ready', 'Selesai')">
                        <i class="fa-solid fa-circle-check me-1"></i> Selesai (Ready)
                    </button>
                `;
            case 'Order Ready':
                return `
                    <div class="badge bg-success-subtle text-success border border-success p-2 w-100 fs-7" style="grid-column: span 2;">
                        <i class="fa-solid fa-circle-check me-1"></i> Siap Disajikan / Diambil
                    </div>
                `;
            default:
                return '';
        }
    }

    // Build the complete HTML of a card (Text FULL, NEVER truncated, with Drag & Drop attributes)
    function createOrderCardHtml(order) {
        // Menu Items list with FULL text
        const itemsHtml = (order.items || []).map(item => {
            let optionsHtml = '';
            if (item.selected_options && item.selected_options.length > 0) {
                optionsHtml = item.selected_options.map(opt => `
                    <span class="badge bg-secondary bg-opacity-50 text-light me-1 mb-1 font-monospace" style="white-space: normal; text-align: left;">+ ${opt.name}</span>
                `).join('');
            }

            return `
                <div class="kds-menu-item">
                    <span class="kds-menu-qty">${item.quantity}x</span>
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="kds-menu-name">${item.menu_name}</div>
                        ${optionsHtml ? `<div class="mt-1 d-flex flex-wrap">${optionsHtml}</div>` : ''}
                        ${item.special_notes ? `
                            <div class="text-warning small mt-1" style="white-space: normal; line-height: 1.35;">
                                <i class="fa-solid fa-note-sticky me-1"></i>${item.special_notes}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');

        // Badges: Paid / Unpaid
        const paymentBadgeHtml = order.payment_status === 'paid'
            ? '<span class="badge bg-success font-mono"><i class="fa-solid fa-circle-check me-1"></i>Paid</span>'
            : '<span class="badge bg-warning text-dark font-mono"><i class="fa-solid fa-clock me-1"></i>Unpaid</span>';

        // Bukti QRIS Link if exists
        const proofLinkHtml = order.payment_proof_url
            ? `<a href="${order.payment_proof_url}" target="_blank" class="badge bg-info text-white text-decoration-none"><i class="fa-solid fa-image me-1"></i>Bukti</a>`
            : '';

        // Badges di atas kiri per informasi order saja (Tanpa kata "Antrean", cukup "#1", "#2")
        const queuePillHtml = `<span class="kds-queue-pill">#${order.queue_number || order.id}</span>`;

        return `
            <div class="kds-order-card ${getCardStageClass(order.status)}" 
                 id="orderCard-${order.id}" 
                 data-order-id="${order.id}" 
                 draggable="true"
                 ondragstart="handleCardDragStart(event, ${order.id})"
                 ondragend="handleCardDragEnd(event, ${order.id})"
                 ontouchstart="handleTouchStart(event, ${order.id})"
                 ontouchmove="handleTouchMove(event)"
                 ontouchend="handleTouchEnd(event)">
                 
                <!-- Card Head -->
                <div class="kds-card-head">
                    <div class="d-flex align-items-center gap-2" style="min-width: 0;">
                        <i class="fa-solid fa-grip-vertical drag-handle-grip" title="Tahan & geser untuk memindahkan status"></i>
                        ${queuePillHtml}
                        <div style="min-width: 0;">
                            <span class="fw-bold font-monospace fs-6 text-white d-block">#${order.order_number.slice(-8)}</span>
                            <div class="small fw-bold text-info text-truncate" style="max-width: 170px;">
                                ${order.client ? order.client.name : 'Pelanggan'}
                            </div>
                            ${order.store ? `<small class="text-white-50 font-mono d-block" style="font-size: 0.72rem;">${order.store.name}</small>` : ''}
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <span class="badge bg-black bg-opacity-60 text-secondary font-mono small px-2 py-1 mb-1 d-inline-block">
                            <i class="fa-regular fa-clock me-1"></i>${formatWaitingMinutes(order.waiting_minutes)}
                        </span>
                        <div class="d-flex align-items-center justify-content-end gap-1 flex-wrap">
                            ${paymentBadgeHtml}
                            ${proofLinkHtml}
                        </div>
                    </div>
                </div>

                <!-- Card Content (Full text, with scrollable wrap if items are numerous) -->
                <div class="kds-card-content">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom border-secondary border-opacity-25 small font-mono">
                        <span class="text-white-50">${order.items ? order.items.length : 0} Menu</span>
                        <span class="badge bg-warning text-dark fw-bold">${order.total_portions || 1} Porsi</span>
                    </div>

                    ${order.notes ? `
                        <div class="p-2 mb-2 rounded bg-warning bg-opacity-10 border border-warning border-opacity-25 text-warning small" style="line-height: 1.35; white-space: normal;">
                            <i class="fa-solid fa-note-sticky me-1"></i><strong>Catatan:</strong> ${order.notes}
                        </div>
                    ` : ''}

                    <div class="kds-items-scrollable d-flex flex-column gap-2">
                        ${itemsHtml}
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="kds-card-actions" id="cardActions-${order.id}">
                    ${getActionButtonsHtml(order)}
                </div>
            </div>
        `;
    }

    // ==========================================
    // KANBAN DRAG AND DROP (MOUSE & DESKTOP)
    // ==========================================
    let draggedOrderId = null;

    function handleCardDragStart(e, orderId) {
        draggedOrderId = orderId;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(orderId));
        
        const card = document.getElementById(`orderCard-${orderId}`);
        if (card) {
            setTimeout(() => card.classList.add('is-dragging'), 10);
        }
    }

    function handleCardDragEnd(e, orderId) {
        draggedOrderId = null;
        const card = document.getElementById(`orderCard-${orderId}`);
        if (card) {
            card.classList.remove('is-dragging');
        }
        document.querySelectorAll('.kds-panel-body').forEach(panel => {
            panel.classList.remove('drag-over');
        });
    }

    function handlePanelDragOver(e, panelEl) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        if (!panelEl.classList.contains('drag-over')) {
            panelEl.classList.add('drag-over');
        }
        // Auto-scroll when dragging near top or bottom edge of panel
        const rect = panelEl.getBoundingClientRect();
        const y = e.clientY;
        if (y - rect.top < 60) {
            panelEl.scrollTop -= 14;
        } else if (rect.bottom - y < 60) {
            panelEl.scrollTop += 14;
        }
    }

    function handlePanelDragEnter(e, panelEl) {
        e.preventDefault();
        panelEl.classList.add('drag-over');
    }

    function handlePanelDragLeave(e, panelEl) {
        if (!panelEl.contains(e.relatedTarget)) {
            panelEl.classList.remove('drag-over');
        }
    }

    async function handlePanelDrop(e, panelEl, targetStatus) {
        e.preventDefault();
        panelEl.classList.remove('drag-over');

        const orderIdStr = e.dataTransfer ? e.dataTransfer.getData('text/plain') : String(draggedOrderId);
        const orderId = parseInt(orderIdStr || draggedOrderId, 10);
        if (!orderId) return;

        const order = allOrders.find(o => o.id === orderId);
        if (!order) return;

        if (order.status === targetStatus) return;

        showKdsToast(`<i class="fa-solid fa-arrows-turn-to-dots me-1"></i> Menggeser pesanan #${order.order_number.slice(-8)} ke "${targetStatus}"...`, 'info');
        
        // Optimistic UI move
        smoothMoveCardToPanel(orderId, targetStatus);

        // Async API Patch
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/api/v1/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ status: targetStatus, note: 'Diperbarui via Drag & Drop Kanban Admin' })
            });

            const json = await res.json();
            if (json.success) {
                playBeep();
                showKdsToast(`<i class="fa-solid fa-circle-check me-1"></i> Berhasil! Pesanan #${order.order_number.slice(-8)} dipindahkan ke "${targetStatus}".`, 'success');
            } else {
                showKdsToast(`Gagal: ${json.message || 'Terjadi kesalahan sistem'}`, 'danger');
                await fetchKdsOrders();
            }
        } catch (err) {
            console.error('Drag drop error', err);
            showKdsToast('Gagal menghubungi server.', 'danger');
            await fetchKdsOrders();
        }
    }

    // ==========================================
    // TOUCH DRAG AND DROP (KITCHEN TABLETS / IPADS)
    // ==========================================
    let touchDraggedCard = null;
    let touchDraggedOrderId = null;
    let touchClone = null;
    let touchStartX = 0;
    let touchStartY = 0;
    let isTouchDragging = false;

    function handleTouchStart(e, orderId) {
        if (e.target.closest('button') || e.target.closest('a')) return;
        const touch = e.touches[0];
        touchStartX = touch.clientX;
        touchStartY = touch.clientY;
        touchDraggedOrderId = orderId;
        touchDraggedCard = document.getElementById(`orderCard-${orderId}`);
        isTouchDragging = false;
    }

    function handleTouchMove(e) {
        if (!touchDraggedCard) return;
        const touch = e.touches[0];
        const diffX = Math.abs(touch.clientX - touchStartX);
        const diffY = Math.abs(touch.clientY - touchStartY);

        if (!isTouchDragging && (diffX > 15 || diffY > 15)) {
            isTouchDragging = true;
            touchDraggedCard.classList.add('is-dragging');

            touchClone = touchDraggedCard.cloneNode(true);
            touchClone.style.position = 'fixed';
            touchClone.style.pointerEvents = 'none';
            touchClone.style.width = touchDraggedCard.offsetWidth + 'px';
            touchClone.style.zIndex = '99999';
            touchClone.style.opacity = '0.85';
            touchClone.style.transform = 'scale(0.95) rotate(2deg)';
            touchClone.style.boxShadow = '0 12px 30px rgba(0,0,0,0.6)';
            document.body.appendChild(touchClone);
        }

        if (isTouchDragging && touchClone) {
            e.preventDefault();
            touchClone.style.left = (touch.clientX - 100) + 'px';
            touchClone.style.top = (touch.clientY - 40) + 'px';

            const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);
            const panelBody = elementBelow?.closest('.kds-panel-body');
            document.querySelectorAll('.kds-panel-body').forEach(pb => {
                if (pb === panelBody) {
                    pb.classList.add('drag-over');
                    const pbRect = pb.getBoundingClientRect();
                    if (touch.clientY - pbRect.top < 60) {
                        pb.scrollTop -= 14;
                    } else if (pbRect.bottom - touch.clientY < 60) {
                        pb.scrollTop += 14;
                    }
                } else {
                    pb.classList.remove('drag-over');
                }
            });
        }
    }

    function handleTouchEnd(e) {
        if (!touchDraggedCard) return;
        if (isTouchDragging) {
            touchDraggedCard.classList.remove('is-dragging');
            if (touchClone) {
                touchClone.remove();
                touchClone = null;
            }

            const touch = e.changedTouches[0];
            const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);
            const panelBody = elementBelow?.closest('.kds-panel-body');
            
            document.querySelectorAll('.kds-panel-body').forEach(pb => pb.classList.remove('drag-over'));

            if (panelBody && panelBody.dataset.status && touchDraggedOrderId) {
                const targetStatus = panelBody.dataset.status;
                const order = allOrders.find(o => o.id === touchDraggedOrderId);
                if (order && order.status !== targetStatus) {
                    handlePanelDrop({
                        preventDefault: () => {},
                        dataTransfer: null
                    }, panelBody, targetStatus);
                }
            }
        }

        touchDraggedCard = null;
        touchDraggedOrderId = null;
        isTouchDragging = false;
    }

    // ==========================================
    // UI RECONCILIATION & SMOOTH PANEL MOVEMENT
    // ==========================================
    function updatePanelCounts() {
        const countConfirmed = allOrders.filter(o => o.status === 'Order Konfirmed').length;
        const countProcessing = allOrders.filter(o => o.status === 'Order On Proses').length;
        const countPackaging = allOrders.filter(o => o.status === 'Order On Packaging').length;
        const countReady = allOrders.filter(o => o.status === 'Order On Delivery' || o.status === 'Order Ready').length;

        document.getElementById('count-confirmed').innerText = countConfirmed;
        document.getElementById('count-processing').innerText = countProcessing;
        document.getElementById('count-packaging').innerText = countPackaging;
        document.getElementById('count-ready').innerText = countReady;

        // Update mobile/tablet navigation tabs
        const tabConfirmed = document.getElementById('tabCount-confirmed');
        if (tabConfirmed) tabConfirmed.innerText = countConfirmed;
        const tabProcessing = document.getElementById('tabCount-processing');
        if (tabProcessing) tabProcessing.innerText = countProcessing;
        const tabPackaging = document.getElementById('tabCount-packaging');
        if (tabPackaging) tabPackaging.innerText = countPackaging;
        const tabReady = document.getElementById('tabCount-ready');
        if (tabReady) tabReady.innerText = countReady;

        document.getElementById('queueCountBadge').innerText = `${allOrders.length} Total`;
    }

    function checkEmptyStates() {
        const panels = [
            { id: 'panelBody-confirmed', title: 'Belum ada antrean baru', icon: 'fa-receipt' },
            { id: 'panelBody-processing', title: 'Tidak ada yang sedang dimasak', icon: 'fa-fire-burner' },
            { id: 'panelBody-packaging', title: 'Tidak ada yang dikemas', icon: 'fa-box-open' },
            { id: 'panelBody-ready', title: 'Belum ada pesanan siap', icon: 'fa-circle-check' }
        ];

        panels.forEach(p => {
            const container = document.getElementById(p.id);
            if (!container) return;
            const cards = container.querySelectorAll('.kds-order-card');
            const existingEmpty = container.querySelector('.kds-empty-panel');

            if (cards.length === 0 && !existingEmpty) {
                container.innerHTML = `
                    <div class="kds-empty-panel">
                        <i class="fa-solid ${p.icon} fa-2x mb-2 opacity-50"></i>
                        <div>${p.title}</div>
                    </div>
                `;
            } else if (cards.length > 0 && existingEmpty) {
                existingEmpty.remove();
            }
        });
    }

    function recalculateQueueNumbers() {
        let activeNum = 1;
        allOrders.forEach(order => {
            const isActiveCooking = ['Order Konfirmed', 'Order On Proses', 'Order On Packaging'].includes(order.status);
            if (isActiveCooking) {
                order.queue_number = activeNum++;
            } else {
                order.queue_number = null;
            }
            const card = document.getElementById(`orderCard-${order.id}`);
            if (card) {
                const pill = card.querySelector('.kds-queue-pill');
                if (pill) pill.innerText = `#${order.queue_number || order.id}`;
            }
        });
    }

    async function fetchKdsOrders() {
        const storeId = document.getElementById('storeSelector').value;
        const url = `/api/v1/orders/kds${storeId ? '?store_id=' + storeId : ''}`;

        try {
            const res = await fetch(url);
            const json = await res.json();
            if (json.success) {
                syncOrdersData(json.data);
            }
        } catch (err) {
            console.error('Failed to fetch KDS queue', err);
        }
    }

    function syncOrdersData(newOrders) {
        if (isInitialLoad) {
            isInitialLoad = false;
            allOrders = newOrders;
            renderFullBoard();
            return;
        }

        if (newOrders.length > allOrders.length) {
            playBeep();
        }

        const newIds = new Set(newOrders.map(o => o.id));
        allOrders.forEach(oldOrder => {
            if (!newIds.has(oldOrder.id)) {
                const card = document.getElementById(`orderCard-${oldOrder.id}`);
                if (card) {
                    card.classList.add('anim-slide-out');
                    setTimeout(() => card.remove(), 280);
                }
            }
        });

        newOrders.forEach(newOrder => {
            const existing = allOrders.find(o => o.id === newOrder.id);
            if (!existing) {
                allOrders.push(newOrder);
                const targetPanelId = getPanelBodyId(newOrder.status);
                const container = document.getElementById(targetPanelId);
                if (container) {
                    const emptyState = container.querySelector('.kds-empty-panel');
                    if (emptyState) emptyState.remove();

                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = createOrderCardHtml(newOrder);
                    const newCardEl = tempDiv.firstElementChild;
                    newCardEl.classList.add('anim-slide-in');
                    container.prepend(newCardEl);
                    setTimeout(() => newCardEl.classList.remove('anim-slide-in'), 1500);
                }
            } else if (existing.status !== newOrder.status) {
                existing.status = newOrder.status;
                existing.queue_number = newOrder.queue_number;
                smoothMoveCardToPanel(existing.id, newOrder.status);
            }
        });

        allOrders = newOrders;
        updatePanelCounts();
        checkEmptyStates();
    }

    function renderFullBoard() {
        ['confirmed', 'processing', 'packaging', 'ready'].forEach(stage => {
            const container = document.getElementById(`panelBody-${stage}`);
            if (container) container.innerHTML = '';
        });

        allOrders.forEach(order => {
            const targetPanelId = getPanelBodyId(order.status);
            const container = document.getElementById(targetPanelId);
            if (container) {
                container.insertAdjacentHTML('beforeend', createOrderCardHtml(order));
            }
        });

        updatePanelCounts();
        checkEmptyStates();
    }

    function smoothMoveCardToPanel(orderId, nextStatus) {
        const card = document.getElementById(`orderCard-${orderId}`);
        const targetPanelId = getPanelBodyId(nextStatus);
        const targetContainer = document.getElementById(targetPanelId);
        if (!card || !targetContainer) return;

        card.classList.add('anim-slide-out');

        setTimeout(() => {
            card.classList.remove('anim-slide-out');
            card.className = `kds-order-card ${getCardStageClass(nextStatus)}`;

            const orderObj = allOrders.find(o => o.id === orderId);
            if (orderObj) {
                orderObj.status = nextStatus;
                const actionsContainer = document.getElementById(`cardActions-${orderId}`);
                if (actionsContainer) {
                    actionsContainer.innerHTML = getActionButtonsHtml(orderObj);
                }
            }

            const emptyState = targetContainer.querySelector('.kds-empty-panel');
            if (emptyState) emptyState.remove();

            targetContainer.prepend(card);
            card.classList.add('anim-slide-in');
            setTimeout(() => card.classList.remove('anim-slide-in'), 1500);

            recalculateQueueNumbers();
            updatePanelCounts();
            checkEmptyStates();
        }, 260);
    }

    async function handleMoveStatus(btn, orderId, nextStatus, actionLabel) {
        const card = document.getElementById(`orderCard-${orderId}`);
        if (!card) return;

        const allBtns = card.querySelectorAll('button');
        allBtns.forEach(b => b.disabled = true);
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses ${actionLabel}...`;

        const orderObj = allOrders.find(o => o.id === orderId);
        const orderNum = orderObj ? orderObj.order_number : `#${orderId}`;

        showKdsToast(`<i class="fa-solid fa-spinner fa-spin me-1"></i> Memproses ${orderNum}: Memindahkan ke "${nextStatus}"...`, 'info');

        smoothMoveCardToPanel(orderId, nextStatus);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/api/v1/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ status: nextStatus, note: 'Diperbarui via KDS Admin' })
            });

            const json = await res.json();
            if (json.success) {
                showKdsToast(`<i class="fa-solid fa-circle-check me-1"></i> Berhasil! Pesanan ${orderNum} kini "${nextStatus}".`, 'success');
            } else {
                showKdsToast(`Gagal: ${json.message || 'Terjadi kesalahan sistem'}`, 'danger');
                await fetchKdsOrders();
            }
        } catch (err) {
            console.error('Update status error', err);
            showKdsToast('Gagal menghubungi server. Periksa koneksi internet.', 'danger');
            await fetchKdsOrders();
        }
    }

    async function handleRejectClick(btn, orderId, orderNumber) {
        const reason = prompt(`Masukkan alasan penolakan untuk pesanan ${orderNumber}:`, 'Bahan baku habis atau kuota antrean dapur penuh');
        if (!reason || !reason.trim()) return;

        const card = document.getElementById(`orderCard-${orderId}`);
        if (card) {
            const allBtns = card.querySelectorAll('button');
            allBtns.forEach(b => b.disabled = true);
            btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i> Menolak...`;
            card.classList.add('anim-slide-out');
        }

        showKdsToast(`<i class="fa-solid fa-spinner fa-spin me-1"></i> Membatalkan pesanan ${orderNumber}...`, 'warning');

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/api/v1/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ status: 'Order Reject', rejection_reason: reason.trim() })
            });

            const json = await res.json();
            if (json.success) {
                showKdsToast(`<i class="fa-solid fa-triangle-exclamation me-1"></i> Pesanan ${orderNumber} berhasil ditolak.`, 'danger');
                allOrders = allOrders.filter(o => o.id !== orderId);
                if (card) card.remove();
                updatePanelCounts();
                checkEmptyStates();
            } else {
                showKdsToast(`Gagal menolak: ${json.message || 'Terjadi kesalahan'}`, 'danger');
                await fetchKdsOrders();
            }
        } catch (err) {
            showKdsToast('Gagal menghubungi server.', 'danger');
            await fetchKdsOrders();
        }
    }

    function updateWsBadge(isLive, label) {
        const badge = document.getElementById('wsStatusBadge');
        if (!badge) return;
        if (isLive) {
            badge.className = 'badge bg-success text-white font-mono px-2 py-1';
            badge.innerHTML = `<i class="fa-solid fa-bolt me-1"></i> ${label}`;
        } else {
            badge.className = 'badge bg-warning text-dark font-mono px-2 py-1';
            badge.innerHTML = `<i class="fa-solid fa-arrows-rotate me-1"></i> ${label}`;
        }
    }

    function initPusherWebSocket() {
        if (typeof Pusher === 'undefined') {
            updateWsBadge(false, 'Auto-Sync 3s');
            return;
        }

        try {
            const reverbPort = {{ (int) env("REVERB_PORT", 8085) }};
            const pusher = new Pusher('{{ env("REVERB_APP_KEY", "l0eg4pqm0kfrx3ctzlls") }}', {
                wsHost: window.location.hostname || '127.0.0.1',
                wsPort: reverbPort,
                wssPort: reverbPort,
                forceTLS: false,
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                cluster: 'mt1'
            });

            pusher.connection.bind('connected', () => {
                updateWsBadge(true, 'WebSocket Live');
            });
            pusher.connection.bind('connecting', () => {
                updateWsBadge(false, 'Menghubungkan WS...');
            });
            pusher.connection.bind('disconnected', () => {
                updateWsBadge(false, 'Auto-Sync 3s');
            });
            pusher.connection.bind('error', () => {
                updateWsBadge(false, 'Auto-Sync 3s');
            });

            const ordersChannel = pusher.subscribe('orders');
            ordersChannel.bind('order.created', (data) => {
                playBeep();
                showKdsToast(`<i class="fa-solid fa-bell text-warning me-1"></i> Pesanan baru masuk: ${data.order?.order_number || ''}!`, 'info');
                fetchKdsOrders();
            });
            ordersChannel.bind('order.status.updated', (data) => {
                if (data.order) {
                    showKdsToast(`<i class="fa-solid fa-sync text-primary me-1"></i> Update: ${data.order.order_number} -> ${data.status}`, 'info');
                    const existing = allOrders.find(o => o.id === data.order.id);
                    if (existing && existing.status !== data.status) {
                        existing.status = data.status;
                        smoothMoveCardToPanel(data.order.id, data.status);
                    } else {
                        fetchKdsOrders();
                    }
                }
            });
        } catch (err) {
            console.error('Failed to initialize WebSocket', err);
            updateWsBadge(false, 'Auto-Sync 3s');
        }
    }

    document.getElementById('storeSelector').addEventListener('change', () => {
        isInitialLoad = true;
        fetchKdsOrders();
    });

    // Initialize
    fetchKdsOrders();
    initPusherWebSocket();
    setInterval(fetchKdsOrders, 3500);
</script>
</body>
</html>
