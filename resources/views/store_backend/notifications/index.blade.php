@extends('store_backend.layouts.app')

@section('title', 'Notifikasi Realtime')

@push('styles')
<style>
    .notif-hero { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); color: #fff; border-radius: 18px; padding: 1.5rem 1.75rem; position: relative; overflow: hidden; }
    .notif-hero::after { content: ''; position: absolute; right: -40px; top: -60px; width: 220px; height: 220px; border-radius: 50%; background: rgba(56,189,248,.15); }
    .notif-hero h2 { font-weight: 800; margin: 0; }
    .notif-hero p { margin: .25rem 0 0; color: #cbd5e1; }

    .push-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: .9rem 1.1rem; display: flex; flex-wrap: wrap; gap: .75rem 1.25rem; align-items: center; justify-content: space-between; }
    .push-dot { width: 10px; height: 10px; border-radius: 50%; background: #94a3b8; display: inline-block; margin-right: .5rem; }
    .push-dot.ok { background: #10b981; box-shadow: 0 0 0 4px rgba(16,185,129,.2); animation: pulseDot 1.8s infinite; }
    .push-dot.warn { background: #f59e0b; }
    .push-dot.bad { background: #ef4444; }
    @keyframes pulseDot { 50% { box-shadow: 0 0 0 8px rgba(16,185,129,0); } }

    .legend-chip { display: inline-flex; align-items: center; gap: .45rem; font-size: .78rem; font-weight: 700; padding: .3rem .75rem; border-radius: 999px; }
    .legend-chip.cod { background: #fef3c7; color: #92400e; }
    .legend-chip.direct { background: #d1fae5; color: #065f46; }

    .filter-tabs .btn { border-radius: 999px; font-size: .82rem; font-weight: 600; }

    .n-card { position: relative; border-radius: 16px; padding: 1.1rem 1.25rem 1.1rem 1.5rem; margin-bottom: 1rem; border: 1px solid transparent; cursor: pointer; transition: transform .15s, box-shadow .15s; overflow: hidden; }
    .n-card:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(15,23,42,.1); }
    .n-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 7px; }
    .n-card.cod { background: linear-gradient(135deg, #fffbeb 0%, #fff7e0 100%); border-color: #fde68a; }
    .n-card.cod::before { background: linear-gradient(180deg, #f59e0b, #ea580c); }
    .n-card.direct { background: linear-gradient(135deg, #ecfdf5 0%, #e6faf1 100%); border-color: #a7f3d0; }
    .n-card.direct::before { background: linear-gradient(180deg, #10b981, #059669); }
    .n-card.unread { box-shadow: 0 6px 18px rgba(15,23,42,.08); }
    .n-card.is-new { animation: cardIn .6s ease, cardFlash 2.2s ease; }
    @keyframes cardIn { from { opacity: 0; transform: translateY(-14px) scale(.98); } to { opacity: 1; transform: none; } }
    @keyframes cardFlash { 0%,40% { box-shadow: 0 0 0 4px rgba(14,165,233,.45); } 100% { box-shadow: 0 6px 18px rgba(15,23,42,.08); } }

    .n-icon { width: 46px; height: 46px; border-radius: 13px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; color: #fff; flex-shrink: 0; }
    .cod .n-icon { background: linear-gradient(135deg, #f59e0b, #ea580c); }
    .direct .n-icon { background: linear-gradient(135deg, #10b981, #059669); }

    .n-name { font-weight: 800; color: #0f172a; font-size: 1.02rem; line-height: 1.2; }
    .n-meta { font-size: .76rem; color: #64748b; }
    .n-badge { font-size: .7rem; font-weight: 800; padding: .25rem .65rem; border-radius: 999px; letter-spacing: .02em; white-space: nowrap; }
    .cod .n-badge { background: #f59e0b; color: #fff; }
    .direct .n-badge { background: #10b981; color: #fff; }
    .n-unread-dot { width: 9px; height: 9px; border-radius: 50%; background: #ef4444; display: inline-block; margin-right: 6px; }

    .n-items { background: rgba(255,255,255,.75); border-radius: 12px; padding: .65rem .9rem; margin: .85rem 0; font-size: .86rem; }
    .n-item { display: flex; justify-content: space-between; gap: 1rem; padding: .22rem 0; }
    .n-item + .n-item { border-top: 1px dashed rgba(100,116,139,.25); }
    .n-item small { color: #64748b; display: block; }
    .n-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .6rem; }
    .n-total { font-family: var(--font-mono); font-weight: 800; font-size: 1.2rem; color: #0f172a; }
    .n-pay { font-size: .8rem; font-weight: 700; display: inline-flex; align-items: center; gap: .4rem; background: rgba(255,255,255,.85); padding: .3rem .7rem; border-radius: 10px; color: #334155; }
    .cod .n-pay i { color: #ea580c; } .direct .n-pay i { color: #059669; }
    .empty-state { text-align: center; padding: 3.5rem 1rem; color: #64748b; }
    .empty-state i { font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; }
</style>
@endpush

@section('content')
<div class="notif-hero mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 position-relative" style="z-index:1;">
        <div>
            <h2><i class="fa-solid fa-bell me-2"></i>Notifikasi Pesanan</h2>
            <p>Pesanan & pembayaran masuk {{ $store->name }} secara realtime.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-light btn-sm fw-semibold" id="btnMute"><i class="fa-solid fa-volume-high me-1"></i><span>Suara Aktif</span></button>
            <button type="button" class="btn btn-outline-light btn-sm fw-semibold" id="btnTestCod"><i class="fa-solid fa-play me-1"></i>Tes COD</button>
            <button type="button" class="btn btn-outline-light btn-sm fw-semibold" id="btnTestDirect"><i class="fa-solid fa-play me-1"></i>Tes Bayar Langsung</button>
        </div>
    </div>
</div>

{{-- Status Push OneSignal --}}
<div class="push-panel mb-3" id="pushPanel">
    <div class="d-flex align-items-center">
        <span class="push-dot" id="pushDot"></span>
        <div>
            <div class="fw-bold small" id="pushTitle">Memeriksa status notifikasi...</div>
            <div class="text-muted" style="font-size:.76rem;" id="pushDesc"></div>
        </div>
    </div>
    <button type="button" class="btn btn-primary btn-sm fw-semibold" id="btnEnablePush" style="display:none;">
        <i class="fa-solid fa-bell me-1"></i> Aktifkan Notifikasi
    </button>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div class="d-flex flex-wrap gap-2">
        <span class="legend-chip cod"><i class="fa-solid fa-bell-concierge"></i> COD · Bayar setelah makanan jadi</span>
        <span class="legend-chip direct"><i class="fa-solid fa-circle-check"></i> Bayar langsung (QRIS / Gateway)</span>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="btn-group filter-tabs" role="group" id="filterTabs">
            <button type="button" class="btn btn-sm btn-outline-dark active" data-filter="all">Semua</button>
            <button type="button" class="btn btn-sm btn-outline-dark" data-filter="unread">Belum dibaca <span class="badge bg-danger ms-1" data-notif-badge style="display:none;">0</span></button>
            <button type="button" class="btn btn-sm btn-outline-warning" data-filter="cod">COD</button>
            <button type="button" class="btn btn-sm btn-outline-success" data-filter="direct">Bayar Langsung</button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnReadAll"><i class="fa-solid fa-check-double me-1"></i>Tandai semua dibaca</button>
    </div>
</div>

<div id="notifList"></div>
<div class="text-center mb-4">
    <button type="button" class="btn btn-outline-primary" id="btnLoadMore" style="display:none;"><i class="fa-solid fa-clock-rotate-left me-1"></i> Muat riwayat lebih lama</button>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const cfg = window.NaumilNotifConfig;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const initial = @json($initial);
    const readUrl = (id) => cfg.feedUrl.replace(/feed$/, id + '/read');
    const readAllUrl = cfg.feedUrl.replace(/feed$/, 'read-all');

    const listEl = document.getElementById('notifList');
    const moreBtn = document.getElementById('btnLoadMore');
    let filter = 'all';
    let hasMore = initial.has_more;
    let oldestId = null;

    const rupiah = n => 'Rp ' + Math.round(n || 0).toLocaleString('id-ID');
    function el(tag, cls, text) {
        const e = document.createElement(tag);
        if (cls) e.className = cls;
        if (text !== undefined && text !== null) e.textContent = text;
        return e;
    }
    function timeAgo(iso) {
        const s = Math.max(0, (Date.now() - new Date(iso).getTime()) / 1000);
        if (s < 45) return 'baru saja';
        if (s < 3600) return Math.round(s / 60) + ' menit lalu';
        if (s < 86400) return Math.round(s / 3600) + ' jam lalu';
        return Math.round(s / 86400) + ' hari lalu';
    }

    function buildCard(item, isNew) {
        const d = item.data || {};
        const isCod = item.type === 'cod';
        const card = el('div', 'n-card ' + (isCod ? 'cod' : 'direct') + (item.is_read ? '' : ' unread') + (isNew ? ' is-new' : ''));
        card.dataset.id = item.id;

        // Header
        const head = el('div', 'd-flex align-items-start gap-3');
        const icon = el('div', 'n-icon');
        icon.innerHTML = '<i class="fa-solid ' + (isCod ? 'fa-bell-concierge' : 'fa-circle-check') + '"></i>';
        const who = el('div', 'flex-grow-1');
        const nameRow = el('div', 'n-name');
        if (!item.is_read) nameRow.append(el('span', 'n-unread-dot'));
        nameRow.append(document.createTextNode(d.customer_name || 'Pelanggan'));
        const meta = el('div', 'n-meta');
        meta.textContent = '#' + (d.order_number || '-') + ' · ' + item.created_human + ' · ';
        const ago = el('span', 'js-ago', timeAgo(item.created_at));
        ago.dataset.at = item.created_at;
        meta.append(ago);
        if (d.customer_whatsapp) meta.append(document.createTextNode(' · ' + d.customer_whatsapp));
        who.append(nameRow, meta);

        const label = isCod ? 'COD · BAYAR SETELAH MAKANAN JADI'
            : (item.event === 'payment_review' ? 'BAYAR LANGSUNG · MENUNGGU VERIFIKASI' : 'BAYAR LANGSUNG · LUNAS');
        head.append(icon, who, el('span', 'n-badge', label));

        // Rincian pembelian
        const items = el('div', 'n-items');
        (d.items || []).forEach(it => {
            const row = el('div', 'n-item');
            const left = el('div');
            left.append(el('span', 'fw-semibold', it.quantity + 'x ' + it.name));
            if (it.options && it.options.length) left.append(el('small', '', '+ ' + it.options.join(', ')));
            if (it.notes) left.append(el('small', '', 'Catatan: ' + it.notes));
            row.append(left, el('span', 'font-mono', rupiah(it.subtotal)));
            items.append(row);
        });
        if (d.discount > 0) {
            const row = el('div', 'n-item');
            row.append(el('span', 'text-success', 'Diskon'), el('span', 'font-mono text-success', '- ' + rupiah(d.discount)));
            items.append(row);
        }
        if (d.notes) items.append(el('small', 'text-muted d-block mt-1', 'Catatan pesanan: ' + d.notes));

        // Footer: total + metode bayar
        const foot = el('div', 'n-foot');
        const pay = el('span', 'n-pay');
        pay.innerHTML = '<i class="fa-solid ' + (isCod ? 'fa-hand-holding-dollar' : 'fa-credit-card') + '"></i>';
        pay.append(document.createTextNode(d.payment_label || d.payment_method || '-'));
        const totalBox = el('div', 'text-end');
        totalBox.append(el('div', 'n-meta', 'Total Harga'), el('div', 'n-total', rupiah(d.total)));
        const link = el('a', 'btn btn-sm btn-dark fw-semibold');
        link.href = item.order_url;
        link.innerHTML = '<i class="fa-solid fa-receipt me-1"></i>Lihat Pesanan';
        link.addEventListener('click', e => e.stopPropagation());
        const left = el('div', 'd-flex flex-wrap align-items-center gap-2');
        left.append(pay, link);
        foot.append(left, totalBox);

        card.append(head, items, foot);
        card.addEventListener('click', () => markRead(item, card));
        return card;
    }

    async function markRead(item, card) {
        if (item.is_read) return;
        item.is_read = true;
        card.classList.remove('unread');
        const dot = card.querySelector('.n-unread-dot'); if (dot) dot.remove();
        try {
            const r = await fetch(readUrl(item.id), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, credentials: 'same-origin' });
            const j = await r.json();
            window.NaumilNotif.updateBadge(j.unread_count);
        } catch (e) {}
    }

    function setEmpty() {
        if (listEl.children.length) return;
        const box = el('div', 'empty-state');
        box.innerHTML = '<i class="fa-regular fa-bell-slash d-block"></i>';
        box.append(el('div', 'fw-bold', 'Belum ada notifikasi'), el('div', 'small', 'Pesanan COD dan pembayaran masuk akan muncul di sini secara realtime.'));
        listEl.append(box);
    }

    function render(items, { append = false, isNew = false } = {}) {
        if (!append) listEl.innerHTML = '';
        listEl.querySelector('.empty-state')?.remove();
        items.forEach(it => append ? listEl.append(buildCard(it, false)) : listEl.append(buildCard(it, isNew)));
        const last = listEl.querySelector('.n-card:last-child');
        oldestId = last ? parseInt(last.dataset.id, 10) : null;
        moreBtn.style.display = hasMore && oldestId ? '' : 'none';
        setEmpty();
    }

    function matchesFilter(item) {
        return filter === 'all' || (filter === 'unread' && !item.is_read) || filter === item.type;
    }

    async function load({ before = null } = {}) {
        const qs = new URLSearchParams({ filter, limit: 20 });
        if (before) qs.set('before_id', before);
        const r = await fetch(cfg.feedUrl + '?' + qs, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        return r.json();
    }

    // Render awal (data sudah tersedia dari server)
    render(initial.items);
    window.NaumilNotif.updateBadge(initial.unread_count);

    // Filter
    document.querySelectorAll('#filterTabs [data-filter]').forEach(btn => btn.addEventListener('click', async () => {
        filter = btn.dataset.filter;
        document.querySelectorAll('#filterTabs [data-filter]').forEach(b => {
            const active = b === btn;
            b.classList.toggle('active', active);
        });
        const data = await load();
        hasMore = data.has_more;
        render(data.items);
    }));

    moreBtn.addEventListener('click', async () => {
        moreBtn.disabled = true;
        const data = await load({ before: oldestId });
        hasMore = data.has_more;
        render(data.items, { append: true });
        moreBtn.disabled = false;
    });

    document.getElementById('btnReadAll').addEventListener('click', async () => {
        await fetch(readAllUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, credentials: 'same-origin' });
        listEl.querySelectorAll('.n-card.unread').forEach(c => { c.classList.remove('unread'); c.querySelector('.n-unread-dot')?.remove(); });
        window.NaumilNotif.updateBadge(0);
    });

    // Notifikasi baru (dari polling / push saat halaman terbuka)
    document.addEventListener('naumil:new', e => {
        const fresh = e.detail.items.filter(matchesFilter);
        if (!fresh.length) return;
        listEl.querySelector('.empty-state')?.remove();
        fresh.slice().reverse().forEach(it => listEl.prepend(buildCard(it, true)));
    });

    setInterval(() => document.querySelectorAll('.js-ago').forEach(s => s.textContent = timeAgo(s.dataset.at)), 30000);

    /* ---------- Suara ---------- */
    const btnMute = document.getElementById('btnMute');
    function paintMute() {
        const m = window.NaumilNotif.isMuted();
        btnMute.querySelector('i').className = 'fa-solid ' + (m ? 'fa-volume-xmark' : 'fa-volume-high') + ' me-1';
        btnMute.querySelector('span').textContent = m ? 'Suara Mati' : 'Suara Aktif';
    }
    btnMute.addEventListener('click', () => { window.NaumilNotif.setMuted(!window.NaumilNotif.isMuted()); paintMute(); });
    document.getElementById('btnTestCod').addEventListener('click', () => window.NaumilNotif.playSound('cod'));
    document.getElementById('btnTestDirect').addEventListener('click', () => window.NaumilNotif.playSound('direct'));
    paintMute();

    /* ---------- Status Push OneSignal ---------- */
    const dot = document.getElementById('pushDot');
    const title = document.getElementById('pushTitle');
    const desc = document.getElementById('pushDesc');
    const btnPush = document.getElementById('btnEnablePush');
    const states = {
        loading:      ['', 'Menyiapkan OneSignal...', 'Mohon tunggu sebentar.', false],
        unconfigured: ['bad', 'OneSignal belum dikonfigurasi', 'Isi ONESIGNAL_APP_ID & ONESIGNAL_REST_API_KEY di file .env. Riwayat & suara di halaman ini tetap berjalan.', false],
        'not-eligible': ['warn', 'Akun ini tidak menerima push', 'Push hanya dikirim ke admin toko yang login pada cabang ini.', false],
        unsupported:  ['bad', 'Browser tidak mendukung Web Push', 'Gunakan Chrome / Edge / Firefox terbaru (HTTPS atau localhost).', false],
        blocked:      ['bad', 'Notifikasi diblokir browser', 'Klik ikon gembok di address bar, ubah Notifikasi menjadi "Izinkan", lalu muat ulang.', false],
        prompt:       ['warn', 'Notifikasi belum diaktifkan', 'Aktifkan agar pesanan masuk tampil sebagai push notification.', true],
        optedout:     ['warn', 'Langganan notifikasi nonaktif', 'Klik tombol untuk berlangganan kembali.', true],
        active:       ['ok', 'Notifikasi aktif', 'Perangkat ini terhubung ke akun Anda. Klik notifikasi untuk membuka halaman ini.', false],
    };
    function paintPush(state) {
        const s = states[state] || states.loading;
        dot.className = 'push-dot ' + s[0];
        title.textContent = s[1];
        desc.textContent = s[2];
        btnPush.style.display = s[3] ? '' : 'none';
    }
    document.addEventListener('naumil:push-state', e => paintPush(e.detail.state));
    paintPush(window.NaumilNotif.getPushState());
    btnPush.addEventListener('click', () => window.NaumilNotif.requestPush());

    // Browser memblokir audio sebelum interaksi pertama
    document.addEventListener('naumil:audio-blocked', () => {
        desc.textContent = 'Klik di mana saja pada halaman ini sekali agar efek suara bisa diputar oleh browser.';
    });

    // Tawarkan prompt izin otomatis (slidedown OneSignal) bila belum pernah diminta
    document.addEventListener('naumil:push-state', function once(e) {
        if (e.detail.state !== 'prompt') return;
        document.removeEventListener('naumil:push-state', once);
        window.OneSignalDeferred.push(function (OneSignal) { OneSignal.Slidedown.promptPush(); });
    });
})();
</script>
@endpush
