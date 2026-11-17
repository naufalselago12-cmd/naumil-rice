<template>
  <div class="gm-wrapper">
    <div class="gm-toolbar">
      <div class="gm-search-group">
        <span class="gm-search-pin" aria-hidden="true">
          <i class="fa-solid fa-location-dot"></i>
        </span>
        <input
          v-model="searchQuery"
          type="text"
          class="gm-search-input"
          placeholder="Cari nama jalan, perumahan, atau patokan..."
          @keydown.enter.prevent="searchLocation"
        />
        <button
          type="button"
          class="gm-btn gm-btn-search"
          :disabled="searching || !searchQuery.trim()"
          @click="searchLocation"
        >
          <i v-if="searching" class="fa-solid fa-spinner fa-spin"></i>
          <i v-else class="fa-solid fa-magnifying-glass"></i>
          <span>Cari Peta</span>
        </button>
      </div>
      <button
        type="button"
        class="gm-btn gm-btn-gps"
        :disabled="locating"
        @click="useCurrentGps"
      >
        <i v-if="locating" class="fa-solid fa-spinner fa-spin"></i>
        <i v-else class="fa-solid fa-crosshairs"></i>
        <span>GPS Saya</span>
      </button>
    </div>

    <!-- Hasil Pencarian Peta Nominatim -->
    <div v-if="searchResults.length > 0" class="gm-results">
      <button
        v-for="(item, idx) in searchResults"
        :key="idx"
        type="button"
        class="gm-result-item"
        @click="pickSearchResult(item)"
      >
        <i class="fa-solid fa-map-pin text-danger"></i>
        <span>{{ item.display_name }}</span>
      </button>
    </div>

    <div class="gm-map-shell">
      <div
        ref="mapContainer"
        class="gm-map-canvas"
        :style="{ height: height }"
      ></div>
    </div>

    <div class="gm-meta-row">
      <small class="gm-hint-text">
        <i class="fa-solid fa-hand-pointer text-primary"></i>
        Klik peta atau geser pin marker untuk menentukan koordinat akurat.
      </small>
      <span v-if="hasCoords" class="gm-coord-badge font-mono">
        {{ Number(latitude).toFixed(6) }}, {{ Number(longitude).toFixed(6) }}
      </span>
      <span v-else class="gm-coord-badge is-empty">
        Belum ada titik pin
      </span>
    </div>

    <div v-if="reversing" class="gm-status-loading">
      <i class="fa-solid fa-spinner fa-spin"></i> Mendeteksi alamat dari koordinat...
    </div>
    <div v-else-if="geoAddress" class="gm-detected-box">
      <strong class="gm-detected-title">
        <i class="fa-solid fa-map-location-dot"></i> Alamat Terdeteksi Peta (OpenStreetMap):
      </strong>
      <span class="gm-detected-text">{{ geoAddress }}</span>
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

const emit = defineEmits(['update:latitude', 'update:longitude', 'update:coordinates', 'update:geoAddress', 'detected'])

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
  emit('update:coordinates', { latitude: fixedLat, longitude: fixedLng })
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

<style scoped>
.gm-wrapper {
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.75rem;
}

.gm-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.65rem;
}

.gm-search-group {
  flex: 1 1 240px;
  min-inline-size: 0;
  display: flex;
  align-items: stretch;
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  overflow: hidden;
}

.gm-search-group:focus-within {
  border-color: #e11d48;
  box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.12);
}

.gm-search-pin {
  display: inline-flex;
  align-items: center;
  padding: 0 0.65rem;
  color: #e11d48;
  font-size: 0.9rem;
}

.gm-search-input {
  flex: 1;
  min-inline-size: 0;
  min-height: 40px;
  border: none;
  outline: none;
  font-size: 0.88rem;
  color: #0f172a;
  background: transparent;
  padding: 0.35rem 0.25rem;
}

.gm-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  min-height: 40px;
  padding: 0 0.85rem;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.gm-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.gm-btn-search {
  border: none;
  border-left: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #be123c;
}

.gm-btn-search:hover:not(:disabled) {
  background: #ffe4e6;
}

.gm-btn-gps {
  border: 1.5px solid #10b981;
  border-radius: 10px;
  background: #f0fdf4;
  color: #15803d;
}

.gm-btn-gps:hover:not(:disabled) {
  background: #dcfce7;
}

.gm-results {
  max-height: 150px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #ffffff;
  margin-bottom: 0.65rem;
}

.gm-result-item {
  width: 100%;
  border: none;
  border-bottom: 1px solid #f1f5f9;
  background: #ffffff;
  padding: 0.5rem 0.75rem;
  font-size: 0.82rem;
  color: #1e293b;
  text-align: left;
  display: flex;
  align-items: flex-start;
  gap: 0.45rem;
  cursor: pointer;
}

.gm-result-item:last-child {
  border-bottom: none;
}

.gm-result-item:hover {
  background: #fff1f2;
  color: #9f1239;
}

/* Mengisolasi stacking context Leaflet agar dropdown wilayah di atasnya selalu dapat diklik */
.gm-map-shell {
  position: relative;
  isolation: isolate;
  z-index: 1;
  border: 1.5px solid #cbd5e1;
  border-radius: 10px;
  overflow: hidden;
  background: #f8fafc;
}

.gm-map-canvas {
  width: 100%;
}

.gm-meta-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-top: 0.55rem;
}

.gm-hint-text {
  color: #64748b;
  font-size: 0.78rem;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.gm-coord-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  color: #0f172a;
  font-size: 0.75rem;
  font-weight: 700;
}

.gm-coord-badge.is-empty {
  background: #fffbeb;
  border-color: #fcd34d;
  color: #92400e;
}

.gm-status-loading {
  margin-top: 0.45rem;
  font-size: 0.8rem;
  color: #e11d48;
  font-weight: 600;
}

.gm-detected-box {
  margin-top: 0.6rem;
  padding: 0.65rem 0.85rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 10px;
  font-size: 0.82rem;
  color: #1e293b;
}

.gm-detected-title {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  color: #15803d;
  margin-bottom: 0.25rem;
}

.gm-detected-text {
  display: block;
  line-height: 1.45;
}
</style>

