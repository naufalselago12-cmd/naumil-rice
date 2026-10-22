<template>
  <div class="app-layout">
    <!-- Top Navigation Bar -->
    <header class="header-glass">
      <div class="container nav-container">
        <Link href="/" class="brand-logo">
          <div class="brand-icon">
            <i class="fa-solid fa-bowl-rice"></i>
          </div>
          <div>
            <span class="brand-name">Naumil Store</span>
            <span class="brand-sub">KULINER NUSANTARA & OPEN PO</span>
          </div>
        </Link>

        <nav class="nav-links">
          <Link href="/" :class="['nav-link', isActive('/') ? 'active' : '']">
            <i class="fa-solid fa-store"></i> Cabang Toko
          </Link>
          <Link href="/trace" :class="['nav-link', isActive('/trace') ? 'active' : '']">
            <i class="fa-solid fa-radar"></i> Lacak Pesanan
          </Link>
          <Link href="/customer/profile" :class="['nav-link', isActive('/customer/profile') ? 'active' : '']">
            <i class="fa-solid fa-crown text-warning"></i> Pelanggan Setia
          </Link>
        </nav>

        <div class="nav-actions">
          <!-- Customer Loyalty Member State -->
          <template v-if="customerLoggedIn && customerData">
            <Link href="/customer/profile" class="btn-loyalty-member" title="Kunjungi Profil Pelanggan Setia">
              <div class="loyalty-icon-badge">
                <i class="fa-solid fa-crown text-warning"></i>
              </div>
              <div class="loyalty-text-box d-none-mobile">
                <span class="loyalty-name">{{ customerData.name }}</span>
                <span class="loyalty-points-badge font-mono">
                  <i class="fa-solid fa-coins text-warning me-1"></i>{{ customerData.loyalty_points || 0 }} Pts
                </span>
              </div>
            </Link>

            <button type="button" class="btn-logout" @click="handleCustomerLogout" title="Keluar Akun">
              <i class="fa-solid fa-right-from-bracket"></i>
            </button>
          </template>

          <!-- Guest State: Button to Visit Loyalty Portal -->
          <template v-else>
            <Link href="/customer/profile" class="btn-loyalty-cta" title="Daftar atau Masuk Pelanggan Setia">
              <i class="fa-solid fa-crown text-warning"></i>
              <span class="d-none-mobile">Masuk / Daftar Pelanggan</span>
              <span class="d-inline-mobile">Masuk</span>
            </Link>
          </template>

          <!-- Elegant Cart Button with Logo & Badge in Top Right -->
          <div class="nav-cart-wrapper">
            <button type="button" class="btn-nav-cart" @click="handleNavCartClick" title="Lihat Keranjang Belanja">
              <div class="nav-cart-icon-box">
                <i class="fa-solid fa-basket-shopping"></i>
                <span v-if="globalCartCount > 0" class="nav-cart-badge font-mono animate-bounce-subtle">
                  {{ globalCartCount > 99 ? '99+' : globalCartCount }}
                </span>
              </div>
              <span class="nav-cart-text d-none-mobile">
                <span class="nav-cart-label">Keranjang</span>
                <span v-if="globalCartTotal > 0" class="nav-cart-total font-mono">
                  Rp {{ formatNavPrice(globalCartTotal) }}
                </span>
                <span v-else class="text-muted" style="font-size: 0.72rem;">
                  0 Item
                </span>
              </span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main View Content -->
    <main class="main-content">
      <slot />
    </main>

    <!-- Modern App Footer -->
    <footer class="app-footer">
      <div class="container footer-content">
        <div class="footer-brand">
          <div class="brand-logo mb-2">
            <div class="brand-icon">
              <i class="fa-solid fa-bowl-rice"></i>
            </div>
            <div>
              <span class="brand-name">Naumil Store</span>
              <span class="brand-sub">PRE-ORDER AUTHENTIC RICE BOWL</span>
            </div>
          </div>
          <p class="footer-desc">
            Sajian kuliner nasi aromatik dan aneka lauk istimewa nusantara dengan sistem Open Pre-Order batch teratur, diolah fresh dari dapur pilihan setiap cabang.
          </p>
        </div>

        <div class="footer-links">
          <h4 class="footer-title">Navigasi Cepat</h4>
          <ul>
            <li><Link href="/">Daftar Open PO Aktif</Link></li>
            <li><Link href="/stores">Daftar Cabang & Dapur</Link></li>
            <li><Link href="/trace">Lacak Status Pesanan (Live)</Link></li>
          </ul>
        </div>

        <div class="footer-contact">
          <h4 class="footer-title">Hubungi Kami</h4>
          <a href="https://wa.me/081779954574" target="_blank" class="wa-btn">
            <i class="fa-brands fa-whatsapp fs-5"></i> Customer Care WA
          </a>
        </div>
      </div>
      <div class="footer-bottom">
        <div class="container d-flex justify-content-between align-items-center">
          <p>&copy; 2026 Naumil Store. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import axios from 'axios'

const page = usePage()

const customerLoggedIn = ref(false)
const customerData = ref(null)

const isActive = (path) => {
  if (path === '/') {
    return page.url === '/'
  }
  return page.url.startsWith(path)
}

const syncCustomerSession = async () => {
  // 1. Check local storage
  const cached = localStorage.getItem('naumil_customer_session')
  if (cached) {
    try {
      const user = JSON.parse(cached)
      if (user && user.name) {
        customerData.value = user
        customerLoggedIn.value = true
      }
    } catch (e) {}
  }

  // 2. Fetch server session
  try {
    const res = await axios.get('/api/v1/customer/current-session')
    if (res.data?.authenticated && res.data?.client) {
      customerData.value = res.data.client
      customerLoggedIn.value = true
      localStorage.setItem('naumil_customer_session', JSON.stringify(res.data.client))
    } else if (!cached) {
      customerLoggedIn.value = false
      customerData.value = null
    }
  } catch (e) {}
}

const handleCustomerLogout = async () => {
  if (!confirm('Apakah Anda yakin ingin keluar dari akun Pelanggan Setia?')) return

  try {
    await axios.post('/api/v1/customer/logout')
  } catch (e) {}

  localStorage.removeItem('naumil_customer_session')
  localStorage.removeItem('naumil_user')
  customerLoggedIn.value = false
  customerData.value = null
  router.visit('/')
}

const globalCartCount = ref(0)
const globalCartTotal = ref(0)

const formatNavPrice = (val) => {
  return new Intl.NumberFormat('id-ID').format(Math.round(val || 0))
}

const syncGlobalCart = () => {
  try {
    const raw = localStorage.getItem('naumil_customer_cart_backup')
    if (raw) {
      const items = JSON.parse(raw)
      if (Array.isArray(items)) {
        globalCartCount.value = items.reduce((acc, it) => acc + (Number(it.qty || it.quantity) || 1), 0)
        globalCartTotal.value = items.reduce((acc, it) => acc + (Number(it.subtotal) || 0), 0)
        return
      }
    }
  } catch (e) {}
  globalCartCount.value = 0
  globalCartTotal.value = 0
}

const handleNavCartClick = () => {
  // Dispatch custom event for current page to open its cart modal
  window.dispatchEvent(new CustomEvent('open-naumil-cart', {
    detail: { count: globalCartCount.value, total: globalCartTotal.value }
  }))

  // If on non-storefront pages (e.g. root /, trace, or customer profile)
  const path = window.location.pathname
  if (path === '/' || path.startsWith('/trace') || path.startsWith('/customer')) {
    const returnUrl = localStorage.getItem('naumil_return_store_url')
    const lastStore = localStorage.getItem('naumil_last_store_slug')
    if (returnUrl && returnUrl !== window.location.href) {
      window.location.href = returnUrl
    } else if (lastStore) {
      window.location.href = '/' + lastStore
    } else {
      window.location.href = '/'
    }
  }
}

onMounted(() => {
  syncCustomerSession()
  syncGlobalCart()

  // Listen to cart update events dispatched from StoreFront or Campaign
  window.addEventListener('naumil-cart-updated', (e) => {
    if (e.detail) {
      globalCartCount.value = Number(e.detail.count) || 0
      globalCartTotal.value = Number(e.detail.total) || 0
    } else {
      syncGlobalCart()
    }
  })
})
</script>

<style scoped>
.app-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.nav-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 0.85rem;
  padding-bottom: 0.85rem;
}

.brand-logo {
  display: flex;
  align-items: center;
  gap: 10px;
}

.brand-icon {
  width: 42px;
  height: 42px;
  background: linear-gradient(135deg, var(--primary) 0%, #be123c 100%);
  color: white;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  box-shadow: 0 4px 10px rgba(225, 29, 72, 0.3);
}

.brand-name {
  display: block;
  font-family: var(--font-display);
  font-weight: 800;
  font-size: 1.2rem;
  letter-spacing: -0.02em;
  color: var(--dark);
  line-height: 1.1;
}

.brand-sub {
  display: block;
  font-size: 0.65rem;
  font-weight: 700;
  color: var(--primary);
  letter-spacing: 0.06em;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.nav-link {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 6px;
  transition: var(--transition);
  padding: 6px 12px;
  border-radius: var(--radius-sm);
}

.nav-link:hover, .nav-link.active {
  color: var(--primary);
  background: var(--primary-light);
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-loyalty-member {
  background: #fffbeb;
  border: 1.5px solid #fde68a;
  color: #78350f;
  padding: 4px 10px;
  border-radius: var(--radius-md);
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  transition: var(--transition);
}

.btn-loyalty-member:hover {
  background: #fef3c7;
  border-color: #f59e0b;
  transform: translateY(-1px);
}

.loyalty-icon-badge {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #fef3c7;
  border: 1px solid #fde68a;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
}

.loyalty-text-box {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}

.loyalty-name {
  font-size: 0.82rem;
  font-weight: 700;
  color: #1e293b;
  max-width: 130px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.loyalty-points-badge {
  font-size: 0.72rem;
  font-weight: 700;
  color: #b45309;
}

.btn-logout {
  background: transparent;
  border: 1px solid #e2e8f0;
  color: #64748b;
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: var(--transition);
}

.btn-logout:hover {
  background: #fee2e2;
  color: #ef4444;
  border-color: #fca5a5;
}

.btn-loyalty-cta {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border: 1px solid #f59e0b;
  color: #78350f;
  padding: 6px 14px;
  border-radius: var(--radius-md);
  font-size: 0.84rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  transition: var(--transition);
  box-shadow: 0 2px 6px rgba(245, 158, 11, 0.2);
}

.btn-loyalty-cta:hover {
  background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%);
  transform: translateY(-1px);
  color: #78350f;
}

.d-inline-mobile {
  display: none;
}

.btn-kds {
  background: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fecaca;
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  font-size: 0.82rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: var(--transition);
}

.btn-kds:hover {
  background: #fca5a5;
  color: #7f1d1d;
}

.main-content {
  flex: 1;
}

/* Footer */
.app-footer {
  background: white;
  border-top: 1px solid var(--border);
  margin-top: 4rem;
}

.footer-content {
  padding-top: 3.5rem;
  padding-bottom: 2.5rem;
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 2.5rem;
}

.footer-desc {
  color: var(--text-muted);
  font-size: 0.9rem;
  max-width: 420px;
  line-height: 1.6;
}

.footer-title {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: var(--dark);
}

.footer-links ul {
  list-style: none;
}

.footer-links li {
  margin-bottom: 0.6rem;
}

.footer-links a {
  color: var(--text-muted);
  font-size: 0.88rem;
  transition: var(--transition);
}

.footer-links a:hover {
  color: var(--primary);
  padding-left: 4px;
}

.wa-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #22c55e;
  color: white;
  padding: 8px 16px;
  border-radius: var(--radius-md);
  font-weight: 600;
  font-size: 0.88rem;
  transition: var(--transition);
}

.wa-btn:hover {
  background: #16a34a;
  transform: translateY(-2px);
}

.footer-bottom {
  border-top: 1px solid var(--border);
  padding: 1.25rem 0;
  font-size: 0.82rem;
  color: var(--text-muted);
}

@media (max-width: 768px) {
  .nav-links {
    display: none;
  }
  .d-none-mobile {
    display: none;
  }
  .d-inline-mobile {
    display: inline;
  }
  .footer-content {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
}
</style>
