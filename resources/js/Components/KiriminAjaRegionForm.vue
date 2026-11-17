<template>
  <div class="ka-region-form">
    <div class="ka-grid-2">
      <div class="ka-field">
        <label :for="`${uid}-province`" class="ka-label">
          Provinsi <span class="text-danger">*</span>
        </label>
        <SearchableSelect
          :button-id="`${uid}-province`"
          :model-value="modelValue.province_id"
          :options="provinces"
          :loading="loadingProvinces"
          :load-error="provincesError"
          placeholder="-- Pilih Provinsi --"
          search-placeholder="Cari provinsi..."
          @select="onProvinceSelect"
          @retry="loadProvinces(true)"
        />
      </div>

      <div class="ka-field">
        <label :for="`${uid}-city`" class="ka-label">
          Kota / Kabupaten <span class="text-danger">*</span>
        </label>
        <SearchableSelect
          :button-id="`${uid}-city`"
          :model-value="modelValue.city_id"
          :options="cities"
          :loading="loadingCities"
          :load-error="citiesError"
          :disabled="!modelValue.province_id"
          :placeholder="modelValue.province_id ? '-- Pilih Kota / Kabupaten --' : 'Pilih provinsi terlebih dahulu'"
          search-placeholder="Cari kota/kabupaten..."
          @select="onCitySelect"
          @retry="loadCities(modelValue.province_id)"
        />
      </div>
    </div>

    <div class="ka-grid-3">
      <div class="ka-field">
        <label :for="`${uid}-district`" class="ka-label">
          Kecamatan <span class="text-danger">*</span>
        </label>
        <SearchableSelect
          :button-id="`${uid}-district`"
          :model-value="modelValue.district_id"
          :options="districts"
          :loading="loadingDistricts"
          :load-error="districtsError"
          :disabled="!modelValue.city_id"
          :placeholder="modelValue.city_id ? '-- Pilih Kecamatan --' : 'Pilih kota terlebih dahulu'"
          search-placeholder="Cari kecamatan..."
          @select="onDistrictSelect"
          @retry="loadDistricts(modelValue.city_id)"
        />
      </div>

      <div class="ka-field">
        <label :for="`${uid}-subdistrict`" class="ka-label">
          Kelurahan / Desa <span class="text-danger">*</span>
        </label>
        <SearchableSelect
          :button-id="`${uid}-subdistrict`"
          :model-value="modelValue.subdistrict_id"
          :options="subdistricts"
          :loading="loadingSubdistricts"
          :load-error="subdistrictsError"
          :disabled="!modelValue.district_id"
          :placeholder="modelValue.district_id ? '-- Pilih Kelurahan --' : 'Pilih kecamatan terlebih dahulu'"
          search-placeholder="Cari kelurahan..."
          @select="onSubdistrictSelect"
          @retry="loadSubdistricts(modelValue.district_id)"
        />
      </div>

      <div class="ka-field">
        <label :for="`${uid}-postal`" class="ka-label">
          Kode Pos <span class="text-danger">*</span>
        </label>
        <input
          :id="`${uid}-postal`"
          :value="modelValue.postal_code"
          type="text"
          inputmode="numeric"
          autocomplete="postal-code"
          maxlength="5"
          class="ka-input font-mono"
          placeholder="12345"
          @input="updateField('postal_code', $event.target.value.replace(/\D+/g, '').slice(0, 5))"
        />
      </div>
    </div>

    <div class="ka-grid-address">
      <div class="ka-field">
        <label :for="`${uid}-address`" class="ka-label">
          Alamat Lengkap (Jalan / No. Rumah / RT-RW) <span class="text-danger">*</span>
        </label>
        <textarea
          :id="`${uid}-address`"
          :value="modelValue.address"
          rows="2"
          autocomplete="street-address"
          class="ka-textarea"
          placeholder="Contoh: Jl. Melati Indah No. 15, RT 03/RW 05..."
          @input="updateField('address', $event.target.value)"
        ></textarea>
      </div>

      <div class="ka-field">
        <label :for="`${uid}-note`" class="ka-label">
          Catatan Gang / Patokan
        </label>
        <textarea
          :id="`${uid}-note`"
          :value="modelValue.address_note"
          rows="2"
          maxlength="150"
          class="ka-textarea"
          placeholder="Contoh: Masuk gang beringin 20m, rumah pagar hijau..."
          @input="updateField('address_note', $event.target.value)"
        ></textarea>
      </div>
    </div>

    <div class="ka-map-block">
      <div class="ka-label ka-label-map">
        <i class="fa-solid fa-map-pin text-danger"></i>
        <span>Geotag Peta Lokasi (Klik &amp; Geser Marker - OpenStreetMap)</span>
        <span class="text-danger">*</span>
      </div>
      <GeotagMap
        :latitude="modelValue.latitude"
        :longitude="modelValue.longitude"
        :geo-address="modelValue.geo_address"
        :default-lat="defaultLat"
        :default-lng="defaultLng"
        :height="mapHeight"
        @update:coordinates="onCoordinatesUpdate"
        @update:geo-address="updateField('geo_address', $event)"
        @detected="onGeoDetected"
      />
      <div v-if="detectedRegionHint" class="ka-region-hint">
        <span class="ka-region-hint-text">
          <i class="fa-solid fa-wand-magic-sparkles"></i>
          Wilayah peta terdeteksi: <strong>{{ detectedRegionLabel }}</strong>
        </span>
        <button type="button" class="ka-apply-btn" @click="applyDetectedRegion">
          Terapkan ke Dropdown
        </button>
      </div>
    </div>
  </div>
</template>

<script>
let cachedProvincesList = null
let formInstanceSeq = 0
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

const uid = `ka-reg-${++formInstanceSeq}`

const provinces = ref([])
const cities = ref([])
const districts = ref([])
const subdistricts = ref([])

const loadingProvinces = ref(false)
const loadingCities = ref(false)
const loadingDistricts = ref(false)
const loadingSubdistricts = ref(false)

const provincesError = ref('')
const citiesError = ref('')
const districtsError = ref('')
const subdistrictsError = ref('')

const detectedRegionHint = ref(null)

// Draft sinkron mencegah patch berturut-turut dalam tick yang sama saling menimpa
let localDraft = { ...(props.modelValue || {}) }
watch(
  () => props.modelValue,
  (val) => {
    localDraft = { ...(val || {}) }
  }
)

const detectedRegionLabel = computed(() => {
  const m = detectedRegionHint.value
  if (!m) return ''
  return [m.subdistrict_name, m.district_name, m.city_name, m.province_name].filter(Boolean).join(', ')
})

const updateFields = (patch) => {
  localDraft = { ...localDraft, ...patch }
  emit('update:modelValue', localDraft)
}

const updateField = (key, value) => {
  localDraft = { ...localDraft, [key]: value }
  emit('update:modelValue', localDraft)
}

const loadProvinces = async (force = false) => {
  if (!force && Array.isArray(cachedProvincesList) && cachedProvincesList.length > 0) {
    provinces.value = cachedProvincesList
    provincesError.value = ''
    return
  }
  loadingProvinces.value = true
  provincesError.value = ''
  try {
    const res = await axios.get('/api/v1/shipping/provinces')
    const list = res.data?.data || []
    provinces.value = list
    if (list.length > 0) {
      cachedProvincesList = list
    }
  } catch (e) {
    provinces.value = []
    provincesError.value = 'Gagal memuat daftar provinsi.'
  } finally {
    loadingProvinces.value = false
  }
}

const loadCities = async (provinceId) => {
  if (!provinceId) {
    cities.value = []
    citiesError.value = ''
    return
  }
  loadingCities.value = true
  citiesError.value = ''
  try {
    const res = await axios.get('/api/v1/shipping/cities', { params: { province_id: provinceId } })
    cities.value = res.data?.data || []
  } catch (e) {
    cities.value = []
    citiesError.value = 'Gagal memuat kota/kabupaten.'
  } finally {
    loadingCities.value = false
  }
}

const loadDistricts = async (cityId) => {
  if (!cityId) {
    districts.value = []
    districtsError.value = ''
    return
  }
  loadingDistricts.value = true
  districtsError.value = ''
  try {
    const res = await axios.get('/api/v1/shipping/districts', { params: { city_id: cityId } })
    districts.value = res.data?.data || []
  } catch (e) {
    districts.value = []
    districtsError.value = 'Gagal memuat kecamatan.'
  } finally {
    loadingDistricts.value = false
  }
}

const loadSubdistricts = async (districtId) => {
  if (!districtId) {
    subdistricts.value = []
    subdistrictsError.value = ''
    return
  }
  loadingSubdistricts.value = true
  subdistrictsError.value = ''
  try {
    const res = await axios.get('/api/v1/shipping/subdistricts', { params: { district_id: districtId } })
    subdistricts.value = res.data?.data || []
  } catch (e) {
    subdistricts.value = []
    subdistrictsError.value = 'Gagal memuat kelurahan.'
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
  if (opt.postal_code && !localDraft.postal_code) {
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

const onCoordinatesUpdate = ({ latitude, longitude }) => {
  updateFields({ latitude, longitude })
}

const onGeoDetected = (info) => {
  const patch = {
    latitude: info.latitude,
    longitude: info.longitude,
    geo_address: info.geo_address,
  }
  if (info.postal_code && !localDraft.postal_code) {
    patch.postal_code = String(info.postal_code).replace(/\D+/g, '').slice(0, 5)
  }
  if (info.short_address && !localDraft.address) {
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

<style scoped>
.ka-region-form {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.ka-grid-2 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.ka-grid-3 {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 128px;
  gap: 0.75rem;
}

.ka-grid-address {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
  gap: 0.75rem;
}

@media (max-width: 640px) {
  .ka-grid-2,
  .ka-grid-3,
  .ka-grid-address {
    grid-template-columns: minmax(0, 1fr);
  }
}

.ka-field {
  min-inline-size: 0;
  display: flex;
  flex-direction: column;
}

.ka-label {
  display: block;
  font-size: 0.83rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.35rem;
}

.ka-label-map {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.ka-input,
.ka-textarea {
  width: 100%;
  box-sizing: border-box;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  background-color: #ffffff;
  color: #0f172a;
  font-size: max(0.92rem, 15px);
  line-height: 1.45;
  padding: 0.6rem 0.8rem;
  transition: border-color 0.18s ease, box-shadow 0.18s ease;
  outline: none;
}

.ka-input {
  min-height: 44px;
}

.ka-textarea {
  min-height: 76px;
  resize: vertical;
}

.ka-input::placeholder,
.ka-textarea::placeholder {
  color: #94a3b8;
}

.ka-input:focus,
.ka-textarea:focus {
  border-color: #e11d48;
  box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.12);
}

.ka-map-block {
  display: flex;
  flex-direction: column;
}

.ka-region-hint {
  margin-top: 0.6rem;
  padding: 0.65rem 0.85rem;
  border-radius: 10px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1e3a8a;
  font-size: 0.82rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.ka-region-hint-text {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.ka-apply-btn {
  border: none;
  border-radius: 8px;
  background: #2563eb;
  color: #ffffff;
  font-size: 0.78rem;
  font-weight: 700;
  padding: 0.35rem 0.75rem;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.ka-apply-btn:hover {
  background: #1d4ed8;
}
</style>

