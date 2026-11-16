<template>
  <div v-if="store?.delivery_enabled" class="checkout-delivery-panel mb-3">
    <!-- Pilihan Metode Penerimaan Pesanan -->
    <div class="mb-3">
      <label class="form-label fw-bold small text-dark d-block mb-2">
        <i class="fa-solid fa-truck-fast text-primary me-1"></i>
        Metode Pengambilan / Pengiriman Pesanan <span class="text-danger">*</span>
      </label>

      <div class="row g-2">
        <div class="col-6">
          <label
            class="fulfillment-choice-card p-3 rounded-3 border d-block h-100 cursor-pointer"
            :class="modelValue.fulfillment_type === 'pickup' ? 'border-primary bg-primary-subtle shadow-sm' : 'bg-white'"
          >
            <div class="d-flex align-items-start gap-2">
              <input
                type="radio"
                class="form-check-input mt-1"
                value="pickup"
                :checked="modelValue.fulfillment_type === 'pickup'"
                @change="setFulfillmentType('pickup')"
              />
              <div>
                <div class="fw-bold small text-dark">
                  <i class="fa-solid fa-store text-primary me-1"></i> COD (Ambil di Outlet)
                </div>
                <small class="text-muted d-block" style="font-size: 0.74rem;">
                  Ambil langsung di cabang · Ongkir Rp 0
                </small>
              </div>
            </div>
          </label>
        </div>

        <div class="col-6">
          <label
            class="fulfillment-choice-card p-3 rounded-3 border d-block h-100 cursor-pointer"
            :class="modelValue.fulfillment_type === 'delivery' ? 'border-success bg-success-subtle shadow-sm' : 'bg-white'"
          >
            <div class="d-flex align-items-start gap-2">
              <input
                type="radio"
                class="form-check-input mt-1"
                value="delivery"
                :checked="modelValue.fulfillment_type === 'delivery'"
                @change="setFulfillmentType('delivery')"
              />
              <div>
                <div class="fw-bold small text-dark">
                  <i class="fa-solid fa-motorcycle text-success me-1"></i> Kirim Langsung ke Rumah
                </div>
                <small class="text-muted d-block" style="font-size: 0.74rem;">
                  Kurir KiriminAja (Express / Instant)
                </small>
              </div>
            </div>
          </label>
        </div>
      </div>
    </div>

    <!-- Form Alamat + Kurir Saat Memilih Kirim Langsung -->
    <div v-if="modelValue.fulfillment_type === 'delivery'" class="p-3 border rounded-3 bg-light">
      <!-- PELANGGAN SETIA: Pilihan Buku Alamat (Simpan 1 Kali, Tambah Alamat Baru Kapan Saja) -->
      <div v-if="isLoyalty && savedAddresses.length > 0 && !addingNewAddress" class="mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
          <label class="form-label fw-bold small text-dark mb-0">
            <i class="fa-solid fa-address-book text-primary me-1"></i> Pilih Alamat Tersimpan Anda
          </label>
          <button
            type="button"
            class="btn btn-sm btn-outline-primary fw-semibold"
            @click="enableNewAddressMode"
          >
            <i class="fa-solid fa-plus me-1"></i> Tambah Alamat Baru
          </button>
        </div>

        <div class="d-flex flex-column gap-2">
          <label
            v-for="addr in savedAddresses"
            :key="addr.id"
            class="p-2 px-3 rounded-3 border bg-white d-flex align-items-start gap-2 cursor-pointer"
            :class="Number(modelValue.client_address_id) === Number(addr.id) ? 'border-success shadow-sm' : ''"
          >
            <input
              type="radio"
              class="form-check-input mt-1"
              :checked="Number(modelValue.client_address_id) === Number(addr.id)"
              @change="selectSavedAddress(addr)"
            />
            <div class="flex-grow-1 small">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <strong class="text-dark">{{ addr.label }}</strong>
                <span v-if="addr.is_default" class="badge bg-success-subtle text-success border border-success-subtle">Utama</span>
              </div>
              <div class="text-dark mt-1">{{ addr.full_address }}</div>
              <div v-if="addr.address_note" class="text-primary mt-1">
                <i class="fa-solid fa-signs-post me-1"></i> Patokan/Gang: {{ addr.address_note }}
              </div>
            </div>
          </label>
        </div>
      </div>

      <!-- ALAMAT BARU (Tamu Non-Loyalty ATAU Loyalty Klik Tambah Alamat Baru / Belum Punya Alamat) -->
      <div v-else class="mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
          <label class="form-label fw-bold small text-dark mb-0">
            <i class="fa-solid fa-map-location-dot text-danger me-1"></i>
            {{ isLoyalty ? 'Alamat Pengiriman Baru' : 'Alamat Pengiriman Tujuan' }}
          </label>
          <button
            v-if="isLoyalty && savedAddresses.length > 0"
            type="button"
            class="btn btn-sm btn-outline-secondary"
            @click="cancelNewAddressMode"
          >
            <i class="fa-solid fa-arrow-left me-1"></i> Pilih Alamat Tersimpan
          </button>
        </div>

        <div v-if="isLoyalty" class="row g-2 mb-2 align-items-end">
          <div class="col-md-5">
            <label class="form-label small fw-bold mb-1">Label Alamat (mis: Rumah / Kantor)</label>
            <input
              :value="modelValue.shipping.address_label"
              type="text"
              maxlength="50"
              class="form-control form-control-sm"
              placeholder="Rumah / Kantor / Kos"
              @input="updateShippingField('address_label', $event.target.value)"
            />
          </div>
          <div class="col-md-7">
            <label class="d-flex align-items-center gap-2 small cursor-pointer mb-1">
              <input
                type="checkbox"
                class="form-check-input mt-0"
                :checked="modelValue.shipping.save_address"
                @change="updateShippingField('save_address', $event.target.checked)"
              />
              <span class="fw-semibold text-success">
                Simpan alamat ini (cukup simpan 1 kali untuk pesanan berikutnya)
              </span>
            </label>
          </div>
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
      <div class="pt-3 border-top">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
          <label class="form-label fw-bold small text-dark mb-0">
            <i class="fa-solid fa-box text-primary me-1"></i> Pilihan Kurir &amp; Biaya Pengiriman
          </label>
          <button
            type="button"
            class="btn btn-sm btn-outline-primary py-0 px-2"
            :disabled="quoting || !activeDistrictId"
            @click="fetchQuote"
          >
            <i class="fa-solid fa-rotate me-1" :class="{ 'fa-spin': quoting }"></i> Cek Ongkir
          </button>
        </div>

        <div v-if="!activeDistrictId" class="alert alert-secondary small py-2 mb-0">
          Lengkapi kecamatan &amp; alamat tujuan untuk menampilkan daftar tarif pengiriman KiriminAja.
        </div>

        <div v-else-if="quoting" class="p-3 text-center small text-muted bg-white border rounded-3">
          <i class="fa-solid fa-spinner fa-spin text-primary me-1"></i> Menghitung tarif pengiriman KiriminAja...
        </div>

        <div v-else-if="quoteError" class="alert alert-danger small py-2 mb-0">
          <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ quoteError }}
        </div>

        <div v-else-if="courierOptions.length === 0" class="alert alert-warning small py-2 mb-0">
          Layanan kurir belum tersedia untuk tujuan ini. Pastikan kelurahan/koordinat peta sudah benar lalu klik Cek Ongkir.
        </div>

        <div v-else class="d-flex flex-column gap-2">
          <label
            v-for="opt in courierOptions"
            :key="opt.key"
            class="p-2 px-3 rounded-3 border bg-white d-flex align-items-center justify-content-between gap-2 cursor-pointer"
            :class="isSelectedOption(opt) ? 'border-primary bg-primary-subtle shadow-sm' : ''"
          >
            <div class="d-flex align-items-center gap-2">
              <input
                type="radio"
                class="form-check-input mt-0"
                :checked="isSelectedOption(opt)"
                @change="selectCourierOption(opt)"
              />
              <div>
                <div class="small fw-bold text-dark d-flex align-items-center gap-1 flex-wrap">
                  <span
                    class="badge"
                    :class="opt.type === 'instant' ? 'bg-success' : 'bg-primary'"
                    style="font-size: 0.65rem;"
                  >
                    {{ opt.type === 'instant' ? 'INSTANT' : 'EXPRESS' }}
                  </span>
                  <span>{{ opt.courier_name }} - {{ opt.service_name }}</span>
                </div>
                <small class="text-muted" style="font-size: 0.74rem;">
                  Estimasi: {{ opt.etd || 'Sesuai kurir' }}
                </small>
              </div>
            </div>
            <div class="font-mono fw-bold text-success text-nowrap">
              Rp {{ formatRupiah(opt.cost) }}
            </div>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
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

let quoteTimer = null

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

const emitModel = (next) => {
  emit('update:modelValue', next)
}

const setFulfillmentType = (type) => {
  const next = {
    ...props.modelValue,
    fulfillment_type: type,
  }
  if (type === 'pickup') {
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
    next.shipping.type = ''
    next.shipping.courier = ''
    next.shipping.service_type = ''
    next.shipping.service_name = ''
    next.shipping.cost = 0
    next.shipping.etd = ''
  }
  emitModel(next)
}

const selectCourierOption = (opt) => {
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

const fetchQuote = async () => {
  const dest = activeDestination.value
  if (props.modelValue.fulfillment_type !== 'delivery' || !dest.district_id || !props.store?.id) {
    courierOptions.value = []
    return
  }

  quoting.value = true
  quoteError.value = ''
  try {
    const res = await axios.post('/api/v1/shipping/quote', {
      store_id: props.store.id,
      district_id: dest.district_id,
      subdistrict_id: dest.subdistrict_id || null,
      latitude: dest.latitude ?? null,
      longitude: dest.longitude ?? null,
      address: dest.address || null,
      total_qty: Math.max(1, Number(props.totalQty || 1)),
      item_value: Math.max(0, Number(props.subtotalAfterDiscount || 10000)),
    })

    const opts = res.data?.data?.options || []
    courierOptions.value = opts

    if (opts.length > 0) {
      const current = opts.find((o) => isSelectedOption(o))
      if (current) {
        selectCourierOption(current)
      } else {
        selectCourierOption(opts[0])
      }
    } else {
      updateShippingField('cost', 0)
    }
  } catch (err) {
    courierOptions.value = []
    quoteError.value = err.response?.data?.message || 'Gagal menghitung biaya pengiriman KiriminAja.'
    updateShippingField('cost', 0)
  } finally {
    quoting.value = false
  }
}

const scheduleQuote = () => {
  if (quoteTimer) clearTimeout(quoteTimer)
  quoteTimer = setTimeout(() => {
    fetchQuote()
  }, 350)
}

watch(
  () => [
    props.modelValue.fulfillment_type,
    props.modelValue.client_address_id,
    activeDestination.value.district_id,
    activeDestination.value.subdistrict_id,
    activeDestination.value.latitude,
    activeDestination.value.longitude,
    props.totalQty,
    props.subtotalAfterDiscount,
  ],
  () => {
    if (props.modelValue.fulfillment_type === 'delivery' && activeDestination.value.district_id) {
      scheduleQuote()
    }
  }
)

watch(
  () => [props.isLoyalty, props.savedAddresses.length],
  ([loyal]) => {
    if (loyal && props.savedAddresses.length > 0 && !addingNewAddress.value && !props.modelValue.client_address_id && props.modelValue.fulfillment_type === 'delivery') {
      const def = props.savedAddresses.find((a) => a.is_default) || props.savedAddresses[0]
      if (def) selectSavedAddress(def)
    }
  }
)
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
.fulfillment-choice-card {
  transition: all 0.18s ease;
}
.fulfillment-choice-card:hover {
  border-color: #0ea5e9 !important;
}
</style>

