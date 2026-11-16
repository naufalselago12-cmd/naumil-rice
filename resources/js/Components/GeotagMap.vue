<template>
  <div class="geotag-map-wrapper border rounded-3 p-2 bg-light">
    <div class="d-flex flex-wrap gap-2 mb-2">
      <div class="input-group input-group-sm flex-grow-1">
        <span class="input-group-text bg-white text-danger">
          <i class="fa-solid fa-location-dot"></i>
        </span>
        <input
          v-model="searchQuery"
          type="text"
          class="form-control"
          placeholder="Cari nama jalan, perumahan, atau patokan..."
          @keydown.enter.prevent="searchLocation"
        />
        <button
          type="button"
          class="btn btn-outline-primary fw-semibold"
          :disabled="searching || !searchQuery.trim()"
          @click="searchLocation"
        >
          <i v-if="searching" class="fa-solid fa-spinner fa-spin me-1"></i>
          <i v-else class="fa-solid fa-magnifying-glass me-1"></i>
          Cari Peta
        </button>
      </div>
      <button
        type="button"
        class="btn btn-sm btn-outline-success fw-semibold"
        :disabled="locating"
        @click="useCurrentGps"
      >
        <i v-if="locating" class="fa-solid fa-spinner fa-spin me-1"></i>
        <i v-else class="fa-solid fa-crosshairs me-1"></i>
        GPS Saya
      </button>
    </div>

    <!-- Hasil Pencarian Peta Nominatim -->
    <div v-if="searchResults.length > 0" class="list-group list-group-flush mb-2 border rounded bg-white shadow-sm" style="max-height: 150px; overflow-y: auto;">
      <button
        v-for="(item, idx) in searchResults"
        :key="idx"
        type="button"
        class="list-group-item list-group-item-action py-2 px-2 small text-start"
        @click="pickSearchResult(item)"
      >
        <i class="fa-solid fa-map-pin text-danger me-1"></i> {{ item.display_name }}
      </button>
    </div>

    <div
      ref="mapContainer"
      class="rounded-3 border bg-white"
      :style="{ height: height, width: '100%', zIndex: 1 }"
    ></div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 px-1">
      <small class="text-muted">
        <i class="fa-solid fa-hand-pointer text-primary me-1"></i>
        Klik peta atau geser pin marker untuk menentukan koordinat akurat.
      </small>
      <span v-if="hasCoords" class="badge bg-white text-dark border font-mono">
        {{ Number(latitude).toFixed(6) }}, {{ Number(longitude).toFixed(6) }}
      </span>
      <span v-else class="badge bg-warning-subtle text-dark border border-warning">
        Belum ada titik pin
      </span>
    </div>

    <div v-if="reversing" class="small text-primary mt-1 px-1">
      <i class="fa-solid fa-spinner fa-spin me-1"></i> Mendeteksi alamat dari koordinat...
    </div>
    <div v-else-if="geoAddress" class="small text-dark bg-white border rounded p-2 mt-2">
      <strong class="d-block text-success mb-1">
        <i class="fa-solid fa-map-location-dot me-1"></i> Alamat Terdeteksi Peta (OpenStreetMap):
      </strong>
      <span>{{ geoAddress }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: markerIcon2x,
  iconUrl: markerIcon,
  shadowUrl: markerShadow,
})

const props = defineProps({
  latitude: { type: [Number, String, null], default: null },
  longitude: { type: [Number, String, null], default: null },
  geoAddress: { type: String, default: '' },
  height: { type: String, default: '240px' },
  defaultLat: { type: [Number, String, null], default: -6.2088 },
  defaultLng: { type: [Number, String, null], default: 106.8456 },
  matchRegion: { type: Boolean, default: true },
})

const emit = defineEmits(['update:latitude', 'update:longitude', 'update:geoAddress', 'detected'])

const mapContainer = ref(null)
const searchQuery = ref('')
const searchResults = ref([])
const searching = ref(false)
const locating = ref(false)
const reversing = ref(false)

let map = null
let marker = null
let resizeObserver = null
let internalUpdate = false

const hasCoords = computed(() => {
  const lat = Number(props.latitude)
  const lng = Number(props.longitude)
  return Number.isFinite(lat) && Number.isFinite(lng) && props.latitude !== null && props.latitude !== ''
})

const ensureMarker = (lat, lng, centerMap = true) => {
  if (!map) return
  const numLat = Number(lat)
  const numLng = Number(lng)
  if (!Number.isFinite(numLat) || !Number.isFinite(numLng)) return

  if (!marker) {
    marker = L.marker([numLat, numLng], { draggable: true }).addTo(map)
    marker.on('dragend', () => {
      const pos = marker.getLatLng()
      handlePinMoved(pos.lat, pos.lng, true)
    })
  } else {
    marker.setLatLng([numLat, numLng])
  }

  if (centerMap) {
    map.setView([numLat, numLng], Math.max(map.getZoom() || 15, 16))
  }
}

const handlePinMoved = async (lat, lng, shouldReverse = true) => {
  internalUpdate = true
  const fixedLat = Number(Number(lat).toFixed(7))
  const fixedLng = Number(Number(lng).toFixed(7))
  emit('update:latitude', fixedLat)
  emit('update:longitude', fixedLng)
  ensureMarker(fixedLat, fixedLng, false)
  setTimeout(() => {
    internalUpdate = false
  }, 50)

  if (!shouldReverse) return

  reversing.value = true
  try {
    const res = await axios.get('/api/v1/geo/reverse', {
      params: {
        latitude: fixedLat,
        longitude: fixedLng,
        match_region: props.matchRegion ? 1 : 0,
      },
    })
    if (res.data?.success && res.data?.data) {
      const info = res.data.data
      const detectedAddr = info.display_name || ''
      emit('update:geoAddress', detectedAddr)
      emit('detected', {
        latitude: fixedLat,
        longitude: fixedLng,
        geo_address: detectedAddr,
        short_address: info.short_address || detectedAddr,
        postal_code: info.postal_code || '',
        matched_region: info.matched_region || null,
      })
    }
  } catch (e) {
    // Biarkan koordinat tetap tersimpan meski reverse geocoding gagal
  } finally {
    reversing.value = false
  }
}

const searchLocation = async () => {
  const q = searchQuery.value.trim()
  if (q.length < 2) return
  searching.value = true
  try {
    const res = await axios.get('/api/v1/geo/search', { params: { q } })
    searchResults.value = res.data?.data || []
  } catch (e) {
    searchResults.value = []
  } finally {
    searching.value = false
  }
}

const pickSearchResult = (item) => {
  searchResults.value = []
  const lat = Number(item.latitude)
  const lng = Number(item.longitude)
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return
  ensureMarker(lat, lng, true)
  handlePinMoved(lat, lng, true)
}

const useCurrentGps = () => {
  if (!navigator.geolocation) {
    alert('Browser tidak mendukung deteksi GPS.')
    return
  }
  locating.value = true
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      locating.value = false
      const lat = pos.coords.latitude
      const lng = pos.coords.longitude
      ensureMarker(lat, lng, true)
      handlePinMoved(lat, lng, true)
    },
    () => {
      locating.value = false
      alert('Gagal mendeteksi lokasi GPS. Pastikan izin lokasi browser aktif atau klik langsung di peta.')
    },
    { enableHighAccuracy: true, timeout: 10000 }
  )
}

watch(
  () => [props.latitude, props.longitude],
  ([lat, lng]) => {
    if (internalUpdate) return
    if (lat !== null && lat !== '' && lng !== null && lng !== '') {
      ensureMarker(lat, lng, true)
    }
  }
)

onMounted(() => {
  if (!mapContainer.value) return
  const initLat = hasCoords.value ? Number(props.latitude) : Number(props.defaultLat || -6.2088)
  const initLng = hasCoords.value ? Number(props.longitude) : Number(props.defaultLng || 106.8456)

  map = L.map(mapContainer.value, {
    center: [initLat, initLng],
    zoom: hasCoords.value ? 16 : 13,
  })

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map)

  if (hasCoords.value) {
    ensureMarker(initLat, initLng, false)
  }

  map.on('click', (e) => {
    ensureMarker(e.latlng.lat, e.latlng.lng, false)
    handlePinMoved(e.latlng.lat, e.latlng.lng, true)
  })

  if (typeof ResizeObserver !== 'undefined') {
    resizeObserver = new ResizeObserver(() => {
      map?.invalidateSize()
    })
    resizeObserver.observe(mapContainer.value)
  }
  setTimeout(() => map?.invalidateSize(), 200)
})

onBeforeUnmount(() => {
  resizeObserver?.disconnect()
  if (map) {
    map.remove()
    map = null
  }
})
</script>

