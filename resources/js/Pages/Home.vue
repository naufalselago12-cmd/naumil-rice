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
              Pilih Cabang Dapur <span class="highlight-text">Naumil Rice</span> Terdekat Anda.
            </h1>
            <p class="hero-subtitle">
              Setiap cabang Naumil Rice dilengkapi dapur mandiri, menu otentik nusantara, batch Open Pre-Order teratur, dan sistem QRIS dinamis resmi.
            </p>
            <div class="hero-actions">
              <a href="#stores-list" class="btn-primary">
                <i class="fa-solid fa-store"></i> Pilih Cabang Toko
              </a>
              <Link href="/trace" class="btn-secondary">
                <i class="fa-solid fa-radar"></i> Lacak Pesanan Anda
              </Link>
            </div>
          </div>

          <div class="hero-image-wrapper">
            <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&q=80" 
                 alt="Naumil Rice Bowl" class="hero-main-img" />
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
              <button class="btn-primary" @click="goToTrace">
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
            <h2 class="section-title">Daftar Cabang & Toko Naumil Rice</h2>
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
                  <Link :href="'/' + (store.slug || store.code)" class="btn-primary w-100 text-center py-2">
                    <i class="fa-solid fa-utensils me-1"></i> Kunjungi Toko & Pesan
                  </Link>

                  <!-- Secondary Buttons: WA & Store Backend -->
                  <div class="d-flex gap-2">
                    <a v-if="store.phone" :href="'https://wa.me/' + cleanPhone(store.phone)" target="_blank" 
                       class="btn-secondary flex-grow-1 text-center py-2" title="WhatsApp Cabang">
                      <i class="fa-brands fa-whatsapp text-success"></i> WhatsApp
                    </a>
                    
                    <!-- Store Backend Link: base_url/(nama_toko)/backend -->
                    <a :href="'/' + (store.slug || store.code) + '/backend'" target="_blank" 
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

      <!-- Super Admin Quick Portal Banner -->
      <section class="superadmin-banner-section py-4 my-5 bg-dark text-white">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle bg-danger text-white">
              <i class="fa-solid fa-crown fs-4"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-0">Super Admin Master Portal</h5>
              <p class="text-muted small mb-0">Kelola seluruh cabang, daftarkan cabang baru, dan atur penugasan user admin per toko.</p>
            </div>
          </div>
          <div>
            <a href="/superadmin" class="btn btn-danger px-4 py-2 fw-bold">
              <i class="fa-solid fa-shield-halved me-1"></i> Masuk Super Admin (/superadmin)
            </a>
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
</style>
