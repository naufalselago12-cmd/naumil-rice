{{--
    Notifikasi realtime admin toko (OneSignal Web Push SDK v16 + polling riwayat + efek suara).
    Di-include dari store_backend/layouts/app.blade.php sehingga suara & toast aktif di semua halaman backend toko.
    Ref: https://documentation.onesignal.com/docs/en/web-sdk-setup
--}}
@php
    $__nUser = auth()->user();
    $__nAppId = config('onesignal.app_id');
    // Hanya admin toko pemilik cabang ini yang didaftarkan ke OneSignal (external_id)
    $__nEligible = $__nUser && $__nUser->isStoreAdmin() && (int) $__nUser->store_id === (int) $currentStore->id;
    $__nLatestId = (int) \App\Models\StoreNotification::where('store_id', $currentStore->id)->max('id');
@endphp

<div id="naumilToastHost" aria-live="polite"></div>

<style>
    #naumilToastHost { position: fixed; top: 76px; right: 18px; z-index: 2000; display: flex; flex-direction: column; gap: 10px; width: min(380px, calc(100vw - 36px)); pointer-events: none; }
    .naumil-toast { pointer-events: auto; cursor: pointer; background: #fff; border-radius: 14px; padding: .85rem 1rem; box-shadow: 0 10px 30px rgba(15,23,42,.18); border-left: 6px solid #f59e0b; display: flex; gap: .75rem; align-items: flex-start; animation: naumilToastIn .35s cubic-bezier(.2,.9,.3,1.2); text-decoration: none; color: inherit; }
    .naumil-toast.is-direct { border-left-color: #10b981; }
    .naumil-toast .t-ico { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #fff; background: #f59e0b; }
    .naumil-toast.is-direct .t-ico { background: #10b981; }
    .naumil-toast .t-title { font-weight: 700; font-size: .88rem; color: #0f172a; line-height: 1.25; }
    .naumil-toast .t-sub { font-size: .78rem; color: #64748b; margin-top: 2px; }
    .naumil-toast.leaving { opacity: 0; transform: translateX(30px); transition: all .3s; }
    @keyframes naumilToastIn { from { opacity: 0; transform: translateX(40px) scale(.96); } to { opacity: 1; transform: none; } }
</style>

<script>
    window.NaumilNotifConfig = @json([
        'appId' => $__nAppId,
        'safariWebId' => config('onesignal.safari_web_id'),
        'externalId' => $__nEligible ? config('onesignal.external_id_prefix') . $__nUser->id : null,
        'eligible' => (bool) $__nEligible,
        'feedUrl' => route('store.backend.notification.feed', $currentStore->slug),
        'pageUrl' => route('store.backend.notification', $currentStore->slug),
        'latestId' => $__nLatestId,
        'isNotificationPage' => request()->routeIs('store.backend.notification'),
        'pollMs' => 8000,
        'sounds' => [
            'cod' => asset('sounds/notif-pembelian.wav'),     // COD (bayar setelah makanan jadi)
            'direct' => asset('sounds/notif-pembayaran.wav'), // Bayar langsung
        ],
    ]);
</script>

@if($__nAppId)
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
@endif

<script>
(function () {
    const cfg = window.NaumilNotifConfig;
    const MUTE_KEY = 'naumil_notif_muted';

    /* ---------- Audio ---------- */
    const sounds = { cod: new Audio(cfg.sounds.cod), direct: new Audio(cfg.sounds.direct) };
    Object.values(sounds).forEach(a => { a.preload = 'auto'; });
    let audioUnlocked = false;

    function emit(name, detail) { document.dispatchEvent(new CustomEvent(name, { detail: detail || {} })); }
    function isMuted() { return localStorage.getItem(MUTE_KEY) === '1'; }
    function setMuted(v) { localStorage.setItem(MUTE_KEY, v ? '1' : '0'); emit('naumil:audio-state'); }

    // Browser memblokir audio otomatis sebelum ada interaksi; "buka kunci" saat klik/tekan tombol pertama.
    function unlockAudio() {
        if (audioUnlocked) return;
        audioUnlocked = true;
        Object.values(sounds).forEach(a => {
            a.muted = true;
            const p = a.play();
            if (p && p.then) {
                p.then(() => { a.pause(); a.currentTime = 0; a.muted = false; emit('naumil:audio-state'); })
                 .catch(() => { a.muted = false; audioUnlocked = false; });
            }
        });
    }
    ['pointerdown', 'keydown', 'touchstart'].forEach(ev => document.addEventListener(ev, unlockAudio, { passive: true }));

    function playSound(type) {
        if (isMuted()) return false;
        const a = sounds[type] || sounds.cod;
        try {
            a.currentTime = 0;
            const p = a.play();
            if (p && p.catch) p.catch(() => { audioUnlocked = false; emit('naumil:audio-blocked'); });
            return true;
        } catch (e) { return false; }
    }

    /* ---------- Badge & Toast ---------- */
    function updateBadge(count) {
        document.querySelectorAll('[data-notif-badge]').forEach(el => {
            el.textContent = count > 99 ? '99+' : count;
            el.style.display = count > 0 ? '' : 'none';
        });
    }

    function rupiah(n) { return 'Rp ' + Math.round(n || 0).toLocaleString('id-ID'); }

    function showToast(item) {
        const host = document.getElementById('naumilToastHost');
        if (!host) return;
        const d = item.data || {};
        const a = document.createElement('a');
        a.className = 'naumil-toast ' + (item.type === 'direct' ? 'is-direct' : 'is-cod');
        a.href = cfg.pageUrl;

        const ico = document.createElement('div');
        ico.className = 't-ico';
        ico.innerHTML = '<i class="fa-solid ' + (item.type === 'direct' ? 'fa-circle-check' : 'fa-bell-concierge') + '"></i>';
        const box = document.createElement('div');
        const t = document.createElement('div'); t.className = 't-title'; t.textContent = item.title;
        const s = document.createElement('div'); s.className = 't-sub';
        s.textContent = (d.payment_label || '') + ' \u2022 ' + rupiah(d.total);
        box.append(t, s);
        a.append(ico, box);
        host.prepend(a);

        setTimeout(() => { a.classList.add('leaving'); setTimeout(() => a.remove(), 320); }, 9000);
    }

    /* ---------- Polling riwayat baru (realtime, tidak bergantung pada Reverb/queue) ---------- */
    let lastId = cfg.latestId || 0;
    let polling = false;
    const played = new Set();

    async function poll() {
        if (polling) return;
        polling = true;
        try {
            const res = await fetch(cfg.feedUrl + '?after_id=' + lastId + '&limit=50', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();
            updateBadge(data.unread_count);
            if (data.items && data.items.length) {
                lastId = Math.max(lastId, ...data.items.map(i => i.id));
                data.items.slice().reverse().forEach(item => {
                    if (!played.has(item.id)) { played.add(item.id); playSound(item.type); }
                    if (!cfg.isNotificationPage) showToast(item);
                });
                emit('naumil:new', { items: data.items, unread: data.unread_count });
            }
        } catch (e) { /* jaringan putus: coba lagi pada interval berikutnya */ }
        finally { polling = false; }
    }
    setInterval(poll, cfg.pollMs);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

    /* ---------- OneSignal Web Push ---------- */
    let OS = null;

    function pushState() {
        if (!cfg.appId) return 'unconfigured';
        if (!cfg.eligible) return 'not-eligible';
        if (!OS) return 'loading';
        if (typeof Notification === 'undefined' || !OS.Notifications.isPushSupported()) return 'unsupported';
        if (Notification.permission === 'denied') return 'blocked';
        if (Notification.permission === 'granted') return OS.User.PushSubscription.optedIn ? 'active' : 'optedout';
        return 'prompt';
    }
    function emitPushState() { emit('naumil:push-state', { state: pushState() }); }

    async function requestPush() {
        if (!OS) return pushState();
        unlockAudio();
        await OS.Notifications.requestPermission();
        if (Notification.permission === 'granted' && !OS.User.PushSubscription.optedIn) {
            await OS.User.PushSubscription.optIn();
        }
        emitPushState();
        return pushState();
    }

    if (cfg.appId) {
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        window.OneSignalDeferred.push(async function (OneSignal) {
            OS = OneSignal;
            await OneSignal.init({
                appId: cfg.appId,
                serviceWorkerPath: 'OneSignalSDKWorker.js',
                serviceWorkerParam: { scope: '/' },
                allowLocalhostAsSecureOrigin: true,
                notifyButton: { enable: false },
                ...(cfg.safariWebId ? { safari_web_id: cfg.safariWebId } : {}),
            });

            // Notifikasi hanya untuk admin toko yang SEDANG login: hubungkan perangkat ke akun ini
            if (cfg.externalId) {
                if (OneSignal.User.externalId !== cfg.externalId) await OneSignal.login(cfg.externalId);
            } else if (OneSignal.User.externalId) {
                await OneSignal.logout();
            }

            // Push masuk saat halaman terbuka -> mainkan suara sesuai tipe, lalu sinkronkan riwayat
            OneSignal.Notifications.addEventListener('foregroundWillDisplay', function (event) {
                const d = (event.notification && event.notification.additionalData) || {};
                const id = parseInt(d.notification_id, 10);
                if (id && !played.has(id)) { played.add(id); playSound(d.type); }
                poll();
            });
            OneSignal.Notifications.addEventListener('permissionChange', emitPushState);
            OneSignal.User.PushSubscription.addEventListener('change', emitPushState);
            emitPushState();
        });
    }

    // Saat logout: lepas perangkat dari akun (OneSignal.logout) agar tidak menerima notifikasi lagi
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('storeLogoutForm');
        if (!form || !cfg.appId) return;
        form.addEventListener('submit', function (e) {
            if (form.dataset.done) return;
            e.preventDefault();
            const go = () => { form.dataset.done = '1'; form.submit(); };
            const fallback = setTimeout(go, 2000);
            window.OneSignalDeferred.push(async function (OneSignal) {
                try { await OneSignal.logout(); } catch (err) {}
                clearTimeout(fallback);
                go();
            });
        });
    });

    window.NaumilNotif = { playSound, isMuted, setMuted, unlockAudio, requestPush, getPushState: pushState, poll, updateBadge, markPlayed: id => played.add(id) };
    emitPushState();
})();
</script>

