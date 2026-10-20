<template>
  <AppLayout>
    <div class="stores-page container py-5">
      <div class="text-center mb-5">
        <span class="badge badge-primary mb-2">JARINGAN DAPUR KAMI</span>
        <h1 class="page-title">Cabang & Lokasi Dapur Naumil Store</h1>
        <p class="text-muted">Setiap cabang dilengkapi dapur mandiri dan QRIS merchant resmi untuk melayani pesanan Anda.</p>
      </div>

      <div v-if="loading" class="text-center py-5">
        <div class="spinner-custom"></div>
        <p class="text-muted mt-3">Memuat data cabang...</p>
      </div>

      <div v-else class="stores-grid">
        <div v-for="store in stores" :key="store.id" class="card store-card">
          <div class="store-card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
              <img :src="store.logo || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=100&q=80'" 
                   :alt="store.name" class="store-img" />
              <div>
                <span class="badge badge-dark mb-1 font-mono">{{ store.code }}</span>
                <h3 class="store-name mb-0">{{ store.name }}</h3>
                <span class="badge badge-green mt-1">Dapur Buka (Open)</span>
              </div>
            </div>

            <div class="store-details mb-4">
              <p class="text-muted small mb-2">
                <i class="fa-solid fa-location-dot text-danger me-2"></i> {{ store.address }}
              </p>
              <p v-if="store.phone" class="text-muted small mb-0">
                <i class="fa-solid fa-phone text-success me-2"></i> {{ store.phone }}
              </p>
            </div>

            <!-- Dynamic QRIS Status -->
            <div class="qris-status-box p-3 rounded mb-3 bg-light border">
              <div class="d-flex justify-content-between align-items-center">
                <span class="small fw-bold">
                  <i class="fa-solid fa-qrcode text-primary me-1"></i> QRIS Dinamis Per Toko
                </span>
                <span v-if="store.qris_image" class="badge badge-primary">Tersedia</span>
                <span v-else class="badge badge-amber">Offline</span>
              </div>
            </div>

            <div class="d-flex gap-2">
              <a v-if="store.phone" :href="'https://wa.me/' + cleanPhone(store.phone)" target="_blank" class="btn-secondary flex-grow-1">
                <i class="fa-brands fa-whatsapp text-success"></i> WhatsApp
              </a>
              <Link href="/" class="btn btn-primary flex-grow-1">
                <i class="fa-solid fa-bullhorn"></i> Lihat PO
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  initialStores: {
    type: Array,
    default: () => []
  }
})

const stores = ref(props.initialStores || [])
const loading = ref(stores.value.length === 0)

const fetchStores = async () => {
  try {
    if (stores.value.length === 0) {
      loading.value = true
    }
    const res = await axios.get('/api/v1/stores')
    if (res.data.success) {
      stores.value = res.data.data
    }
  } catch (err) {
    console.error('Failed to load stores', err)
  } finally {
    loading.value = false
  }
}

const cleanPhone = (phone) => {
  return phone.replace(/[^0-9]/g, '').replace(/^0/, '62')
}

onMounted(() => {
  fetchStores()
})
</script>

<style scoped>
.page-title {
  font-size: 2.2rem;
  margin-bottom: 0.5rem;
}

.stores-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 2rem;
}

.store-img {
  width: 64px;
  height: 64px;
  border-radius: var(--radius-md);
  object-fit: cover;
  border: 1px solid var(--border);
}

.store-name {
  font-size: 1.2rem;
  font-weight: 800;
}
</style>
