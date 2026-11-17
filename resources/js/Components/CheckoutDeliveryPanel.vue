<template>
  <div v-if="store?.delivery_enabled" class="cdp-panel">
    <!-- Pilihan Metode Penerimaan Pesanan -->
    <div class="cdp-section">
      <div class="cdp-section-title">
        <i class="fa-solid fa-truck-fast text-primary"></i>
        <span>Metode Pengambilan / Pengiriman Pesanan</span>
        <span class="text-danger">*</span>
      </div>

      <div class="cdp-choice-grid">
        <label
          class="cdp-choice-card"
          :class="{ 'is-active-pickup': modelValue.fulfillment_type === 'pickup' }"
        >
          <input
            type="radio"
            class="cdp-radio"
            value="pickup"
            :checked="modelValue.fulfillment_type === 'pickup'"
            @change="setFulfillmentType('pickup')"
          />
          <div class="cdp-choice-body">
            <div class="cdp-choice-title">
              <i class="fa-solid fa-store text-primary"></i>
              <span>COD (Ambil di Outlet)</span>
            </div>
            <small class="cdp-choice-sub">Ambil langsung di cabang · Ongkir Rp 0</small>
          </div>
        </label>

        <label
          class="cdp-choice-card"
          :class="{ 'is-active-delivery': modelValue.fulfillment_type === 'delivery' }"
        >
          <input
            type="radio"
            class="cdp-radio"
            value="delivery"
            :checked="modelValue.fulfillment_type === 'delivery'"
            @change="setFulfillmentType('delivery')"
          />
          <div class="cdp-choice-body">
            <div class="cdp-choice-title">
              <i class="fa-solid fa-motorcycle text-success"></i>
              <span>Kirim Langsung ke Rumah</span>
            </div>
            <small class="cdp-choice-sub">Kurir KiriminAja (Express / Instant)</small>
          </div>
        </label>
      </div>
    </div>

    <!-- Form Alamat + Kurir Saat Memilih Kirim Langsung -->
    <div v-if="modelValue.fulfillment_type === 'delivery'" class="cdp-delivery-box">
      <!-- PELANGGAN SETIA: Pilihan Buku Alamat (Simpan 1 Kali, Tambah Alamat Baru Kapan Saja) -->
      <div v-if="isLoyalty && savedAddresses.length > 0 && !addingNewAddress" class="cdp-address-block">
        <div class="cdp-header-row">
          <span class="cdp-block-title">
            <i class="fa-solid fa-address-book text-primary"></i> Pilih Alamat Tersimpan Anda
          </span>
          <button
            type="button"
            class="cdp-btn-outline"
            @click="enableNewAddressMode"
          >
            <i class="fa-solid fa-plus"></i> Tambah Alamat Baru
          </button>
        </div>

        <div class="cdp-saved-list">
          <label
            v-for="addr in savedAddresses"
            :key="addr.id"
            class="cdp-addr-card"
            :class="{ 'is-selected': Number(modelValue.client_address_id) === Number(addr.id) }"
          >
            <input
              type="radio"
              class="cdp-radio"
              :checked="Number(modelValue.client_address_id) === Number(addr.id)"
              @change="selectSavedAddress(addr)"
            />
            <div class="cdp-addr-body">
              <div class="cdp-addr-head">
                <strong>{{ addr.label }}</strong>
                <span v-if="addr.is_default" class="cdp-badge-default">Utama</span>
              </div>
              <div class="cdp-addr-text">{{ addr.full_address }}</div>
              <div v-if="addr.address_note" class="cdp-addr-note">
                <i class="fa-solid fa-signs-post"></i> Patokan/Gang: {{ addr.address_note }}
              </div>
            </div>
          </label>
        </div>
      </div>

      <!-- ALAMAT BARU (Tamu Non-Loyalty ATAU Loyalty Klik Tambah Alamat Baru / Belum Punya Alamat) -->
      <div v-else class="cdp-address-block">
        <div class="cdp-header-row">
          <span class="cdp-block-title">
            <i class="fa-solid fa-map-location-dot text-danger"></i>
            {{ isLoyalty ? 'Alamat Pengiriman Baru' : 'Alamat Pengiriman Tujuan' }}
          </span>
          <button
            v-if="isLoyalty && savedAddresses.length > 0"
            type="button"
            class="cdp-btn-ghost"
            @click="cancelNewAddressMode"
          >
            <i class="fa-solid fa-arrow-left"></i> Pilih Alamat Tersimpan
          </button>
        </div>

        <div v-if="isLoyalty" class="cdp-loyalty-meta">
          <div class="cdp-field">
            <label for="cdp-addr-label" class="cdp-field-label">
              Label Alamat (mis: Rumah / Kantor)
            </label>
            <input
              id="cdp-addr-label"
              :value="modelValue.shipping.address_label"
              type="text"
              maxlength="50"
              class="cdp-input"
              placeholder="Rumah / Kantor / Kos"
              @input="updateShippingField('address_label', $event.target.value)"
            />
          </div>
          <label class="cdp-save-toggle">
            <input
              type="checkbox"
              class="cdp-checkbox"
              :checked="modelValue.shipping.save_address"
              @change="updateShippingField('save_address', $event.target.checked)"
            />
            <span class="cdp-save-text">
              Simpan alamat ini (cukup simpan 1 kali untuk pesanan berikutnya)
            </span>
          </label>
        </div>

        <KiriminAjaRegionForm
          :model-value="modelValue.shipping"
          :default-lat="store.ka_latitude || -6.2088"
          :default-lng="store.ka_longitude || 106.8456"
          map-height="220px"
          @update:model-value="onRegionFormUpdate"
        />
      </div>

      <!-- PILIHAN KURIR & ONGKOS KIRIM KIRIMINAJA -->
      <div class="cdp-courier-section">
        <div class="cdp-header-row">
          <span class="cdp-block-title">
            <i class="fa-solid fa-box text-primary"></i> Pilihan Kurir &amp; Biaya Pengiriman
          </span>
          <div class="cdp-quote-actions">
            <span v-if="quoting && courierOptions.length > 0" class="cdp-refresh-pill">
              <i class="fa-solid fa-spinner fa-spin"></i> Memperbarui tarif...
            </span>
            <button
              type="button"
              class="cdp-btn-outline"
              :disabled="quoting || !activeDistrictId"
              @click="fetchQuote(true)"
            >
              <i class="fa-solid fa-rotate" :class="{ 'fa-spin': quoting }"></i> Cek Ongkir
            </button>
          </div>
        </div>

        <div v-if="!activeDistrictId" class="cdp-alert cdp-alert-muted">
          <i class="fa-solid fa-location-crosshairs"></i>
          <span>Lengkapi kecamatan &amp; alamat tujuan untuk menampilkan daftar tarif pengiriman KiriminAja.</span>
        </div>

        <div v-else-if="quoting && courierOptions.length === 0" class="cdp-loading-box">
          <i class="fa-solid fa-spinner fa-spin text-primary"></i>
          <span>Menghitung tarif pengiriman KiriminAja...</span>
        </div>

        <div v-else-if="quoteError && courierOptions.length === 0" class="cdp-alert cdp-alert-danger">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span>{{ quoteError }}</span>
        </div>

        <div v-else-if="courierOptions.length === 0" class="cdp-alert cdp-alert-warning">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span>Layanan kurir belum tersedia untuk tujuan ini. Pastikan kelurahan/koordinat peta sudah benar lalu klik Cek Ongkir.</span>
        </div>

        <div v-else>
          <div v-if="quoteError" class="cdp-alert cdp-alert-danger cdp-mb-2">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>{{ quoteError }}</span>
          </div>

          <div class="cdp-courier-list" :class="{ 'is-refreshing': quoting }">
            <label
              v-for="opt in courierOptions"
              :key="opt.key"
              class="cdp-courier-card"
              :class="{ 'is-selected': isSelectedOption(opt) }"
            >
              <div class="cdp-courier-left">
                <input
                  type="radio"
                  class="cdp-radio"
                  :checked="isSelectedOption(opt)"
                  @change="selectCourierOption(opt)"
                />
                <div class="cdp-courier-info">
                  <div class="cdp-courier-name">
                    <span
                      class="cdp-type-badge"
                      :class="opt.type === 'instant' ? 'is-instant' : 'is-express'"
                    >
                      {{ opt.type === 'instant' ? 'INSTANT' : 'EXPRESS' }}
                    </span>
                    <span>{{ opt.courier_name }} - {{ opt.service_name }}</span>
                  </div>
                  <small class="cdp-courier-etd">
                    Estimasi: {{ opt.etd || 'Sesuai kurir' }}
                  </small>
                </div>
              </div>
              <div class="cdp-courier-price font-mono">
                Rp {{ formatRupiah(opt.cost) }}
              </div>
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import KiriminAjaRegionForm from './KiriminAjaRegionForm.vue'

const props = defineProps({
  store: { type: Object, required: true },
  isLoyalty: { type: Boolean, default: false },
  savedAddresses: { type: Array, default: () => [] },
  totalQty: { type: Number, default: 1 },
  subtotalAfterDiscount: { type: Number, default: 0 },
  modelValue: { type: Object, required: true },
})

const emit = defineEmits(['update:modelValue', 'fulfillment-changed'])

const addingNewAddress = ref(false)
const quoting = ref(false)
const quoteError = ref('')
const courierOptions = ref([])
const lastQuotedSignature = ref('')

let quoteTimer = null
let activeAbortController = null

const formatRupiah = (n) => new Intl.NumberFormat('id-ID').format(Math.round(n || 0))

const selectedSavedAddress = computed(() => {
  if (!props.isLoyalty || addingNewAddress.value || !props.modelValue.client_address_id) {
    return null
  }
  return props.savedAddresses.find((a) => Number(a.id) === Number(props.modelValue.client_address_id)) || null
})

const activeDestination = computed(() => {
  if (selectedSavedAddress.value) {
    const a = selectedSavedAddress.value
    return {
      district_id: a.district_id,
      subdistrict_id: a.subdistrict_id,
      latitude: a.latitude,
      longitude: a.longitude,
      address: a.full_address || a.address,
    }
  }
  const s = props.modelValue.shipping || {}
  return {
    district_id: s.district_id,
    subdistrict_id: s.subdistrict_id,
    latitude: s.latitude,
    longitude: s.longitude,
    address: s.address || s.geo_address || '',
  }
})

const activeDistrictId = computed(() => activeDestination.value.district_id || null)

// Kunci primitif stabil agar update field non-rute (seperti courier/cost/catatan) tidak pernah memicu loop API
const quoteRequestSignature = computed(() => {
  if (props.modelValue.fulfillment_type !== 'delivery') return ''
  const dest = activeDestination.value
  if (!dest.district_id || !props.store?.id) return ''
  const lat = dest.latitude !== null && dest.latitude !== undefined && dest.latitude !== ''
    ? Number(dest.latitude).toFixed(6)
    : ''
  const lng = dest.longitude !== null && dest.longitude !== undefined && dest.longitude !== ''
    ? Number(dest.longitude).toFixed(6)
    : ''
  return [
    props.store.id,
    props.modelValue.client_address_id || 'custom',
    dest.district_id,
    dest.subdistrict_id || '',
    lat,
    lng,
    Math.max(1, Number(props.totalQty || 1)),
    Math.max(0, Math.round(Number(props.subtotalAfterDiscount || 0))),
  ].join('|')
})

const emitModel = (next) => {
  emit('update:modelValue', next)
}

const setFulfillmentType = (type) => {
  const next = {
    ...props.modelValue,
    fulfillment_type: type,
  }
  if (type === 'pickup') {
    if (quoteTimer) clearTimeout(quoteTimer)
    activeAbortController?.abort()
    lastQuotedSignature.value = ''
    next.client_address_id = null
    next.shipping = {
      ...next.shipping,
      type: '',
      courier: '',
      service_type: '',
      service_name: '',
      cost: 0,
      etd: '',
    }
    courierOptions.value = []
    quoteError.value = ''
    quoting.value = false
  } else if (type === 'delivery' && props.isLoyalty && props.savedAddresses.length > 0 && !addingNewAddress.value) {
    const def = props.savedAddresses.find((a) => a.is_default) || props.savedAddresses[0]
    if (def) {
      next.client_address_id = def.id
    }
  }
  emitModel(next)
  emit('fulfillment-changed', type)
}

const selectSavedAddress = (addr) => {
  addingNewAddress.value = false
  emitModel({
    ...props.modelValue,
    client_address_id: addr.id,
    shipping: {
      ...props.modelValue.shipping,
      type: '',
      courier: '',
      service_type: '',
      service_name: '',
      cost: 0,
      etd: '',
    },
  })
}

const enableNewAddressMode = () => {
  addingNewAddress.value = true
  courierOptions.value = []
  lastQuotedSignature.value = ''
  quoteError.value = ''
  emitModel({
    ...props.modelValue,
    client_address_id: null,
    shipping: {
      ...props.modelValue.shipping,
      save_address: true,
      address_label: props.modelValue.shipping.address_label || 'Alamat Lain',
      type: '',
      courier: '',
      service_type: '',
      service_name: '',
      cost: 0,
      etd: '',
    },
  })
}

const cancelNewAddressMode = () => {
  addingNewAddress.value = false
  const def = props.savedAddresses.find((a) => a.is_default) || props.savedAddresses[0]
  if (def) {
    selectSavedAddress(def)
  }
}

const updateShippingField = (key, val) => {
  if (props.modelValue.shipping?.[key] === val) return
  emitModel({
    ...props.modelValue,
    shipping: {
      ...props.modelValue.shipping,
      [key]: val,
    },
  })
}

const onRegionFormUpdate = (regionPatch) => {
  const prevDist = props.modelValue.shipping?.district_id
  const prevSub = props.modelValue.shipping?.subdistrict_id
  const next = {
    ...props.modelValue,
    shipping: {
      ...props.modelValue.shipping,
      ...regionPatch,
    },
  }
  if (regionPatch.district_id !== prevDist || regionPatch.subdistrict_id !== prevSub) {
    lastQuotedSignature.value = ''
    next.shipping.type = ''
    next.shipping.courier = ''
    next.shipping.service_type = ''
    next.shipping.service_name = ''
    next.shipping.cost = 0
    next.shipping.etd = ''
  }
  emitModel(next)
}

const isOptionSameAsModel = (opt) => {
  const s = props.modelValue.shipping || {}
  return (
    s.type === opt.type &&
    s.courier === opt.courier &&
    s.service_type === opt.service_type &&
    s.service_name === opt.service_name &&
    Number(s.cost || 0) === Number(opt.cost || 0) &&
    (s.etd || '') === (opt.etd || '')
  )
}

const selectCourierOption = (opt) => {
  if (isOptionSameAsModel(opt)) return
  emitModel({
    ...props.modelValue,
    shipping: {
      ...props.modelValue.shipping,
      type: opt.type,
      courier: opt.courier,
      service_type: opt.service_type,
      service_name: opt.service_name,
      cost: Number(opt.cost || 0),
      etd: opt.etd || '',
    },
  })
}

const isSelectedOption = (opt) => {
  const s = props.modelValue.shipping || {}
  return s.type === opt.type && s.courier === opt.courier && s.service_type === opt.service_type
}

const fetchQuote = async (force = false) => {
  const sig = quoteRequestSignature.value
  const dest = activeDestination.value
  if (!sig || !dest.district_id) {
    courierOptions.value = []
    lastQuotedSignature.value = ''
    return
  }

  if (!force && sig === lastQuotedSignature.value && courierOptions.value.length > 0) {
    return
  }

  if (activeAbortController) {
    activeAbortController.abort()
  }
  const controller = new AbortController()
  activeAbortController = controller

  quoting.value = true
  quoteError.value = ''
  try {
    const res = await axios.post(
      '/api/v1/shipping/quote',
      {
        store_id: props.store.id,
        district_id: dest.district_id,
        subdistrict_id: dest.subdistrict_id || null,
        latitude: dest.latitude ?? null,
        longitude: dest.longitude ?? null,
        address: dest.address || null,
        total_qty: Math.max(1, Number(props.totalQty || 1)),
        item_value: Math.max(10000, Number(props.subtotalAfterDiscount || 10000)),
      },
      { signal: controller.signal }
    )

    if (controller.signal.aborted) return

    const opts = res.data?.data?.options || []
    courierOptions.value = opts
    lastQuotedSignature.value = sig

    if (opts.length > 0) {
      const current = opts.find((o) => isSelectedOption(o))
      selectCourierOption(current || opts[0])
    } else if (Number(props.modelValue.shipping?.cost || 0) !== 0) {
      updateShippingField('cost', 0)
    }
  } catch (err) {
    if (axios.isCancel?.(err) || err?.code === 'ERR_CANCELED' || err?.name === 'AbortError') {
      return
    }
    courierOptions.value = []
    lastQuotedSignature.value = ''
    quoteError.value = err.response?.data?.message || 'Gagal menghitung biaya pengiriman KiriminAja.'
    if (Number(props.modelValue.shipping?.cost || 0) !== 0) {
      updateShippingField('cost', 0)
    }
  } finally {
    if (activeAbortController === controller) {
      activeAbortController = null
      quoting.value = false
    }
  }
}

const scheduleQuote = () => {
  if (quoteTimer) clearTimeout(quoteTimer)
  quoteTimer = setTimeout(() => {
    fetchQuote(false)
  }, 380)
}

watch(quoteRequestSignature, (newSig, oldSig) => {
  if (newSig === oldSig) return
  if (quoteTimer) clearTimeout(quoteTimer)

  if (!newSig) {
    lastQuotedSignature.value = ''
    if (props.modelValue.fulfillment_type === 'delivery' && !activeDestination.value.district_id) {
      courierOptions.value = []
    }
    return
  }

  scheduleQuote()
})

watch(
  () => [props.isLoyalty, props.savedAddresses.length],
  ([loyal]) => {
    if (loyal && props.savedAddresses.length > 0 && !addingNewAddress.value && !props.modelValue.client_address_id && props.modelValue.fulfillment_type === 'delivery') {
      const def = props.savedAddresses.find((a) => a.is_default) || props.savedAddresses[0]
      if (def) selectSavedAddress(def)
    }
  }
)

onMounted(() => {
  if (quoteRequestSignature.value) {
    scheduleQuote()
  }
})

onBeforeUnmount(() => {
  if (quoteTimer) clearTimeout(quoteTimer)
  activeAbortController?.abort()
})
</script>

<style scoped>
.cdp-panel {
  margin-bottom: 1.25rem;
}

.cdp-section {
  margin-bottom: 0.9rem;
}

.cdp-section-title {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.88rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 0.55rem;
}

.cdp-choice-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.65rem;
}

@media (max-width: 520px) {
  .cdp-choice-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}

.cdp-choice-card {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  padding: 0.85rem;
  border-radius: 12px;
  border: 1.5px solid #e2e8f0;
  background: #ffffff;
  cursor: pointer;
  transition: border-color 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
}

.cdp-choice-card:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.cdp-choice-card.is-active-pickup {
  border-color: #e11d48;
  background: #fff1f2;
  box-shadow: 0 2px 10px rgba(225, 29, 72, 0.12);
}

.cdp-choice-card.is-active-delivery {
  border-color: #10b981;
  background: #f0fdf4;
  box-shadow: 0 2px 10px rgba(16, 185, 129, 0.14);
}

.cdp-radio,
.cdp-checkbox {
  width: 17px;
  height: 17px;
  margin-top: 2px;
  flex-shrink: 0;
  accent-color: #e11d48;
  cursor: pointer;
}

.cdp-choice-body {
  min-inline-size: 0;
}

.cdp-choice-title {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.86rem;
  font-weight: 700;
  color: #0f172a;
}

.cdp-choice-sub {
  display: block;
  font-size: 0.74rem;
  color: #64748b;
  margin-top: 0.15rem;
}

.cdp-delivery-box {
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 14px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.cdp-address-block {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.cdp-header-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.cdp-block-title {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.86rem;
  font-weight: 700;
  color: #0f172a;
}

.cdp-quote-actions {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.cdp-refresh-pill {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.cdp-btn-outline {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.7rem;
  border-radius: 8px;
  border: 1.5px solid #e11d48;
  background: #ffffff;
  color: #e11d48;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  transition: background-color 0.15s ease, color 0.15s ease;
}

.cdp-btn-outline:hover:not(:disabled) {
  background: #e11d48;
  color: #ffffff;
}

.cdp-btn-outline:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.cdp-btn-ghost {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.7rem;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #475569;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
}

.cdp-btn-ghost:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.cdp-saved-list {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}

.cdp-addr-card {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.75rem 0.85rem;
  border-radius: 12px;
  border: 1.5px solid #e2e8f0;
  background: #ffffff;
  cursor: pointer;
  transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.cdp-addr-card:hover {
  border-color: #cbd5e1;
}

.cdp-addr-card.is-selected {
  border-color: #10b981;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.14);
}

.cdp-addr-body {
  flex: 1;
  min-inline-size: 0;
  font-size: 0.84rem;
}

.cdp-addr-head {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.45rem;
  color: #0f172a;
}

.cdp-badge-default {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.12rem 0.5rem;
  border-radius: 9999px;
  background: #dcfce7;
  color: #15803d;
  border: 1px solid #bbf7d0;
}

.cdp-addr-text {
  color: #334155;
  margin-top: 0.2rem;
  line-height: 1.4;
}

.cdp-addr-note {
  color: #be123c;
  font-size: 0.78rem;
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.cdp-loyalty-meta {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr);
  align-items: end;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}

@media (max-width: 600px) {
  .cdp-loyalty-meta {
    grid-template-columns: minmax(0, 1fr);
  }
}

.cdp-field {
  min-inline-size: 0;
}

.cdp-field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 700;
  color: #334155;
  margin-bottom: 0.3rem;
}

.cdp-input {
  width: 100%;
  box-sizing: border-box;
  min-height: 40px;
  padding: 0.45rem 0.75rem;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.88rem;
  color: #0f172a;
  background: #ffffff;
  outline: none;
}

.cdp-input:focus {
  border-color: #e11d48;
  box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.12);
}

.cdp-save-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-height: 40px;
  cursor: pointer;
}

.cdp-save-text {
  font-size: 0.8rem;
  font-weight: 600;
  color: #15803d;
  line-height: 1.35;
}

.cdp-courier-section {
  padding-top: 0.9rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.cdp-alert {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 0.7rem 0.85rem;
  border-radius: 10px;
  font-size: 0.82rem;
  line-height: 1.45;
}

.cdp-alert-muted {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #475569;
}

.cdp-alert-danger {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #b91c1c;
}

.cdp-alert-warning {
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #92400e;
}

.cdp-mb-2 {
  margin-bottom: 0.5rem;
}

.cdp-loading-box {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.84rem;
  color: #64748b;
}

.cdp-courier-list {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
  transition: opacity 0.2s ease;
}

.cdp-courier-list.is-refreshing {
  opacity: 0.72;
}

.cdp-courier-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 0.9rem;
  border-radius: 12px;
  border: 1.5px solid #e2e8f0;
  background: #ffffff;
  cursor: pointer;
  transition: border-color 0.16s ease, background-color 0.16s ease, box-shadow 0.16s ease;
}

.cdp-courier-card:hover {
  border-color: #cbd5e1;
}

.cdp-courier-card.is-selected {
  border-color: #e11d48;
  background: #fff1f2;
  box-shadow: 0 2px 8px rgba(225, 29, 72, 0.12);
}

.cdp-courier-left {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  min-inline-size: 0;
}

.cdp-courier-info {
  min-inline-size: 0;
}

.cdp-courier-name {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.4rem;
  font-size: 0.85rem;
  font-weight: 700;
  color: #0f172a;
}

.cdp-type-badge {
  display: inline-block;
  padding: 0.15rem 0.45rem;
  border-radius: 6px;
  font-size: 0.65rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: 0.03em;
}

.cdp-type-badge.is-instant {
  background: #10b981;
}

.cdp-type-badge.is-express {
  background: #e11d48;
}

.cdp-courier-etd {
  display: block;
  font-size: 0.74rem;
  color: #64748b;
  margin-top: 0.15rem;
}

.cdp-courier-price {
  font-weight: 800;
  font-size: 0.9rem;
  color: #15803d;
  white-space: nowrap;
}
</style>

