<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import {
  Search, Download, CloudUpload, Trash2, Eye, ChevronLeft, ChevronRight,
  MapPin, X, RefreshCw,
} from 'lucide-vue-next'

import StatusBadge from '@/components/ui/StatusBadge.vue'
import EditLocationModal from '@/components/EditLocationModal.vue'
import { useMeasurementStore, peakCurrentDisplay as peakCurrentValue } from '@/stores/measurement'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const store  = useMeasurementStore()
const auth   = useAuthStore()
const { list, listTotal, listPage, listPerPage, listLoading, filters } = storeToRefs(store)

// ===== Role check =====
// Tombol "Edit Location" hanya tampil untuk admin.
// Penjagaan kedua ada di backend (middleware role:admin).
const isAdmin = computed(() => auth.isAdmin)

// ===== Edit Location modal state =====
const editLocOpen        = ref(false)
const editLocMeasurement = ref(null)

function openEditLocation(m, event) {
  // Hentikan propagasi supaya klik tombol tidak sekaligus membuka Result
  event?.stopPropagation()
  editLocMeasurement.value = m
  editLocOpen.value = true
}

function onLocationSaved() {
  // Refresh list supaya kolom Location langsung diperbarui tanpa reload halaman
  store.fetchList()
}

// ===== Filtered methods =====
// Metode yang boleh muncul di filter untuk pengukuran baru. SWV historis
// tetap tersimpan di data, tapi tidak lagi ditawarkan sebagai opsi filter.
const FILTERABLE_METHODS = ['all', 'DPV', 'CV']

// Guard: kalau filter method lama/tersimpan berisi SWV (atau value lain
// yang sudah tidak tersedia di dropdown), jatuhkan ke "All methods" agar
// dropdown tidak error dan tabel tidak diam-diam kosong.
onMounted(() => {
  if (!FILTERABLE_METHODS.includes(filters.value.method)) {
    store.setFilters({ method: 'all' })
  }
})

// Debounce search
let searchDebounce = null
watch(() => filters.value.search, () => {
  if (searchDebounce) clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => store.fetchList(), 400)
})

watch([() => filters.value.status, () => filters.value.method], () => {
  store.setFilters({})
})

function openResult(id) {
  router.push(`/app/result/${id}`)
}

// Logic prioritas DPV/CV ada di stores/measurement.js (dipakai bareng
// dengan halaman Result supaya nilai yang ditampilkan konsisten).
function peakCurrentDisplay(m) {
  const value = peakCurrentValue(m)
  return value != null ? value.toFixed(3) : null
}

const hasActiveFilters = computed(() =>
  filters.value.search || filters.value.status !== 'all' || filters.value.method !== 'all'
)
const totalPages = computed(() => Math.ceil(listTotal.value / listPerPage.value))

function formatDate(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
  } catch { return '—' }
}
function formatTime(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })
  } catch { return '—' }
}

// Tampilkan nama lokasi paling deskriptif yang tersedia.
// Prioritas: location_name > kecamatan (fallback lama) > '—'
function locationDisplay(m) {
  if (!m.location) return null
  return m.location.location_name || m.location.kecamatan || null
}

function exportCsv() {
  if (!list.value.length) return
  const headers = ['date', 'sample_id', 'method', 'peak_current', 'peak_voltage', 'status', 'device', 'location']
  const rows = list.value.map(m => [
    m.created_at, m.sample_id, m.method, m.peak_current, m.peak_voltage, m.status,
    m.device?.device_id ?? '', m.location?.location_name ?? m.location?.kecamatan ?? '',
  ])
  const csv = [headers, ...rows].map(r => r.join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `measurements-${new Date().toISOString().slice(0, 10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <div class="space-y-6">
    <!-- Toolbar -->
    <div class="lab-card p-4">
      <div class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[260px] relative">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-ink-faint" :stroke-width="1.75" />
          <input v-model="filters.search" type="text"
                 placeholder="Search by sample ID or device..." class="field-input pl-10" />
        </div>

        <select v-model="filters.status" class="field-select min-w-[150px]">
          <option value="all">All results</option>
          <option value="positive">Positive</option>
          <option value="negative">Negative</option>
          <option value="warning">Warning</option>
          <option value="inconclusive">Inconclusive</option>
        </select>

        <select v-model="filters.method" class="field-select min-w-[120px]">
          <option value="all">All methods</option>
          <option value="DPV">DPV</option>
          <option value="CV">CV</option>
        </select>

        <button v-if="hasActiveFilters" @click="store.clearFilters()" class="btn-ghost text-sm py-2">
          <X class="h-3.5 w-3.5" :stroke-width="2" />Clear
        </button>

        <div class="ml-auto flex items-center gap-2">
          <button @click="store.fetchList()" class="btn-ghost text-sm py-2" :disabled="listLoading">
            <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': listLoading }" :stroke-width="1.75" />
            Refresh
          </button>
          <button @click="exportCsv" class="btn-secondary text-sm py-2" :disabled="!list.length">
            <Download class="h-3.5 w-3.5" :stroke-width="1.75" />Export CSV
          </button>
          <button class="btn-primary text-sm py-2">
            <CloudUpload class="h-3.5 w-3.5" :stroke-width="1.75" />Sync Cloud
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="lab-card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-surface-muted border-b border-line">
            <tr class="text-left">
              <th class="px-5 py-3 eyebrow">Date</th>
              <th class="px-5 py-3 eyebrow">Time</th>
              <th class="px-5 py-3 eyebrow">Sample ID</th>
              <th class="px-5 py-3 eyebrow">Method</th>
              <th class="px-5 py-3 eyebrow">Peak Current</th>
              <th class="px-5 py-3 eyebrow">Location</th>
              <th class="px-5 py-3 eyebrow">Device</th>
              <th class="px-5 py-3 eyebrow">Result</th>
              <th class="px-5 py-3 eyebrow text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in list" :key="m.id"
                class="border-t border-line hover:bg-surface-muted/60 transition-colors group cursor-pointer"
                tabindex="0" role="button" :aria-label="`Open result for sample ${m.sample_id}`"
                @click="openResult(m.id)"
                @keydown.enter="openResult(m.id)"
                @keydown.space.prevent="openResult(m.id)">
              <td class="px-5 py-3.5 text-ink-muted">{{ formatDate(m.created_at) }}</td>
              <td class="px-5 py-3.5 font-mono text-xs text-ink-subtle">{{ formatTime(m.created_at) }}</td>
              <td class="px-5 py-3.5 font-mono text-xs text-ink">{{ m.sample_id }}</td>
              <td class="px-5 py-3.5">
                <span class="font-mono text-xs px-1.5 py-0.5 rounded bg-surface-muted text-ink-muted">{{ m.method }}</span>
              </td>
              <td class="px-5 py-3.5 font-mono text-xs text-ink">
                <template v-if="peakCurrentDisplay(m) !== null">{{ peakCurrentDisplay(m) }} <span class="text-ink-faint ml-0.5">µA</span></template>
                <span v-else class="text-ink-faint">—</span>
              </td>
              <td class="px-5 py-3.5 text-ink-muted text-xs">
                <span v-if="locationDisplay(m)" class="inline-flex items-center gap-1">
                  <MapPin class="h-3 w-3 text-ink-faint" :stroke-width="1.75" />
                  {{ locationDisplay(m) }}
                </span>
                <span v-else class="text-ink-faint">—</span>
              </td>
              <td class="px-5 py-3.5 font-mono text-xs text-ink-muted">{{ m.device?.device_id ?? '—' }}</td>
              <td class="px-5 py-3.5">
                <StatusBadge :status="m.status" dot>{{ m.status }}</StatusBadge>
              </td>
              <td class="px-5 py-3.5 text-right">
                <div class="inline-flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <!-- View Result -->
                  <button @click.stop="openResult(m.id)"
                          class="p-1.5 rounded-md hover:bg-surface-muted text-ink-muted hover:text-ink"
                          title="Lihat Result">
                    <Eye class="h-3.5 w-3.5" :stroke-width="1.75" />
                  </button>

                  <!-- Edit Location — hanya admin -->
                  <button v-if="isAdmin"
                          @click="openEditLocation(m, $event)"
                          class="p-1.5 rounded-md hover:bg-primary-50 text-ink-muted hover:text-primary-600"
                          title="Edit Lokasi Pengujian">
                    <MapPin class="h-3.5 w-3.5" :stroke-width="1.75" />
                  </button>

                  <!-- Delete (placeholder — sama seperti sebelumnya) -->
                  <button @click.stop
                          class="p-1.5 rounded-md hover:bg-red-50 text-ink-muted hover:text-red-500"
                          title="Hapus">
                    <Trash2 class="h-3.5 w-3.5" :stroke-width="1.75" />
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!list.length && !listLoading">
              <td colspan="9" class="px-5 py-16 text-center">
                <p class="text-ink-subtle text-sm">No measurements match the current filters.</p>
                <button v-if="hasActiveFilters" @click="store.clearFilters()" class="text-primary-600 text-sm mt-2">
                  Clear filters
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between px-5 py-3 border-t border-line bg-white">
        <p class="text-xs text-ink-subtle">
          Showing <span class="font-mono text-ink font-semibold">{{ Math.min((listPage - 1) * listPerPage + 1, listTotal) }}–{{ Math.min(listPage * listPerPage, listTotal) }}</span>
          of <span class="font-mono text-ink font-semibold">{{ listTotal }}</span>
        </p>
        <div class="flex items-center gap-1">
          <button @click="listPage > 1 && store.setPage(listPage - 1)" :disabled="listPage === 1"
                  class="p-1.5 rounded-md text-ink-muted hover:bg-surface-muted disabled:opacity-40 disabled:cursor-not-allowed">
            <ChevronLeft class="h-4 w-4" :stroke-width="1.75" />
          </button>
          <span class="font-mono text-xs text-ink-muted px-2">{{ listPage }} / {{ totalPages || 1 }}</span>
          <button @click="listPage < totalPages && store.setPage(listPage + 1)" :disabled="listPage >= totalPages"
                  class="p-1.5 rounded-md text-ink-muted hover:bg-surface-muted disabled:opacity-40 disabled:cursor-not-allowed">
            <ChevronRight class="h-4 w-4" :stroke-width="1.75" />
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Location Modal — render di luar tabel via Teleport -->
    <EditLocationModal
      v-model="editLocOpen"
      :measurement="editLocMeasurement"
      @saved="onLocationSaved"
    />
  </div>
</template>
