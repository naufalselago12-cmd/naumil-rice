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
            <span class="brand-name">NAUMIL RICE</span>
            <span class="brand-sub">KULINER NUSANTARA & OPEN PO</span>
          </div>
        </Link>

        <nav class="nav-links">
          <Link href="/" :class="['nav-link', isActive('/') ? 'active' : '']">
            <i class="fa-solid fa-bullhorn"></i> Open PO
          </Link>
          <Link href="/stores" :class="['nav-link', isActive('/stores') ? 'active' : '']">
            <i class="fa-solid fa-store"></i> Cabang Toko
          </Link>
          <Link href="/trace" :class="['nav-link', isActive('/trace') ? 'active' : '']">
            <i class="fa-solid fa-radar"></i> Lacak Pesanan
          </Link>
        </nav>

        <div class="nav-actions">
          <a href="/backend" target="_blank" class="btn-admin" title="Buka Admin LTE Backend">
            <i class="fa-solid fa-gauge"></i>
            <span class="d-none-mobile">Backend</span>
          </a>
          <a href="/backend/kds" target="_blank" class="btn-kds" title="Layar Kitchen Display">
            <i class="fa-solid fa-desktop"></i>
            <span class="d-none-mobile">KDS</span>
          </a>
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
              <span class="brand-name">NAUMIL RICE</span>
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
            <li><a href="/backend/api-docs" target="_blank">Dokumentasi API Backend</a></li>
          </ul>
        </div>

        <div class="footer-contact">
          <h4 class="footer-title">Hubungi Kami</h4>
          <p class="small text-muted mb-2">
            <i class="fa-solid fa-location-dot me-2 text-danger"></i> Flagship SCBD Sudirman & Tebet Kitchen
          </p>
          <a href="https://wa.me/6281298765432" target="_blank" class="wa-btn">
            <i class="fa-brands fa-whatsapp fs-5"></i> Customer Care WA
          </a>
        </div>
      </div>
      <div class="footer-bottom">
        <div class="container d-flex justify-content-between align-items-center">
          <p>&copy; 2026 Naumil Rice. All rights reserved.</p>
          <p class="font-mono small text-muted">Laravel 12 + Inertia Vue 3</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const isActive = (path) => {
  if (path === '/') {
    return page.url === '/'
  }
  return page.url.startsWith(path)
}
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

.btn-admin {
  background: #f1f5f9;
  color: #334155;
  border: 1px solid #cbd5e1;
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  font-size: 0.82rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: var(--transition);
}

.btn-admin:hover {
  background: #e2e8f0;
  color: #0f172a;
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
  .footer-content {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
}
</style>
