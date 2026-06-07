<script setup>
/**
 * ============================================================================
 *  MapView — Peta monitoring wilayah (Leaflet)
 * ============================================================================
 *  - Lokasi berbasis kecamatan/desa (bukan GPS realtime perangkat)
 *  - Marker warna: hijau = negative, merah = positive, abu = offline/no data
 *  - Polling realtime tiap 5 detik
 *  - State: loading / empty / error / data
 *
 *  CATATAN PENTING soal Leaflet di Vite:
 *  - Instance map disimpan di variabel biasa (BUKAN ref) — membuat objek
 *    Leaflet reaktif menyebabkan error & lag.
 *  - Marker memakai L.divIcon (HTML) sehingga TIDAK bergantung pada file
 *    gambar marker bawaan Leaflet yang sering rusak path-nya di bundler.
 *  - Container peta diberi tinggi eksplisit; invalidateSize() dipanggil
 *    setelah mount agar tidak muncul peta abu-abu.
 * ============================================================================
 */
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { MapPin, RefreshCw, WifiOff } from 'lucide-vue-next'
import { mapApi } from '@/services/api'
import { usePolling } from '@/composables/usePolling'

// --- Pusat peta: wilayah Banjar, Jawa Barat (sesuaikan bila perlu) ---
const DEFAULT_CENTER = [-7.366, 108.534]
const DEFAULT_ZOOM = 13

const mapEl = ref(null)      // div container
let map = null               // instance Leaflet (non-reactif, sengaja)
let markerLayer = null       // LayerGroup untuk semua marker

// --- Polling data marker tiap 5 detik (per lokasi sampel) ---
const { data, error, loading, lastUpdated, refresh } =
  usePolling(() => mapApi.measurements(), 5000)

// --- Warna marker berdasarkan status hasil ---
function colorFor(result) {
  if (result === 'positive') return '#dc2626' // merah
  if (result === 'negative') return '#059669' // hijau
  return '#9ca3af'                              // abu (offline / no data)
}

// --- Buat icon HTML (pin) berwarna — tidak butuh file gambar ---
function pinIcon(result) {
  const c = colorFor(result)
  return L.divIcon({
    className: 'dengue-pin',
    html: `<span style="
      display:block;width:18px;height:18px;border-radius:50% 50% 50% 0;
      background:${c};transform:rotate(-45deg);
      border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4);"></span>`,
    iconSize: [18, 18],
    iconAnchor: [9, 18],
    popupAnchor: [0, -16],
  })
}

function popupHtml(d) {
  const fmt = (v, suf = '') => (v === null || v === undefined ? '–' : v + suf)
  const statusColor = colorFor(d.result)
  const statusLabel = d.result ? d.result.toUpperCase() : 'NO DATA'
  return `
    <div style="font-family:Inter,sans-serif;min-width:190px">
      <div style="font-weight:600;font-size:13px;margin-bottom:4px">${fmt(d.device_id)}</div>
      <div style="display:inline-block;font-size:11px;font-weight:600;color:#fff;
        background:${statusColor};padding:1px 8px;border-radius:10px;margin-bottom:6px">
        ${statusLabel}
      </div>
      <table style="font-size:12px;width:100%;border-collapse:collapse">
        <tr><td style="color:#6b7280;padding:1px 0">Peak current</td><td style="text-align:right">${fmt(d.peak_current, ' µA')}</td></tr>
        <tr><td style="color:#6b7280;padding:1px 0">Peak voltage</td><td style="text-align:right">${fmt(d.peak_voltage, ' V')}</td></tr>
        <tr><td style="color:#6b7280;padding:1px 0">Baterai</td><td style="text-align:right">${fmt(d.battery, ' V')}</td></tr>
        <tr><td style="color:#6b7280;padding:1px 0">Kecamatan</td><td style="text-align:right">${fmt(d.kecamatan)}</td></tr>
        <tr><td style="color:#6b7280;padding:1px 0">Desa</td><td style="text-align:right">${fmt(d.desa)}</td></tr>
        <tr><td style="color:#6b7280;padding:1px 0">Jumlah sampel</td><td style="text-align:right">${fmt(d.sample_count)}</td></tr>
        <tr><td style="color:#6b7280;padding:1px 0">Update</td><td style="text-align:right">${fmt(d.updated_at)}</td></tr>
      </table>
    </div>`
}

// --- Gambar ulang marker setiap data berubah ---
function renderMarkers(list) {
  if (!map || !markerLayer) return
  markerLayer.clearLayers()
  const valid = (list || []).filter(
    (d) => typeof d.latitude === 'number' && typeof d.longitude === 'number'
  )
  valid.forEach((d) => {
    L.marker([d.latitude, d.longitude], { icon: pinIcon(d.result) })
      .bindPopup(popupHtml(d))
      .addTo(markerLayer)
  })
  // Auto-fit ke semua marker bila ada
  if (valid.length) {
    const bounds = L.latLngBounds(valid.map((d) => [d.latitude, d.longitude]))
    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 })
  }
}

onMounted(async () => {
  await nextTick()
  map = L.map(mapEl.value, {
    center: DEFAULT_CENTER,
    zoom: DEFAULT_ZOOM,
    scrollWheelZoom: true,
  })
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
    maxZoom: 19,
  }).addTo(map)
  markerLayer = L.layerGroup().addTo(map)

  // Hindari peta abu-abu: paksa hitung ulang ukuran setelah container siap
  setTimeout(() => map && map.invalidateSize(), 200)

  if (data.value) renderMarkers(data.value)
})

onUnmounted(() => {
  if (map) { map.remove(); map = null }
})

// Redraw saat data polling masuk
watch(data, (val) => renderMarkers(val))

const validCount = ref(0)
watch(data, (val) => {
  validCount.value = (val || []).filter(
    (d) => typeof d.latitude === 'number' && typeof d.longitude === 'number'
  ).length
})
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold tracking-tight text-ink flex items-center gap-2">
          <MapPin class="h-5 w-5 text-primary-600" :stroke-width="2" />
          Map Monitoring
        </h1>
        <p class="text-sm text-ink-subtle mt-0.5">
          Sebaran perangkat berdasarkan wilayah kecamatan / desa
        </p>
      </div>
      <button
        class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-white
               px-3 py-1.5 text-sm font-medium text-ink-muted hover:bg-surface-muted transition-colors"
        @click="refresh"
      >
        <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" :stroke-width="1.75" />
        Refresh
      </button>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap items-center gap-4 text-xs text-ink-muted">
      <span class="inline-flex items-center gap-1.5">
        <span class="h-3 w-3 rounded-full" style="background:#059669" /> Negative
      </span>
      <span class="inline-flex items-center gap-1.5">
        <span class="h-3 w-3 rounded-full" style="background:#dc2626" /> Positive
      </span>
      <span class="inline-flex items-center gap-1.5">
        <span class="h-3 w-3 rounded-full" style="background:#9ca3af" /> Offline / no data
      </span>
      <span v-if="lastUpdated" class="ml-auto text-2xs text-ink-faint">
        Update terakhir: {{ lastUpdated.toLocaleTimeString() }}
      </span>
    </div>

    <!-- Error banner (tidak menutup peta) -->
    <div
      v-if="error && error.isNetwork"
      class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800"
    >
      <WifiOff class="h-4 w-4" :stroke-width="1.75" />
      Tidak dapat terhubung ke server. Menampilkan data terakhir.
    </div>

    <!-- Map container — WAJIB punya tinggi eksplisit -->
    <div class="relative rounded-xl border border-line overflow-hidden bg-surface-muted">
      <div ref="mapEl" style="height: 60vh; min-height: 420px; width: 100%; z-index: 0" />

      <!-- Loading overlay (hanya saat pertama, sebelum ada data) -->
      <div
        v-if="loading && !data"
        class="absolute inset-0 flex items-center justify-center bg-white/60 backdrop-blur-sm"
      >
        <div class="flex items-center gap-2 text-sm text-ink-muted">
          <RefreshCw class="h-4 w-4 animate-spin" :stroke-width="1.75" />
          Memuat peta…
        </div>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="data && validCount === 0"
        class="absolute inset-0 flex items-center justify-center pointer-events-none"
      >
        <div class="rounded-lg bg-white/90 border border-line px-4 py-3 text-center pointer-events-auto">
          <MapPin class="h-6 w-6 text-ink-faint mx-auto mb-1" :stroke-width="1.5" />
          <p class="text-sm font-medium text-ink">Belum ada data lokasi</p>
          <p class="text-2xs text-ink-subtle mt-0.5">
            Pastikan tabel locations memiliki latitude &amp; longitude
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
