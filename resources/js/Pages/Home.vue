<template>
  <AppLayout>
    <div class="home-page">
      <!-- Hero Section -->
      <section class="hero-section">
        <div class="container hero-container">
          <div class="hero-text">
            <div class="badge badge-amber mb-3">
              <i class="fa-solid fa-fire text-danger"></i> OPEN PRE-ORDER NUSANTARA
            </div>
            <h1 class="hero-title">
              Nikmati Kelezatan <span class="highlight-text">Rice Bowl Otentik</span> Dibuat Fresh Sesuai Pesanan.
            </h1>
            <p class="hero-subtitle">
              Sistem Pre-Order batch teratur memastikan setiap porsi nasi daun jeruk, cumi hitam Madura, dan sei sapi diolah segar tanpa pengawet. Scan QR atau pesan online langsung sekarang.
            </p>
            <div class="hero-actions">
              <a href="#campaigns-list" class="btn-primary">
                <i class="fa-solid fa-utensils"></i> Lihat Batch Pre-Order
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
              <div class="floating-icon"><i class="fa-solid fa-star"></i></div>
              <div>
                <div class="fw-bold fs-6">100% Halal & Fresh</div>
                <small class="text-muted">Dimasak khusus saat batch siap</small>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Track Quick Lookup Bar -->
      <section class="lookup-section">
        <div class="container">
          <div class="lookup-card">
            <div class="lookup-info">
              <i class="fa-solid fa-magnifying-glass-location text-danger fs-3"></i>
              <div>
                <h4 class="mb-0 fs-6 fw-bold">Sudah Melakukan Pre-Order?</h4>
                <p class="text-muted small mb-0">Masukkan nomor order untuk memantau proses memasak di dapur secara real-time.</p>
              </div>
            </div>
            <div class="lookup-form">
              <input type="text" v-model="trackOrderInput" placeholder="Contoh: NR-PO-261012-001" 
                     class="form-input text-uppercase font-mono" @keyup.enter="goToTrace" />
              <button class="btn-primary" @click="goToTrace">
                <i class="fa-solid fa-arrow-right"></i> Lacak
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Active Open PO Campaigns -->
      <section class="campaigns-section" id="campaigns-list">
        <div class="container">
          <div class="section-head text-center mb-5">
            <span class="badge badge-primary mb-2">PILIH BATCH & CABANG</span>
            <h2 class="section-title">Campaign Pre-Order Yang Sedang Berlangsung</h2>
            <p class="text-muted">Pilih cabang terdekat dan scan QR toko untuk memesan varian menu favorit Anda.</p>
          </div>

          <div v-if="loading" class="text-center py-5">
            <div class="spinner-custom"></div>
            <p class="text-muted mt-3">Memuat campaign Open PO...</p>
          </div>

          <div v-else-if="campaigns.length === 0" class="empty-state text-center py-5">
            <i class="fa-solid fa-calendar-xmark text-muted fa-3x mb-3"></i>
            <h4>Belum Ada Open PO yang Dibuka</h4>
            <p class="text-muted">Silakan cek kembali beberapa saat lagi atau hubungi admin.</p>
          </div>

          <div v-else class="campaigns-grid">
            <div v-for="camp in campaigns" :key="camp.id" class="campaign-card card">
              <div class="campaign-banner">
                <img :src="camp.banner_image || 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80'" 
                     :alt="camp.title" />
                <div class="banner-overlay">
                  <span class="badge badge-dark">
                    <i class="fa-solid fa-store me-1"></i> {{ camp.store?.name }}
                  </span>
                  <span :class="['badge', camp.is_expired ? 'badge-red' : 'badge-green']">
                    {{ camp.is_expired ? 'CLOSE ORDER' : 'OPEN PO AKTIF' }}
                  </span>
                </div>
              </div>

              <div class="campaign-body">
                <h3 class="campaign-title">{{ camp.title }}</h3>
                <p class="campaign-desc">{{ camp.description }}</p>

                <div class="campaign-meta">
                  <div class="meta-item">
                    <i class="fa-solid fa-hourglass-end text-danger"></i>
                    <div>
                      <span class="meta-label">Batas Close Order:</span>
                      <strong class="meta-val">{{ formatDate(camp.close_order_at) }}</strong>
                    </div>
                  </div>
                  <div class="meta-item">
                    <i class="fa-solid fa-calendar-check text-success"></i>
                    <div>
                      <span class="meta-label">Siap / Ready Kirim:</span>
                      <strong class="meta-val">{{ formatDate(camp.ready_at) }}</strong>
                    </div>
                  </div>
                </div>

                <div class="campaign-footer">
                  <Link :href="'/campaign/' + camp.qr_token" class="btn-primary w-100">
                    <i class="fa-solid fa-qrcode"></i> Buka Menu & Pre-Order
                  </Link>
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
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  initialCampaigns: {
    type: Array,
    default: () => []
  }
})

const campaigns = ref(props.initialCampaigns || [])
const loading = ref(campaigns.value.length === 0)
const trackOrderInput = ref('')

const fetchCampaigns = async () => {
  try {
    if (campaigns.value.length === 0) {
      loading.value = true
    }
    const res = await axios.get('/api/v1/campaigns')
    if (res.data.success) {
      campaigns.value = res.data.data
    }
  } catch (err) {
    console.error('Failed to load campaigns', err)
  } finally {
    loading.value = false
  }
}

const goToTrace = () => {
  if (trackOrderInput.value.trim()) {
    router.visit('/trace/' + trackOrderInput.value.trim())
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }) + ' WIB'
}

onMounted(() => {
  fetchCampaigns()
})
</script>

<style scoped>
.hero-section {
  padding: 4.5rem 0 3.5rem;
  background: linear-gradient(180deg, #fff5f5 0%, #f8fafc 100%);
  position: relative;
  overflow: hidden;
}

.hero-container {
  display: grid;
  grid-template-columns: 1.15fr 0.85fr;
  align-items: center;
  gap: 3.5rem;
}

.hero-title {
  font-size: 3rem;
  line-height: 1.15;
  margin-bottom: 1.25rem;
}

.highlight-text {
  color: var(--primary);
  position: relative;
  display: inline-block;
}

.hero-subtitle {
  font-size: 1.1rem;
  color: var(--text-muted);
  line-height: 1.6;
  margin-bottom: 2rem;
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.hero-image-wrapper {
  position: relative;
}

.hero-main-img {
  width: 100%;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  object-fit: cover;
  aspect-ratio: 4/3;
}

.hero-floating-card {
  position: absolute;
  bottom: -20px;
  left: -20px;
  background: white;
  padding: 1rem 1.25rem;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid var(--border);
}

.floating-icon {
  width: 38px;
  height: 38px;
  background: #fef3c7;
  color: #d97706;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Lookup Section */
.lookup-section {
  margin-top: -2rem;
  position: relative;
  z-index: 10;
}

.lookup-card {
  background: white;
  border-radius: var(--radius-md);
  padding: 1.5rem 2rem;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-md);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
}

.lookup-info {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.lookup-form {
  display: flex;
  gap: 8px;
  min-width: 380px;
}

/* Campaigns Section */
.campaigns-section {
  padding: 5rem 0;
}

.section-title {
  font-size: 2.2rem;
  margin-bottom: 0.5rem;
}

.campaigns-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 2rem;
}

.campaign-card {
  display: flex;
  flex-direction: column;
}

.campaign-banner {
  position: relative;
  height: 220px;
  overflow: hidden;
}

.campaign-banner img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.campaign-card:hover .campaign-banner img {
  transform: scale(1.05);
}

.banner-overlay {
  position: absolute;
  top: 14px;
  left: 14px;
  right: 14px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.campaign-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.campaign-title {
  font-size: 1.25rem;
  margin-bottom: 0.5rem;
}

.campaign-desc {
  font-size: 0.9rem;
  color: var(--text-muted);
  line-height: 1.5;
  margin-bottom: 1.25rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.campaign-meta {
  background: #f8fafc;
  padding: 0.85rem;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  margin-bottom: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.84rem;
}

.meta-label {
  display: block;
  color: var(--text-muted);
  font-size: 0.75rem;
}

.meta-val {
  color: var(--dark);
}

@media (max-width: 900px) {
  .hero-container {
    grid-template-columns: 1fr;
    gap: 2.5rem;
  }
  .hero-title {
    font-size: 2.2rem;
  }
  .lookup-card {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  .lookup-form {
    min-width: unset;
  }
}
</style>
