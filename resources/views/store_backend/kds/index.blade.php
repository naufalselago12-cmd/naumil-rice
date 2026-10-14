<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=2">
    <title>KDS Kitchen Display - {{ $store->name }}</title>

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
            border-radius: 14px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            transition: transform 0.2s;
        }

        .kds-card-header {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-kds);
            background-color: rgba(0, 0, 0, 0.2);
        }

        .kds-order-number {
            font-family: var(--font-mono);
            font-weight: 700;
            font-size: 1.1rem;
            color: #38bdf8;
        }

        .kds-card-body {
            padding: 1rem;
            flex: 1;
        }

        .kds-item-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 0.5rem 0;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
        }

        .kds-qty {
            background-color: #f43f5e;
            color: #fff;
            font-family: var(--font-mono);
            font-weight: 700;
            font-size: 1rem;
            padding: 2px 8px;
            border-radius: 6px;
            min-width: 32px;
            text-align: center;
        }

        .kds-card-actions {
            padding: 0.75rem 1rem;
            background-color: rgba(0, 0, 0, 0.25);
            border-top: 1px solid var(--border-kds);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
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
    </style>
</head>
<body>

<header class="kds-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('store.backend.dashboard', $store->slug) }}" class="btn btn-sm btn-outline-secondary text-white" title="Kembali ke Dashboard">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-desktop text-danger fs-4"></i>
            <div>
                <h5 class="mb-0 fw-bold">KDS DAPUR: {{ $store->name }}</h5>
                <small class="text-secondary font-mono">{{ $store->code }} • Real-Time Order Stream</small>
            </div>
        </div>
    </div>

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

<main class="kds-grid" id="kdsContainer">
    <div class="col-12 text-center py-5 text-secondary">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <h5>Memuat antrean dapur {{ $store->name }}...</h5>
    </div>
</main>

<script>
    const currentStoreId = {{ $store->id }};
    let soundEnabled = true;
    let audioCtx = null;

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

    function updateClock() {
        const now = new Date();
        document.getElementById('liveClock').innerText = now.toLocaleTimeString('id-ID');
    }
    setInterval(updateClock, 1000);
    updateClock();

    document.getElementById('fullscreenBtn').addEventListener('click', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            if (document.exitFullscreen) document.exitFullscreen();
        }
    });

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

    let previousOrderCount = 0;

    async function fetchKdsOrders() {
        try {
            const res = await fetch(`/api/v1/orders/kds?store_id=${currentStoreId}`);
            const json = await res.json();
            if (json.success) {
                renderKdsOrders(json.data);
                if (json.data.length > previousOrderCount) playBeep();
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
                    <h3>Semua Pesanan Dapur Selesai!</h3>
                    <p class="text-muted">Tidak ada tiket antrean memasak saat ini.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = orders.map(order => {
            const itemsHtml = order.items.map(item => {
                let optionsHtml = '';
                if (item.selected_options && item.selected_options.length > 0) {
                    optionsHtml = item.selected_options.map(opt => `
                        <span class="badge bg-secondary me-1">+ ${opt.name}</span>
                    `).join('');
                }
                return `
                    <div class="kds-item-row">
                        <div class="kds-qty">${item.quantity}</div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-white fs-6">${item.menu_name}</div>
                            <div class="mt-1">${optionsHtml}</div>
                            ${item.special_notes ? `<small class="text-warning d-block mt-1"><i class="fa-solid fa-note-sticky me-1"></i>${item.special_notes}</small>` : ''}
                        </div>
                    </div>
                `;
            }).join('');

            return `
                <div class="kds-card">
                    <div class="kds-card-header">
                        <div>
                            <span class="kds-order-number">${order.order_number}</span>
                            <small class="text-muted d-block font-mono">${order.client ? order.client.name : 'Pelanggan'}</small>
                        </div>
                        <span class="badge bg-warning text-dark font-mono">${order.status}</span>
                    </div>
                    <div class="kds-card-body">
                        ${itemsHtml}
                    </div>
                    <div class="kds-card-actions">
                        <button class="btn btn-sm btn-primary" onclick="updateOrderStatus(${order.id}, 'Order On Packaging')">
                            <i class="fa-solid fa-box me-1"></i> Packaging
                        </button>
                        <button class="btn btn-sm btn-success" onclick="updateOrderStatus(${order.id}, 'Order Ready')">
                            <i class="fa-solid fa-check me-1"></i> Siap / Selesai
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    async function updateOrderStatus(orderId, nextStatus) {
        try {
            const res = await fetch(`/api/v1/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ status: nextStatus, note: 'Diperbarui via KDS Dapur' })
            });
            const json = await res.json();
            if (json.success) fetchKdsOrders();
        } catch (err) {
            console.error('Failed to update status', err);
        }
    }

    fetchKdsOrders();
    setInterval(fetchKdsOrders, 5000);
</script>
</body>
</html>
