<template>
  <AppLayout>
    <div class="trace-page container py-5">
      <div class="trace-header text-center mb-5">
        <span class="badge badge-primary mb-2">LIVE ORDER TRACKING</span>
        <h1 class="page-title">Lacak Status Pesanan Anda</h1>
        <p class="text-muted">Pantau tahapan memasak dapur hingga pengantaran secara real-time.</p>

        <!-- Search / Change Order Number Input -->
        <div class="search-box mx-auto mt-3">
          <input type="text" v-model="orderSearchInput" placeholder="Masukkan Nomor Order (cth: NR-PO-261012-001)" 
                 class="form-input font-mono text-uppercase" @keyup.enter="handleSearch" />
          <button class="btn-primary" @click="handleSearch">
            <i class="fa-solid fa-radar"></i> Lacak
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-custom"></div>
        <p class="text-muted mt-3">Menghubungkan ke sistem dapur Naumil...</p>
      </div>

      <!-- Not Found State -->
      <div v-else-if="notFound" class="card text-center p-5 max-w-600 mx-auto">
        <i class="fa-solid fa-magnifying-glass-chart fa-3x text-muted mb-3"></i>
        <h4 class="fw-bold">Pesanan Tidak Ditemukan</h4>
        <p class="text-muted">Pastikan nomor pesanan yang Anda masukkan benar, atau cek kembali pesan konfirmasi WhatsApp Anda.</p>
      </div>

      <!-- Active Tracing Screen -->
      <div v-else-if="orderData" class="trace-content max-w-800 mx-auto">
        <!-- Order Info Header Card -->
        <div class="card p-4 mb-4">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
              <span class="badge badge-dark mb-1 font-mono">{{ orderData.order?.store?.name }}</span>
              <h2 class="fw-bold font-mono text-primary mb-0 fs-3">{{ orderData.order?.order_number }}</h2>
              <small class="text-muted">Pemesan: <strong>{{ orderData.order?.client?.name }}</strong> &bull; Dipesan pada {{ formatDate(orderData.order?.created_at) }}</small>
            </div>

            <div class="text-end">
              <span :class="['badge', getStatusBadgeClass(orderData.order?.status), 'fs-6 p-2']">
                <i class="fa-solid fa-circle-dot live-pulse me-1"></i> {{ orderData.order?.status }}
              </span>
              <small class="text-muted d-block mt-1 font-mono">
                Live Sync Realtime
              </small>
            </div>
          </div>
        </div>

        <!-- REJECTED STATE -->
        <div v-if="orderData.is_rejected" class="card alert-reject p-4 mb-4">
          <div class="d-flex align-items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation text-danger fs-1"></i>
            <div>
              <h4 class="fw-bold text-danger mb-1">Pesanan Ditolak / Dibatalkan (Order Reject)</h4>
              <p class="mb-2 text-dark">
                Mohon maaf, pesanan ini tidak dapat dilanjutkan oleh pihak dapur.
              </p>
              <div class="p-3 bg-white rounded border border-danger-subtle">
                <strong>Alasan Penolakan:</strong>
                <div class="text-danger fw-semibold mt-1">
                  {{ orderData.rejection_reason || 'Bahan baku habis atau kuota batch penuh.' }}
                </div>
              </div>
              <a v-if="orderData.order?.store?.phone" 
                 :href="'https://wa.me/' + cleanPhone(orderData.order.store.phone)" target="_blank" class="btn-wa-sm mt-3">
                <i class="fa-brands fa-whatsapp text-success me-1"></i> Hubungi Dapur Toko
              </a>
            </div>
          </div>
        </div>

        <!-- 5-STAGE KDS PIPELINE TIMELINE -->
        <div v-else class="card p-4 mb-4">
          <h4 class="fw-bold fs-6 mb-4">
            <i class="fa-solid fa-timeline text-primary me-2"></i> Tahapan Pemrosesan Dapur
          </h4>

          <div class="pipeline-container">
            <div v-for="(stage, idx) in stagesList" :key="stage.name" 
                 :class="['pipeline-step', getStepStatus(idx)]">
              
              <div class="step-icon-wrapper">
                <div class="step-icon">
                  <i :class="stage.icon"></i>
                </div>
                <div v-if="idx < stagesList.length - 1" class="step-line"></div>
              </div>

              <div class="step-details">
                <div class="d-flex justify-content-between align-items-center">
                  <h5 class="step-title mb-0">{{ stage.name }}</h5>
                  <span v-if="idx === orderData.current_stage_index" class="badge badge-primary">
                    SEDANG BERJALAN
                  </span>
                  <span v-else-if="idx < orderData.current_stage_index" class="badge badge-green">
                    SELESAI
                  </span>
                </div>
                <p class="step-desc">{{ stage.description }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ORDER ITEMS BREAKDOWN -->
        <div class="card p-4 mb-4">
          <h4 class="fw-bold fs-6 mb-3">
            <i class="fa-solid fa-utensils text-warning me-2"></i> Rincian Menu yang Sedang Dimasak
          </h4>

          <div class="items-list">
            <div v-for="item in orderData.order?.items" :key="item.id" class="item-row">
              <div class="d-flex align-items-start gap-3">
                <span class="item-qty-badge font-mono">{{ item.quantity }}x</span>
                <div>
                  <h5 class="fw-bold fs-6 mb-1">{{ item.menu_name }}</h5>
                  <div v-if="item.selected_options?.length" class="d-flex flex-wrap gap-1 mb-1">
                    <span v-for="(opt, oIdx) in item.selected_options" :key="oIdx" class="badge bg-light border text-dark">
                      + {{ opt.name }}
                    </span>
                  </div>
                  <small v-if="item.special_notes" class="text-amber d-block">
                    <i class="fa-solid fa-note-sticky me-1"></i> Catatan: {{ item.special_notes }}
                  </small>
                </div>
              </div>

              <div class="fw-bold text-dark font-mono">
                Rp {{ formatNumber(item.subtotal) }}
              </div>
            </div>
          </div>

          <div class="totals-box mt-3 pt-3 border-top">
            <div class="d-flex justify-content-between mb-1 small text-muted">
              <span>Subtotal:</span>
              <span>Rp {{ formatNumber(orderData.order?.subtotal) }}</span>
            </div>
            <div v-if="orderData.order?.discount_amount > 0" class="d-flex justify-content-between mb-1 small text-danger">
              <span>Diskon Promo:</span>
              <span>- Rp {{ formatNumber(orderData.order?.discount_amount) }}</span>
            </div>
            <div class="d-flex justify-content-between fs-5 fw-bold text-dark pt-2 border-top">
              <span>Total Pembayaran:</span>
              <span class="text-primary font-mono">Rp {{ formatNumber(orderData.order?.total_amount) }}</span>
            </div>
          </div>
        </div>

        <!-- PAYMENT STATUS & ONLINE PAYMENT / QRIS SECTION -->
        <!-- 1. If Paid -->
        <div v-if="orderData.order?.payment_status === 'paid'" class="card p-4 mb-4 border-success bg-success-subtle shadow-sm">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
              <i class="fa-solid fa-check fs-5"></i>
            </div>
            <div>
              <h5 class="fw-bold mb-0 text-success">Pembayaran Lunas (PAID)</h5>
              <small class="text-muted">Metode: {{ orderData.order?.payment_method }} • Pembayaran terkonfirmasi.</small>
            </div>
          </div>
        </div>

        <!-- 2. If Unpaid with Payment Gateway Link -->
        <div v-else-if="orderData.order?.payment_url" class="card p-4 mb-4 border-primary shadow-sm">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h4 class="fw-bold fs-6 mb-0 text-dark">
              <i class="fa-solid fa-credit-card text-success me-2"></i> Pembayaran Online Mayar.id
            </h4>
            <span class="badge bg-warning text-dark">Menunggu Pembayaran</span>
          </div>
          <p class="text-muted small mb-3">Pesanan Anda memiliki invoice online resmi. Klik tombol di bawah untuk bayar dengan QRIS, Virtual Account, atau E-Wallet:</p>
          <a :href="orderData.order.payment_url" target="_blank" class="btn btn-danger py-2 px-4 fw-bold text-white d-flex align-items-center justify-content-center gap-2 text-decoration-none shadow-sm">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
            <span>Bayar Sekarang via Mayar.id (Rp {{ formatNumber(orderData.order?.total_amount) }})</span>
          </a>
          <small class="text-muted d-block text-center mt-2">Status pesanan akan otomatis terverifikasi setelah Anda menyelesaikan pembayaran.</small>
        </div>

        <!-- 3. If QRIS (Statik / Dinamis) -->
        <div v-else-if="orderData.order?.payment_method === 'QRIS Dinamis' || orderData.order?.payment_method === 'QRIS Statik'" class="card p-4 mb-4">
          <h4 class="fw-bold fs-6 mb-3">
            <i class="fa-solid fa-qrcode text-primary me-2"></i> Pembayaran QRIS Toko
          </h4>

          <div class="text-center p-3 bg-light rounded border">
            <p class="text-muted small mb-2">Scan QRIS berikut untuk menyelesaikan pembayaran pesanan Anda:</p>
            <div class="qris-box-wrapper d-inline-block p-2 bg-white rounded border shadow-sm">
              <img :src="orderData.order?.store?.qris_image || 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=NAUMIL_RICE'" 
                   alt="QRIS Toko" class="qris-img" />
            </div>
            <div class="mt-2 font-mono fw-bold text-primary fs-5">
              Rp {{ formatNumber(orderData.order?.total_amount) }}
            </div>
          </div>
        </div>

        <!-- STORE CONTACT & SUPPORT -->
        <div class="card p-4 text-center">
          <h5 class="fw-bold mb-1">Ada Pertanyaan Mengenai Pesanan Anda?</h5>
          <p class="text-muted small mb-3">Tim dapur dan kurir cabang siap membantu Anda.</p>
          <a v-if="orderData.order?.store?.phone" 
             :href="'https://wa.me/' + cleanPhone(orderData.order.store.phone) + '?text=Halo%20Naumil%20Rice,%20saya%20ingin%20menanyakan%20status%20pesanan%20' + orderData.order.order_number" 
             target="_blank" class="btn-primary mx-auto">
            <i class="fa-brands fa-whatsapp fs-5"></i> Hubungi WhatsApp Dapur
          </a>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  orderNumber: {
    type: String,
    default: ''
  }
})

const orderSearchInput = ref(props.orderNumber || '')
const loading = ref(false)
const notFound = ref(false)
const orderData = ref(null)

let pollTimer = null

const stagesList = [
  {
    name: 'Order Konfirmed',
    icon: 'fa-solid fa-check',
    description: 'Pesanan telah diterima oleh sistem dan masuk ke antrean dapur.'
  },
  {
    name: 'Order On Proses',
    icon: 'fa-solid fa-fire-burner',
    description: 'Dapur sedang memasak nasi dan lauk pesanan Anda secara fresh.'
  },
  {
    name: 'Order On Packaging',
    icon: 'fa-solid fa-box-open',
    description: 'Makanan telah matang dan sedang dikemas rapat dalam food packaging.'
  },
  {
    name: 'Order On Delivery',
    icon: 'fa-solid fa-motorcycle',
    description: 'Pesanan sedang dibawa kurir menuju alamat Anda / siap diambil.'
  },
  {
    name: 'Order Ready',
    icon: 'fa-solid fa-circle-check',
    description: 'Pesanan telah sampai dan siap disantap. Selamat menikmati!'
  }
]

const handleSearch = () => {
  const code = orderSearchInput.value.trim()
  if (code) {
    window.history.pushState({}, '', '/trace/' + code)
    loadTrace(code)
  }
}

const loadTrace = async (orderNum) => {
  if (!orderNum || !orderNum.trim()) return
  try {
    loading.value = true
    notFound.value = false
    const res = await axios.get(`/api/v1/orders/trace/${orderNum.trim()}`)
    if (res.data.success) {
      orderData.value = res.data.data
    }
  } catch (err) {
    notFound.value = true
    orderData.value = null
  } finally {
    loading.value = false
  }
}

// Background silent refresh for live real-time sync
const silentRefresh = async () => {
  if (!orderSearchInput.value) return
  try {
    const res = await axios.get(`/api/v1/orders/trace/${orderSearchInput.value.trim()}`)
    if (res.data.success) {
      orderData.value = res.data.data
    }
  } catch (err) {
    // Keep current state on error
  }
}

const getStepStatus = (idx) => {
  if (!orderData.value) return ''
  const current = orderData.value.current_stage_index
  if (idx < current) return 'completed'
  if (idx === current) return 'active'
  return 'pending'
}

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Order Konfirmed': return 'badge-dark'
    case 'Order On Proses': return 'badge-primary'
    case 'Order On Packaging': return 'badge-amber'
    case 'Order On Delivery': return 'badge-primary'
    case 'Order Ready': return 'badge-green'
    case 'Order Reject': return 'badge-red'
    default: return 'badge-dark'
  }
}

const formatNumber = (num) => {
  if (!num) return '0'
  return new Intl.NumberFormat('id-ID').format(num)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }) + ' WIB'
}

const cleanPhone = (phone) => {
  return phone.replace(/[^0-9]/g, '').replace(/^0/, '62')
}

onMounted(() => {
  if (props.orderNumber) {
    loadTrace(props.orderNumber)
  }
  // 3-second live heartbeat polling
  pollTimer = setInterval(silentRefresh, 3000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})
</script>

<style scoped>
.page-title {
  font-size: 2.2rem;
  margin-bottom: 0.5rem;
}

.search-box {
  display: flex;
  gap: 8px;
  max-width: 480px;
}

.max-w-600 { max-width: 600px; }
.max-w-800 { max-width: 800px; }

/* Alert Reject */
.alert-reject {
  background: #fef2f2;
  border: 1.5px solid #fecaca;
}

/* Pipeline Timeline */
.pipeline-container {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.pipeline-step {
  display: flex;
  gap: 1.25rem;
  position: relative;
}

.step-icon-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.step-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  background: #f1f5f9;
  color: #94a3b8;
  border: 2px solid var(--border);
  transition: var(--transition);
  z-index: 2;
}

.step-line {
  width: 2px;
  flex: 1;
  background: var(--border);
  margin-top: 6px;
  margin-bottom: -14px;
}

.pipeline-step.completed .step-icon {
  background: #dcfce7;
  color: #16a34a;
  border-color: #22c55e;
}
.pipeline-step.completed .step-line {
  background: #22c55e;
}

.pipeline-step.active .step-icon {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
  box-shadow: 0 0 0 6px rgba(225, 29, 72, 0.15);
  animation: pulseAnim 1.5s infinite;
}

.step-details {
  flex: 1;
  padding-bottom: 0.5rem;
}

.step-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--dark);
}

.step-desc {
  font-size: 0.86rem;
  color: var(--text-muted);
  margin-top: 4px;
  margin-bottom: 0;
}

/* Items List */
.items-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.item-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #f1f5f9;
}

.item-qty-badge {
  background: #f1f5f9;
  color: var(--primary);
  font-weight: 800;
  font-size: 0.95rem;
  padding: 4px 8px;
  border-radius: var(--radius-sm);
  display: inline-block;
}

.qris-img {
  width: 200px;
  height: 200px;
  object-fit: contain;
}

.live-pulse {
  animation: pulseAnim 1.2s infinite;
}

@keyframes pulseAnim {
  0% { opacity: 1; }
  50% { opacity: 0.4; }
  100% { opacity: 1; }
}
</style>
