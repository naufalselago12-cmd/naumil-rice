<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=2">
    <title>Kitchen Display System (KDS) - Naumil Rice</title>

    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-kds: #0b1120;
            --card-kds: #151f33;
            --border-kds: #22304d;
            --header-kds: #111a2d;
            --font-family: 'Plus Jakarta Sans', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--bg-kds);
            color: #f1f5f9;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* KDS Header */
        .kds-header {
            background-color: var(--header-kds);
            border-bottom: 2px solid var(--border-kds);
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        /* Order Cards Grid */
        .kds-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.25rem;
            padding: 1.5rem;
            flex: 1;
        }

        .kds-card {
            background-color: var(--card-kds);
            border: 2px solid var(--border-kds);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(0,0,0,0.3);
            transition: transform 0.15s, border-color 0.15s;
        }

        .kds-card:hover {
            border-color: #38bdf8;
        }

        /* Status Header Colors */
        .kds-card.status-confirmed .kds-card-head {
            background: linear-gradient(90deg, #475569 0%, #334155 100%);
        }
        .kds-card.status-processing .kds-card-head {
            background: linear-gradient(90deg, #0284c7 0%, #0369a1 100%);
        }
        .kds-card.status-packaging .kds-card-head {
            background: linear-gradient(90deg, #d97706 0%, #b45309 100%);
        }
        .kds-card.status-delivery .kds-card-head {
            background: linear-gradient(90deg, #0891b2 0%, #0e7490 100%);
        }
        .kds-card.status-ready .kds-card-head {
            background: linear-gradient(90deg, #16a34a 0%, #15803d 100%);
        }

        .kds-card-head {
            padding: 0.75rem 1rem;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kds-card-body {
            padding: 1rem;
            flex: 1;
            overflow-y: auto;
            max-height: 400px;
        }

        .kds-item {
            padding: 0.6rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .kds-item:last-child {
            border-bottom: none;
        }

        .kds-item-qty {
            font-family: var(--font-mono);
            font-size: 1.25rem;
            font-weight: 800;
            color: #38bdf8;
            margin-right: 8px;
            display: inline-block;
            min-width: 32px;
        }

        .kds-item-name {
            font-weight: 700;
            font-size: 1.05rem;
            color: #fff;
        }

        .kds-card-footer {
            padding: 0.75rem 1rem;
            background-color: rgba(0,0,0,0.25);
            border-top: 1px solid var(--border-kds);
            display: flex;
            gap: 6px;
        }

        /* Pulse animation for live alert */
        .live-dot {
            width: 10px;
            height: 10px;
            background-color: #22c55e;
            border-radius: 50%;
            display: inline-block;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
    </style>
</head>
<body>

<!-- KDS Top Bar -->
<header class="kds-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary text-light">
            <i class="fa-solid fa-arrow-left me-1"></i> Dashboard
        </a>
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-fire-burner text-danger fs-4"></i>
            <div>
                <h5 class="mb-0 fw-bold font-monospace text-uppercase" style="letter-spacing: 0.05em;">KITCHEN DISPLAY SYSTEM</h5>
                <small class="text-secondary">Antrean Pesanan Dapur & Pengemasan</small>
            </div>
        </div>
    </div>

    <!-- Center: Store Selector -->
    <div class="d-flex align-items-center gap-2">
        <select id="storeSelector" class="form-select form-select-sm bg-dark text-light border-secondary" style="min-width: 220px;">
            <option value="">Semua Cabang Dapur</option>
            @foreach($stores as $st)
                <option value="{{ $st->id }}" {{ ($selectedStore && $selectedStore->id == $st->id) ? 'selected' : '' }}>
                    {{ $st->name }} ({{ $st->code }})
                </option>
            @endforeach
        </select>
    </div>

    <!-- Right: Stats, Sound Toggle & Fullscreen -->
    <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="live-dot"></span>
            <span class="badge bg-danger fs-6" id="queueCountBadge">0 Antrean</span>
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

<!-- KDS Grid Tickets -->
<main class="kds-grid" id="kdsContainer">
    <!-- Tickets injected via JavaScript -->
    <div class="col-12 text-center py-5 text-secondary">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <h5>Memuat antrean pesanan dapur...</h5>
    </div>
</main>

<script>
    let soundEnabled = true;
    let audioCtx = null;

    // Beep synthesizer for new order notification
    function playBeep() {
        if (!soundEnabled) return;
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5
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
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
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

    // Fetch and render KDS orders
    let previousOrderCount = 0;

    async function fetchKdsOrders() {
        const storeId = document.getElementById('storeSelector').value;
        const url = `/api/v1/orders/kds${storeId ? '?store_id=' + storeId : ''}`;

        try {
            const res = await fetch(url);
            const json = await res.json();
            if (json.success) {
                renderKdsOrders(json.data);
                
                // Audio alert if new orders arrive
                if (json.data.length > previousOrderCount) {
                    playBeep();
                }
                previousOrderCount = json.data.length;
            }
        } catch (err) {
            console.error('Failed to fetch KDS queue', err);
        }
    }

    function renderKdsOrders(orders) {
        const container = document.getElementById('kdsContainer');
        document.getElementById('queueCountBadge').innerText = `${orders.length} Antrean`;

        if (orders.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5 text-secondary">
                    <i class="fa-solid fa-circle-check fa-4x text-success mb-3"></i>
                    <h3>Semua Pesanan Selesai!</h3>
                    <p class="text-muted">Tidak ada pesanan antrean baru saat ini.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = orders.map(order => {
            const statusClass = getStatusClass(order.status);
            const timeAgo = formatTimeAgo(order.created_at);

            const itemsHtml = order.items.map(item => {
                let optionsHtml = '';
                if (item.selected_options && item.selected_options.length > 0) {
                    optionsHtml = item.selected_options.map(opt => `
                        <span class="badge bg-secondary me-1">+ ${opt.name}</span>
                    `).join('');
                }

                let notesHtml = item.special_notes ? `
                    <div class="text-warning small mt-1">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Catatan: ${item.special_notes}
                    </div>
                ` : '';

                return `
                    <div class="kds-item">
                        <div class="d-flex align-items-start">
                            <span class="kds-item-qty">${item.quantity}x</span>
                            <div class="flex-grow-1">
                                <span class="kds-item-name">${item.menu_name}</span>
                                ${optionsHtml ? `<div class="mt-1">${optionsHtml}</div>` : ''}
                                ${notesHtml}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            return `
                <div class="kds-card ${statusClass}">
                    <div class="kds-card-head">
                        <div>
                            <span class="fw-bold font-monospace fs-6">#${order.order_number.slice(-8)}</span>
                            <small class="d-block text-white-50">${order.client?.name || 'Pelanggan'}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-black bg-opacity-50 font-monospace">${timeAgo}</span>
                            <small class="d-block fw-bold">${order.status}</small>
                        </div>
                    </div>

                    <div class="kds-card-body">
                        ${order.notes ? `
                            <div class="alert alert-warning py-1 px-2 mb-2 small">
                                <strong>Pesanan:</strong> ${order.notes}
                            </div>
                        ` : ''}
                        ${itemsHtml}
                    </div>

                    <div class="kds-card-footer">
                        ${getActionButtons(order)}
                    </div>
                </div>
            `;
        }).join('');
    }

    function getStatusClass(status) {
        switch(status) {
            case 'Order Konfirmed': return 'status-confirmed';
            case 'Order On Proses': return 'status-processing';
            case 'Order On Packaging': return 'status-packaging';
            case 'Order On Delivery': return 'status-delivery';
            case 'Order Ready': return 'status-ready';
            default: return '';
        }
    }

    function formatTimeAgo(dateStr) {
        const diffSec = Math.floor((new Date() - new Date(dateStr)) / 1000);
        if (diffSec < 60) return `${diffSec}d`;
        const min = Math.floor(diffSec / 60);
        return `${min}m`;
    }

    function getActionButtons(order) {
        switch(order.status) {
            case 'Order Konfirmed':
                return `
                    <button class="btn btn-sm btn-primary flex-grow-1 fw-bold" onclick="updateOrderStatus(${order.id}, 'Order On Proses')">
                        <i class="fa-solid fa-fire me-1"></i> Mulai Masak
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="rejectOrder(${order.id})" title="Tolak">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                `;
            case 'Order On Proses':
                return `
                    <button class="btn btn-sm btn-warning text-dark flex-grow-1 fw-bold" onclick="updateOrderStatus(${order.id}, 'Order On Packaging')">
                        <i class="fa-solid fa-box me-1"></i> Masuk Kemasan
                    </button>
                `;
            case 'Order On Packaging':
                return `
                    <button class="btn btn-sm btn-info text-dark flex-grow-1 fw-bold" onclick="updateOrderStatus(${order.id}, 'Order On Delivery')">
                        <i class="fa-solid fa-motorcycle me-1"></i> Kirim / Delivery
                    </button>
                `;
            case 'Order On Delivery':
                return `
                    <button class="btn btn-sm btn-success flex-grow-1 fw-bold" onclick="updateOrderStatus(${order.id}, 'Order Ready')">
                        <i class="fa-solid fa-check me-1"></i> Selesai (Ready)
                    </button>
                `;
            case 'Order Ready':
                return `
                    <span class="badge bg-success w-100 py-2 fs-7">
                        <i class="fa-solid fa-circle-check me-1"></i> Siap Diterima
                    </span>
                `;
            default:
                return '';
        }
    }

    async function updateOrderStatus(orderId, nextStatus) {
        try {
            const res = await fetch(`/api/v1/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ status: nextStatus })
            });
            const json = await res.json();
            if (json.success) {
                fetchKdsOrders();
            }
        } catch (e) {
            alert('Gagal mengupdate status pesanan.');
        }
    }

    function rejectOrder(orderId) {
        const reason = prompt('Masukkan alasan penolakan pesanan (misal: Bahan baku habis/antrean penuh):');
        if (reason) {
            fetch(`/api/v1/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ status: 'Order Reject', rejection_reason: reason })
            }).then(() => fetchKdsOrders());
        }
    }

    // Polling heartbeat (every 3.5 seconds)
    setInterval(fetchKdsOrders, 3500);
    document.getElementById('storeSelector').addEventListener('change', fetchKdsOrders);
    fetchKdsOrders();
</script>
</body>
</html>
