<template>
  <AppLayout>
    <div class="home-stores-page">
      <!-- Hero Section -->
      <section class="hero-section">
        <div class="container hero-container">
          <div class="hero-text">
            <div class="badge badge-amber mb-3">
              <i class="fa-solid fa-fire text-danger"></i> MULTI-STORE FOOD & PRE-ORDER
            </div>
            <h1 class="hero-title">
              Pilih Cabang Dapur <span class="highlight-text">Naumil Store</span> Terdekat Anda.
            </h1>
            <p class="hero-subtitle">
              Setiap cabang Naumil Store dilengkapi dapur mandiri, menu otentik nusantara, batch Open Pre-Order teratur, dan sistem QRIS dinamis resmi.
            </p>
            <div class="hero-actions">
              <a href="#stores-list" class="btn btn-primary">
                <i class="fa-solid fa-store"></i> Pilih Cabang Toko
              </a>
              <Link href="/trace" class="btn-secondary">
                <i class="fa-solid fa-radar"></i> Lacak Pesanan Anda
              </Link>
            </div>
          </div>

          <div class="hero-image-wrapper">
            <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&q=80" 
                 alt="Naumil Store Bowl" class="hero-main-img" />
            <div class="hero-floating-card">
              <div class="floating-icon"><i class="fa-solid fa-kitchen-set"></i></div>
              <div>
                <div class="fw-bold fs-6">Dapur Mandiri Per Cabang</div>
                <small class="text-muted">Dimasak fresh saat batch siap</small>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Quick Trace Bar -->
      <section class="lookup-section">
        <div class="container">
          <div class="lookup-card">
            <div class="lookup-info">
              <i class="fa-solid fa-magnifying-glass-location text-danger fs-3"></i>
              <div>
                <h4 class="mb-0 fs-6 fw-bold">Sudah Punya Nomor Pesanan?</h4>
                <p class="text-muted small mb-0">Masukkan nomor order untuk memantau status memasak dapur secara langsung.</p>
              </div>
            </div>
            <div class="lookup-form">
              <input type="text" v-model="trackOrderInput" placeholder="Contoh: NR-PO-SDR-261012-001" 
                     class="form-input text-uppercase font-mono" @keyup.enter="goToTrace" />
              <button class="btn btn-primary" @click="goToTrace">
                <i class="fa-solid fa-arrow-right"></i> Lacak
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Stores Looping Section -->
      <section class="stores-section" id="stores-list">
        <div class="container">
          <div class="section-head text-center mb-4">
            <span class="badge badge-primary mb-2">JARINGAN OUTLET & DAPUR</span>
            <h2 class="section-title">Daftar Cabang & Toko Naumil Store</h2>
            <p class="text-muted">Pilih cabang terdekat untuk melihat menu, promo batch pre-order, dan melakukan pemesanan.</p>
          </div>

          <!-- Search / Filter Toko -->
          <div class="store-filter-bar mb-4 max-w-500 mx-auto">
            <div class="input-with-icon">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input type="text" v-model="searchQuery" placeholder="Cari nama cabang atau lokasi..." 
                     class="form-input" />
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-custom"></div>
            <p class="text-muted mt-3">Memuat data cabang toko...</p>
          </div>

          <!-- Stores Grid (Looping per Toko) -->
          <div v-else class="stores-grid">
            <div v-for="store in filteredStores" :key="store.id" class="card store-card">
              <!-- Banner Image with Store Logo -->
              <div class="store-banner-wrapper">
                <img :src="store.banner || 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&q=80'" 
                     :alt="store.name" class="store-banner-img" />
                <div class="store-banner-overlay">
                  <span class="badge badge-dark font-mono">{{ store.code }}</span>
                  <span class="badge badge-green"><i class="fa-solid fa-circle me-1" style="font-size: 0.6rem;"></i> BUKA</span>
                </div>
                <img :src="store.logo || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=120&q=80'" 
                     :alt="store.name" class="store-avatar-badge" />
              </div>

              <!-- Store Body -->
              <div class="store-card-body p-4 pt-5">
                <h3 class="store-title mb-1">
                  <Link :href="'/' + (store.slug || store.code)" class="text-decoration-none text-dark">
                    {{ store.name }}
                  </Link>
                </h3>
                
                <p class="store-address text-muted small mb-3">
                  <i class="fa-solid fa-location-dot text-danger me-1"></i> {{ store.address }}
                </p>

                <!-- Store Stats Pills -->
                <div class="store-stats-box mb-3 p-2 rounded bg-light border d-flex justify-content-around text-center">
                  <div>
                    <div class="fw-bold font-mono text-dark">{{ store.menus_count || 0 }}</div>
                    <small class="text-muted" style="font-size: 0.72rem;">Menu Makanan</small>
                  </div>
                  <div class="border-start"></div>
                  <div>
                    <div class="fw-bold font-mono text-danger">{{ store.campaigns_count || 0 }}</div>
                    <small class="text-muted" style="font-size: 0.72rem;">Open PO Aktif</small>
                  </div>
                  <div class="border-start"></div>
                  <div>
                    <div class="fw-bold font-mono text-success"><i class="fa-solid fa-qrcode"></i></div>
                    <small class="text-muted" style="font-size: 0.72rem;">QRIS Dinamis</small>
                  </div>
                </div>

                <!-- Action CTA Buttons -->
                <div class="d-flex flex-column gap-2">
                  <!-- Main Button: base_url/(nama_toko) -->
                  <Link :href="'/' + (store.slug || store.code)" class="btn btn-primary w-100 text-center py-2">
                    <i class="fa-solid fa-utensils me-1"></i> Kunjungi Toko & Pesan
                  </Link>

                  <!-- Secondary Buttons: WA & Store Backend -->
                  <div class="d-flex gap-2">
                    <a v-if="store.phone" :href="'https://wa.me/' + cleanPhone(store.phone)" target="_blank" 
                       class="btn btn-secondary flex-grow-1 text-center py-2" title="WhatsApp Cabang">
                      <i class="fa-brands fa-whatsapp text-success"></i> WhatsApp
                    </a>
                    
                    <!-- Store Backend Link: base_url/(nama_toko)/backend (Khusus Admin & Super Admin) -->
                    <a v-if="store.can_manage_backend" :href="'/' + (store.slug || store.code) + '/backend'" target="_blank" 
                       class="btn-secondary flex-grow-1 text-center py-2" title="Masuk Panel Dapur Toko">
                      <i class="fa-solid fa-gauge text-primary"></i> Backend
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Loyalty Member Portal Banner (Upgraded VIP Luxury Edition) -->
      <section class="loyalty-banner-section my-5">
        <div class="container">
          <div class="loyalty-vip-showcase">
            <!-- Ambient Glow Overlays & Background Grid -->
            <div class="ambient-glow glow-gold"></div>
            <div class="ambient-glow glow-rose"></div>
            <div class="luxury-pattern-grid"></div>

            <div class="vip-showcase-inner">
              <!-- Left Col: Value Proposition & Perks -->
              <div class="vip-info-col">
                <!-- Exclusive Top Badge Pill -->
                <div class="vip-tag-wrapper mb-3">
                  <span class="vip-tag-pill">
                    <span class="vip-tag-icon"><i class="fa-solid fa-crown text-warning"></i></span>
                    <span class="vip-tag-text">PROGRAM PELANGGAN SETIA</span>
                    <span class="vip-tag-sparkle"><i class="fa-solid fa-sparkles text-warning"></i> VIP REWARD</span>
                  </span>
                  <span v-if="customerLoggedIn" class="vip-status-online">
                    <span class="status-pulse-dot"></span> Member Aktif
                  </span>
                </div>

                <!-- Main Catchy Title -->
                <h2 class="vip-heading">
                  <template v-if="customerLoggedIn && customerData">
                    Halo, <span class="gold-gradient-text">{{ customerData.name }}</span>! Nikmati Privilese Eksklusif Anda.
                  </template>
                  <template v-else>
                    Makin Sering Pesan, <span class="gold-gradient-text">Makin Banyak Untungnya</span>.
                  </template>
                </h2>

                <p class="vip-subheading">
                  Kumpulkan <strong>Credit Points</strong> di setiap transaksi menu pilihan, nikmati diskon khusus member, dan tukarkan poin reward untuk traktiran lezat di seluruh cabang Naumil Store.
                </p>

                <!-- 3 Visual Perks Mini-Cards -->
                <div class="vip-perks-grid mb-4">
                  <div class="perk-card">
                    <div class="perk-icon-box bg-gold-glow">
                      <i class="fa-solid fa-coins text-warning"></i>
                    </div>
                    <div class="perk-content">
                      <div class="perk-title">Poin Otomatis</div>
                      <div class="perk-desc">Tiap pesanan langsung berbuah Credit Points</div>
                    </div>
                  </div>

                  <div class="perk-card">
                    <div class="perk-icon-box bg-rose-glow">
                      <i class="fa-solid fa-percent text-danger" style="color: #fb7185 !important;"></i>
                    </div>
                    <div class="perk-content">
                      <div class="perk-title">Diskon Member</div>
                      <div class="perk-desc">Akses harga spesial promo batch pre-order</div>
                    </div>
                  </div>

                  <div class="perk-card">
                    <div class="perk-icon-box bg-emerald-glow">
                      <i class="fa-solid fa-gift text-success" style="color: #34d399 !important;"></i>
                    </div>
                    <div class="perk-content">
                      <div class="perk-title">Tukar Menu Gratis</div>
                      <div class="perk-desc">Redeem poin jadi hidangan nusantara favorit</div>
                    </div>
                  </div>
                </div>

                <!-- Action CTA Buttons -->
                <div class="vip-actions-row">
                  <Link href="/customer/profile" class="btn-vip-primary">
                    <i class="fa-solid fa-crown me-2"></i>
                    <span>{{ customerLoggedIn ? 'Buka Portal & Cek Saldo Poin' : 'Buka Portal Pelanggan Setia' }}</span>
                    <i class="fa-solid fa-arrow-right ms-2 arrow-icon"></i>
                  </Link>

                  <Link v-if="!customerLoggedIn" href="/customer/profile" class="btn-vip-ghost">
                    <i class="fa-solid fa-user-plus me-1 text-warning"></i>
                    <span>Daftar / Masuk Member</span>
                  </Link>
                  
                  <Link v-else href="/trace" class="btn-vip-ghost">
                    <i class="fa-solid fa-clock-rotate-left me-1 text-warning"></i>
                    <span>Lacak & Riwayat Order</span>
                  </Link>
                </div>
              </div>

              <!-- Right Col: 3D Floating VIP Card Graphic -->
              <div class="vip-card-col">
                <div class="vip-card-container">
                  <!-- The VIP Member Physical-look Card -->
                  <div class="luxury-member-card">
                    <div class="card-glass-sheen"></div>
                    <div class="card-top-row">
                      <div class="card-brand-mark">
                        <div class="brand-chip-icon">
                          <i class="fa-solid fa-bowl-rice text-warning"></i>
                        </div>
                        <div class="brand-meta">
                          <span class="brand-title">NAUMIL STORE</span>
                          <span class="brand-tier">VIP REWARDS CLUB</span>
                        </div>
                      </div>
                      <div class="card-nfc">
                        <i class="fa-solid fa-wifi text-warning-50"></i>
                      </div>
                    </div>

                    <!-- EMV Chip Graphic -->
                    <div class="card-chip-wrapper">
                      <div class="emv-chip">
                        <div class="chip-line horizontal"></div>
                        <div class="chip-line vertical"></div>
                      </div>
                      <span class="chip-label font-mono">PRIORITY PASS</span>
                    </div>

                    <!-- Card Number (Masked) -->
                    <div class="card-number-box font-mono">
                      <span>8820</span>
                      <span>••••</span>
                      <span>••••</span>
                      <span>{{ customerLoggedIn && customerData?.id ? String(customerData.id).padStart(4, '0') : '2026' }}</span>
                    </div>

                    <!-- Card Bottom Details -->
                    <div class="card-bottom-row">
                      <div class="card-holder-box">
                        <span class="card-label">NAMA ANGGOTA</span>
                        <div class="card-holder-name text-truncate">
                          {{ customerLoggedIn && customerData?.name ? customerData.name : 'VALUED CUSTOMER' }}
                        </div>
                      </div>

                      <div class="card-points-badge">
                        <span class="card-label">SALDO POIN</span>
                        <div class="card-points-val font-mono">
                          <i class="fa-solid fa-coins text-warning me-1"></i>
                          <span>{{ customerLoggedIn && customerData?.loyalty_points !== undefined ? customerData.loyalty_points : 'AKTIF' }}</span>
                          <small v-if="customerLoggedIn" class="ms-1 font-sans">Pts</small>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Floating Trust Badge Below Card -->
                  <div class="card-floating-badge">
                    <div class="floating-badge-icon">
                      <i class="fa-solid fa-shield-halved text-warning"></i>
                    </div>
                    <div class="floating-badge-text">
                      <div class="fw-bold">Multi-Branch Integration</div>
                      <small class="text-white-50">Terkoneksi Seluruh Cabang & Dapur Naumil</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  stores: {
    type: Array,
    default: () => []
  }
})

const storeList = ref(props.stores || [])
const loading = ref(storeList.value.length === 0)
const searchQuery = ref('')
const trackOrderInput = ref('')

// Customer Loyalty State
const customerLoggedIn = ref(false)
const customerData = ref(null)

const syncCustomerSession = () => {
  try {
    const cached = localStorage.getItem('naumil_customer_session')
    if (cached) {
      const user = JSON.parse(cached)
      if (user && user.name) {
        customerData.value = user
        customerLoggedIn.value = true
      }
    }
  } catch (e) {}
}

const fetchStores = async () => {
  if (storeList.value.length > 0) {
    loading.value = false
    return
  }
  try {
    loading.value = true
    const res = await axios.get('/api/v1/stores')
    if (res.data.success) {
      storeList.value = res.data.data
    }
  } catch (err) {
    console.error('Failed to load stores', err)
  } finally {
    loading.value = false
  }
}

const filteredStores = computed(() => {
  if (!searchQuery.value) return storeList.value
  const q = searchQuery.value.toLowerCase()
  return storeList.value.filter(s => 
    s.name.toLowerCase().includes(q) || 
    (s.address && s.address.toLowerCase().includes(q)) ||
    (s.code && s.code.toLowerCase().includes(q)) ||
    (s.slug && s.slug.toLowerCase().includes(q))
  )
})

const cleanPhone = (phone) => {
  if (!phone) return ''
  return phone.replace(/[^0-9]/g, '').replace(/^0/, '62')
}

const goToTrace = () => {
  if (!trackOrderInput.value) return
  router.visit('/trace/' + encodeURIComponent(trackOrderInput.value.trim()))
}

onMounted(() => {
  fetchStores()
  syncCustomerSession()
})
</script>

<style scoped>
.hero-section {
  padding: 4rem 0 3rem 0;
  background: linear-gradient(180deg, #fef2f2 0%, #ffffff 100%);
}

.hero-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3rem;
  align-items: center;
}

.hero-title {
  font-size: 2.8rem;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 1.25rem;
}

.highlight-text {
  color: var(--primary, #e11d48);
}

.hero-subtitle {
  font-size: 1.1rem;
  color: #475569;
  line-height: 1.6;
  margin-bottom: 2rem;
}

.hero-actions {
  display: flex;
  gap: 1rem;
}

.hero-image-wrapper {
  position: relative;
}

.hero-main-img {
  width: 100%;
  height: 380px;
  object-fit: cover;
  border-radius: 24px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
}

.hero-floating-card {
  position: absolute;
  bottom: -20px;
  left: 20px;
  background: white;
  padding: 1rem 1.25rem;
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 12px;
}

.floating-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background-color: #fee2e2;
  color: #e11d48;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

/* Lookup Section */
.lookup-section {
  margin-top: -25px;
  position: relative;
  z-index: 10;
  margin-bottom: 3.5rem;
}

.lookup-card {
  background: white;
  border-radius: 16px;
  padding: 1.25rem 2rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--border, #e2e8f0);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
}

.lookup-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.lookup-form {
  display: flex;
  gap: 0.5rem;
  min-width: 340px;
}

/* Stores Grid */
.stores-section {
  padding: 2rem 0 4rem 0;
}

.stores-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 2rem;
}

.store-card {
  border-radius: 20px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  transition: transform 0.25s, box-shadow 0.25s;
}

.store-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
}

.store-banner-wrapper {
  position: relative;
  height: 140px;
  background-color: #f1f5f9;
}

.store-banner-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.store-banner-overlay {
  position: absolute;
  top: 12px;
  left: 12px;
  right: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.store-avatar-badge {
  position: absolute;
  bottom: -28px;
  left: 20px;
  width: 60px;
  height: 60px;
  border-radius: 14px;
  object-fit: cover;
  border: 3px solid white;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
  background-color: white;
}

.store-title {
  font-size: 1.25rem;
  font-weight: 800;
}

.store-address {
  line-height: 1.4;
  height: 2.8em;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.input-with-icon {
  position: relative;
}

.input-with-icon i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.input-with-icon .form-input {
  padding-left: 40px;
  border-radius: 12px;
}

@media (max-width: 768px) {
  .hero-container {
    grid-template-columns: 1fr;
    text-align: center;
  }
  .hero-actions {
    justify-content: center;
  }
  .hero-floating-card {
    display: none;
  }
  .lookup-card {
    flex-direction: column;
    text-align: center;
  }
  .lookup-form {
    width: 100%;
    min-width: unset;
  }
  .stores-grid {
    grid-template-columns: 1fr;
  }
}

/* ======================================================== */
/* UPGRADED VIP LOYALTY SHOWCASE BANNER STYLING             */
/* ======================================================== */
.loyalty-banner-section {
  position: relative;
  margin-top: 3.5rem;
  margin-bottom: 4rem;
}

.loyalty-vip-showcase {
  position: relative;
  border-radius: 28px;
  overflow: hidden;
  background: radial-gradient(120% 120% at 85% 15%, rgba(225, 29, 72, 0.28) 0%, transparent 60%),
              radial-gradient(100% 100% at 15% 85%, rgba(245, 158, 11, 0.2) 0%, transparent 55%),
              linear-gradient(140deg, #12050a 0%, #200712 35%, #3d0515 70%, #16040c 100%);
  border: 1px solid rgba(245, 158, 11, 0.35);
  box-shadow: 0 25px 60px -15px rgba(20, 5, 11, 0.65), 
              0 0 30px rgba(245, 158, 11, 0.12),
              inset 0 1px 0 rgba(255, 255, 255, 0.1);
  padding: 3.5rem 3.25rem;
}

/* Ambient Glow Orbs */
.ambient-glow {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  filter: blur(60px);
  z-index: 1;
}

.glow-gold {
  width: 320px;
  height: 320px;
  top: -80px;
  right: 25%;
  background: radial-gradient(circle, rgba(245, 158, 11, 0.28) 0%, transparent 70%);
}

.glow-rose {
  width: 380px;
  height: 380px;
  bottom: -90px;
  left: 5%;
  background: radial-gradient(circle, rgba(225, 29, 72, 0.3) 0%, transparent 70%);
}

.luxury-pattern-grid {
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
  background-size: 24px 24px;
  opacity: 0.7;
  pointer-events: none;
  z-index: 1;
}

.vip-showcase-inner {
  position: relative;
  z-index: 2;
  display: grid;
  grid-template-columns: 1.15fr 0.85fr;
  gap: 3rem;
  align-items: center;
}

/* VIP Tag Top Row */
.vip-tag-wrapper {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  flex-wrap: wrap;
}

.vip-tag-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid rgba(245, 158, 11, 0.38);
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #fef08a;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.vip-tag-sparkle {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #ffffff;
  font-size: 0.65rem;
  padding: 0.15rem 0.45rem;
  border-radius: 9999px;
  font-weight: 800;
}

.vip-status-online {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #86efac;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.3);
  padding: 0.35rem 0.75rem;
  border-radius: 9999px;
}

.status-pulse-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background-color: #22c55e;
  box-shadow: 0 0 8px #22c55e;
  animation: pulseGreen 1.8s infinite;
}

@keyframes pulseGreen {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
  70% { transform: scale(1.1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}

/* Headings */
.vip-heading {
  font-size: 2.15rem;
  font-weight: 800;
  color: #ffffff;
  line-height: 1.25;
  margin-bottom: 0.85rem;
  letter-spacing: -0.02em;
}

.gold-gradient-text {
  background: linear-gradient(135deg, #fffbeb 0%, #fde047 35%, #f59e0b 80%, #d97706 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  display: inline-block;
  font-weight: 800;
}

.vip-subheading {
  color: #cbd5e1;
  font-size: 0.96rem;
  line-height: 1.6;
  max-width: 560px;
  margin-bottom: 1.75rem;
}

/* Perks Grid */
.vip-perks-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.85rem;
}

.perk-card {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(12px);
  border-radius: 16px;
  padding: 1rem 0.85rem;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.perk-card:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(245, 158, 11, 0.4);
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25);
}

.perk-icon-box {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  margin-bottom: 0.6rem;
}

.bg-gold-glow {
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.bg-rose-glow {
  background: rgba(225, 29, 72, 0.15);
  border: 1px solid rgba(225, 29, 72, 0.3);
}

.bg-emerald-glow {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.perk-title {
  color: #ffffff;
  font-weight: 700;
  font-size: 0.84rem;
  margin-bottom: 0.25rem;
}

.perk-desc {
  color: #94a3b8;
  font-size: 0.72rem;
  line-height: 1.35;
}

/* Actions Row */
.vip-actions-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-vip-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #fde047 0%, #f59e0b 50%, #d97706 100%);
  color: #451a03 !important;
  font-weight: 800;
  font-size: 0.92rem;
  padding: 0.85rem 1.6rem;
  border-radius: 14px;
  text-decoration: none;
  box-shadow: 0 10px 25px rgba(245, 158, 11, 0.35);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.btn-vip-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(245, 158, 11, 0.5);
  color: #260d02 !important;
}

.btn-vip-primary .arrow-icon {
  transition: transform 0.25s ease;
}

.btn-vip-primary:hover .arrow-icon {
  transform: translateX(4px);
}

.btn-vip-ghost {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.18);
  color: #ffffff !important;
  font-weight: 700;
  font-size: 0.9rem;
  padding: 0.85rem 1.4rem;
  border-radius: 14px;
  text-decoration: none;
  backdrop-filter: blur(8px);
  transition: all 0.25s ease;
}

.btn-vip-ghost:hover {
  background: rgba(255, 255, 255, 0.12);
  border-color: rgba(245, 158, 11, 0.45);
  color: #ffffff !important;
  transform: translateY(-2px);
}

/* ======================================================== */
/* 3D VIRTUAL VIP MEMBER CARD GRAPHIC                      */
/* ======================================================== */
.vip-card-col {
  display: flex;
  justify-content: center;
  align-items: center;
}

.vip-card-container {
  width: 100%;
  max-width: 380px;
  display: flex;
  flex-direction: column;
  align-items: center;
  perspective: 1000px;
}

.luxury-member-card {
  width: 100%;
  aspect-ratio: 1.58 / 1;
  border-radius: 20px;
  padding: 1.4rem 1.5rem;
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #1e1e24 0%, #121217 35%, #1d141a 75%, #2c0e18 100%);
  border: 1.5px solid rgba(245, 158, 11, 0.45);
  box-shadow: 0 25px 45px -10px rgba(0, 0, 0, 0.7), 
              0 0 25px rgba(245, 158, 11, 0.18),
              inset 0 1px 1px rgba(255, 255, 255, 0.25);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  animation: vipCardFloat 5s ease-in-out infinite;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.luxury-member-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 30px 55px -10px rgba(0, 0, 0, 0.8), 
              0 0 35px rgba(245, 158, 11, 0.3);
}

@keyframes vipCardFloat {
  0%, 100% {
    transform: translateY(0) rotate(0deg);
  }
  50% {
    transform: translateY(-8px) rotate(0.6deg);
  }
}

.card-glass-sheen {
  position: absolute;
  top: -60%;
  left: -60%;
  width: 220%;
  height: 220%;
  background: linear-gradient(60deg, transparent 42%, rgba(255, 255, 255, 0.08) 50%, transparent 58%);
  pointer-events: none;
}

.card-top-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-brand-mark {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.brand-chip-icon {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid rgba(245, 158, 11, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
}

.brand-meta {
  display: flex;
  flex-direction: column;
}

.brand-title {
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  color: #ffffff;
}

.brand-tier {
  font-size: 0.6rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  color: #f59e0b;
}

.card-nfc {
  font-size: 1.1rem;
  color: rgba(251, 191, 36, 0.6);
}

/* EMV Chip */
.card-chip-wrapper {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0.4rem 0;
}

.emv-chip {
  width: 42px;
  height: 30px;
  border-radius: 6px;
  background: linear-gradient(135deg, #ffd700 0%, #caa024 50%, #e6b800 100%);
  border: 1px solid rgba(0, 0, 0, 0.35);
  position: relative;
  overflow: hidden;
  box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.45);
}

.emv-chip .chip-line.horizontal {
  position: absolute;
  width: 100%;
  height: 1px;
  top: 50%;
  left: 0;
  background: rgba(0, 0, 0, 0.35);
}

.emv-chip .chip-line.vertical {
  position: absolute;
  width: 1px;
  height: 100%;
  left: 50%;
  top: 0;
  background: rgba(0, 0, 0, 0.35);
}

.chip-label {
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.15em;
  color: rgba(255, 255, 255, 0.4);
}

/* Card Number */
.card-number-box {
  display: flex;
  gap: 0.75rem;
  font-size: 1.05rem;
  letter-spacing: 0.12em;
  color: #f1f5f9;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.6);
  font-weight: 600;
}

/* Card Bottom Details */
.card-bottom-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  padding-top: 0.4rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.card-holder-box {
  max-width: 60%;
}

.card-label {
  display: block;
  font-size: 0.58rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: rgba(255, 255, 255, 0.45);
  margin-bottom: 0.15rem;
}

.card-holder-name {
  color: #ffffff;
  font-weight: 800;
  font-size: 0.8rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.card-points-badge {
  text-align: right;
}

.card-points-val {
  color: #fde047;
  font-weight: 800;
  font-size: 0.95rem;
  letter-spacing: 0.02em;
  text-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
}

/* Floating Trust Badge */
.card-floating-badge {
  margin-top: 1rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  padding: 0.55rem 1rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 0.65rem;
  width: 100%;
}

.floating-badge-icon {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: rgba(245, 158, 11, 0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
}

.floating-badge-text div {
  font-size: 0.76rem;
  color: #ffffff;
}

.floating-badge-text small {
  font-size: 0.68rem;
}

/* Responsive Media Queries for Loyalty Showcase */
@media (max-width: 992px) {
  .vip-showcase-inner {
    grid-template-columns: 1fr;
    gap: 2.5rem;
  }
  .loyalty-vip-showcase {
    padding: 2.5rem 2rem;
  }
  .vip-info-col {
    text-align: center;
  }
  .vip-tag-wrapper {
    justify-content: center;
  }
  .vip-subheading {
    margin-left: auto;
    margin-right: auto;
  }
  .vip-perks-grid {
    text-align: left;
  }
  .vip-actions-row {
    justify-content: center;
  }
}

@media (max-width: 640px) {
  .loyalty-vip-showcase {
    padding: 2rem 1.25rem;
    border-radius: 20px;
  }
  .vip-heading {
    font-size: 1.55rem;
  }
  .vip-perks-grid {
    grid-template-columns: 1fr;
    gap: 0.65rem;
  }
  .vip-actions-row {
    flex-direction: column;
    width: 100%;
  }
  .btn-vip-primary, .btn-vip-ghost {
    width: 100%;
  }
  .luxury-member-card {
    padding: 1.2rem;
  }
  .card-number-box {
    font-size: 0.9rem;
    gap: 0.5rem;
  }
}
</style>
