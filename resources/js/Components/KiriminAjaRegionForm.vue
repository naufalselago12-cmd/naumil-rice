<template>
  <div class="kiriminaja-region-form">
    <div class="row g-2 mb-2">
      <div class="col-md-6">
        <label class="form-label small fw-bold mb-1">Provinsi <span class="text-danger">*</span></label>
        <SearchableSelect
          :model-value="modelValue.province_id"
          :options="provinces"
          :loading="loadingProvinces"
          placeholder="-- Pilih Provinsi --"
          search-placeholder="Cari provinsi..."
          @select="onProvinceSelect"
        />
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-bold mb-1">Kota / Kabupaten <span class="text-danger">*</span></label>
        <SearchableSelect
          :model-value="modelValue.city_id"
          :options="cities"
          :loading="loadingCities"
          :disabled="!modelValue.province_id"
          placeholder="-- Pilih Kota/Kab --"
          search-placeholder="Cari kota/kabupaten..."
          @select="onCitySelect"
        />
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-bold mb-1">Kecamatan <span class="text-danger">*</span></label>
        <SearchableSelect
          :model-value="modelValue.district_id"
          :options="districts"
          :loading="loadingDistricts"
          :disabled="!modelValue.city_id"
          placeholder="-- Pilih Kecamatan --"
          search-placeholder="Cari kecamatan..."
          @select="onDistrictSelect"
        />
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-bold mb-1">Kelurahan / Desa <span class="text-danger">*</span></label>
        <SearchableSelect
          :model-value="modelValue.subdistrict_id"
          :options="subdistricts"
          :loading="loadingSubdistricts"
          :disabled="!modelValue.district_id"
          placeholder="-- Pilih Kelurahan --"
          search-placeholder="Cari kelurahan..."
          @select="onSubdistrictSelect"
        />
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-bold mb-1">Kode Pos <span class="text-danger">*</span></label>
        <input
          :value="modelValue.postal_code"
          type="text"
          maxlength="5"
          class="form-control font-mono"
          placeholder="12345"
          @input="updateField('postal_code', $event.target.value.replace(/\D+/g, '').slice(0, 5))"
        />
      </div>
    </div>

    <div class="row g-2 mb-2">
      <div class="col-md-7">
        <label class="form-label small fw-bold mb-1">Alamat Lengkap (Jalan / No. Rumah / RT-RW) <span class="text-danger">*</span></label>
        <textarea
          :value="modelValue.address"
          rows="2"
          class="form-control"
          placeholder="Contoh: Jl. Melati Indah No. 15, RT 03/RW 05..."
          @input="updateField('address', $event.target.value)"
        ></textarea>
      </div>
      <div class="col-md-5">
        <label class="form-label small fw-bold mb-1">Catatan Gang / Patokan</label>
        <textarea
          :value="modelValue.address_note"
          rows="2"
          maxlength="150"
          class="form-control"
          placeholder="Contoh: Masuk gang beringin 20m, rumah pagar hijau..."
          @input="updateField('address_note', $event.target.value)"
        ></textarea>
      </div>
    </div>

    <div class="mb-1">
      <label class="form-label small fw-bold mb-1">
        <i class="fa-solid fa-map-pin text-danger me-1"></i>
        Geotag Peta Lokasi (Klik &amp; Geser Marker - OpenStreetMap) <span class="text-danger">*</span>
      </label>
      <GeotagMap
        :latitude="modelValue.latitude"
        :longitude="modelValue.longitude"
        :geo-address="modelValue.geo_address"
        :default-lat="defaultLat"
        :default-lng="defaultLng"
        :height="mapHeight"
        @update:latitude="updateField('latitude', $event)"
        @update:longitude="updateField('longitude', $event)"
        @update:geo-address="updateField('geo_address', $event)"
        @detected="onGeoDetected"
      />
      <div v-if="detectedRegionHint" class="alert alert-info py-2 px-3 small mt-2 mb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span>
          <i class="fa-solid fa-wand-magic-sparkles me-1"></i>
          Wilayah peta terdeteksi: <strong>{{ detectedRegionLabel }}</strong>
        </span>
        <button type="button" class="btn btn-sm btn-primary py-0 px-2 fw-semibold" @click="applyDetectedRegion">
          Terapkan ke Dropdown
        </button>
      </div>
    </div>
  </div>
</template>

<script>
let cachedProvincesList = null
</script>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import SearchableSelect from './SearchableSelect.vue'
import GeotagMap from './GeotagMap.vue'

const props = defineProps({
  modelValue: { type: Object, required: true },
  defaultLat: { type: [Number, String, null], default: -6.2088 },
  defaultLng: { type: [Number, String, null], default: 106.8456 },
  mapHeight: { type: String, default: '220px' },
})

const emit = defineEmits(['update:modelValue'])

const provinces = ref([])
const cities = ref([])
const districts = ref([])
const subdistricts = ref([])

const loadingProvinces = ref(false)
const loadingCities = ref(false)
const loadingDistricts = ref(false)
const loadingSubdistricts = ref(false)

const detectedRegionHint = ref(null)

const detectedRegionLabel = computed(() => {
  const m = detectedRegionHint.value
  if (!m) return ''
  return [m.subdistrict_name, m.district_name, m.city_name, m.province_name].filter(Boolean).join(', ')
})

const updateFields = (patch) => {
  emit('update:modelValue', { ...props.modelValue, ...patch })
}

const updateField = (key, value) => {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}

const loadProvinces = async () => {
  if (cachedProvincesList) {
    provinces.value = cachedProvincesList
    return
  }
  loadingProvinces.value = true
  try {
    const res = await axios.get('/api/v1/shipping/provinces')
    provinces.value = res.data?.data || []
    cachedProvincesList = provinces.value
  } catch (e) {
    provinces.value = []
  } finally {
    loadingProvinces.value = false
  }
}

const loadCities = async (provinceId) => {
  if (!provinceId) {
    cities.value = []
    return
  }
  loadingCities.value = true
  try {
    const res = await axios.get('/api/v1/shipping/cities', { params: { province_id: provinceId } })
    cities.value = res.data?.data || []
  } catch (e) {
    cities.value = []
  } finally {
    loadingCities.value = false
  }
}

const loadDistricts = async (cityId) => {
  if (!cityId) {
    districts.value = []
    return
  }
  loadingDistricts.value = true
  try {
    const res = await axios.get('/api/v1/shipping/districts', { params: { city_id: cityId } })
    districts.value = res.data?.data || []
  } catch (e) {
    districts.value = []
  } finally {
    loadingDistricts.value = false
  }
}

const loadSubdistricts = async (districtId) => {
  if (!districtId) {
    subdistricts.value = []
    return
  }
  loadingSubdistricts.value = true
  try {
    const res = await axios.get('/api/v1/shipping/subdistricts', { params: { district_id: districtId } })
    subdistricts.value = res.data?.data || []
  } catch (e) {
    subdistricts.value = []
  } finally {
    loadingSubdistricts.value = false
  }
}

const onProvinceSelect = (opt) => {
  cities.value = []
  districts.value = []
  subdistricts.value = []
  updateFields({
    province_id: opt.id,
    province_name: opt.name,
    city_id: '',
    city_name: '',
    district_id: '',
    district_name: '',
    subdistrict_id: '',
    subdistrict_name: '',
  })
  loadCities(opt.id)
}

const onCitySelect = (opt) => {
  districts.value = []
  subdistricts.value = []
  const patch = {
    city_id: opt.id,
    city_name: opt.name,
    district_id: '',
    district_name: '',
    subdistrict_id: '',
    subdistrict_name: '',
  }
  if (opt.postal_code && !props.modelValue.postal_code) {
    patch.postal_code = String(opt.postal_code).slice(0, 5)
  }
  updateFields(patch)
  loadDistricts(opt.id)
}

const onDistrictSelect = (opt) => {
  subdistricts.value = []
  updateFields({
    district_id: opt.id,
    district_name: opt.name,
    subdistrict_id: '',
    subdistrict_name: '',
  })
  loadSubdistricts(opt.id)
}

const onSubdistrictSelect = (opt) => {
  updateFields({
    subdistrict_id: opt.id,
    subdistrict_name: opt.name,
  })
}

const onGeoDetected = (info) => {
  const patch = {
    latitude: info.latitude,
    longitude: info.longitude,
    geo_address: info.geo_address,
  }
  if (info.postal_code && !props.modelValue.postal_code) {
    patch.postal_code = String(info.postal_code).replace(/\D+/g, '').slice(0, 5)
  }
  if (info.short_address && !props.modelValue.address) {
    patch.address = info.short_address
  }
  updateFields(patch)

  if (info.matched_region && info.matched_region.province_id) {
    detectedRegionHint.value = info.matched_region
  }
}

const applyDetectedRegion = async () => {
  const m = detectedRegionHint.value
  if (!m || !m.province_id) return
  detectedRegionHint.value = null

  updateFields({
    province_id: m.province_id,
    province_name: m.province_name || '',
    city_id: m.city_id || '',
    city_name: m.city_name || '',
    district_id: m.district_id || '',
    district_name: m.district_name || '',
    subdistrict_id: m.subdistrict_id || '',
    subdistrict_name: m.subdistrict_name || '',
  })

  if (m.province_id) await loadCities(m.province_id)
  if (m.city_id) await loadDistricts(m.city_id)
  if (m.district_id) await loadSubdistricts(m.district_id)
}

const hydrateExistingCascade = async () => {
  await loadProvinces()
  if (props.modelValue.province_id) {
    await loadCities(props.modelValue.province_id)
  }
  if (props.modelValue.city_id) {
    await loadDistricts(props.modelValue.city_id)
  }
  if (props.modelValue.district_id) {
    await loadSubdistricts(props.modelValue.district_id)
  }
}

watch(
  () => [props.modelValue.province_id, props.modelValue.city_id, props.modelValue.district_id],
  async ([p, c, d], [oldP, oldC, oldD]) => {
    if (p && p !== oldP && cities.value.length === 0) await loadCities(p)
    if (c && c !== oldC && districts.value.length === 0) await loadDistricts(c)
    if (d && d !== oldD && subdistricts.value.length === 0) await loadSubdistricts(d)
  }
)

onMounted(() => {
  hydrateExistingCascade()
})
</script>

