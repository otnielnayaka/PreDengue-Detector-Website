import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { measurementApi } from '@/services/api'

/**
 * ============================================================================
 *  Measurement Store
 * ============================================================================
 *
 *  Two distinct functions:
 *    1. Latest measurement (polled for dashboard chart)
 *    2. List with filters & pagination (for Data Log)
 *
 *  All numeric fields are converted from string to number to prevent
 *  formatting issues. Points array is normalized to {n, v, i} for ECharts.
 * ============================================================================
 */
export const useMeasurementStore = defineStore('measurement', () => {
  // ===== LATEST MEASUREMENT =====
  const latestRaw     = ref(null)
  const latestError   = ref(null)
  const latestLoading = ref(false)
  let latestTimer = null

  /**
   * Normalize measurement data. Backend may return strings for decimals,
   * nested 'scan' object, or flat structure. We unify to one shape.
   */
  function normalizeMeasurement(m) {
    if (!m || typeof m !== 'object') return null
    const scan = m.scan || {}
    return {
      id:              m.id,
      sample_id:       m.sample_id ?? '—',
      method:          m.method ?? 'DPV',
      status:          m.status ?? 'inconclusive',
      peak_current:    Number(m.peak_current ?? 0),
      peak_voltage:    Number(m.peak_voltage ?? 0),
      threshold:       Number(m.threshold ?? 8),
      delta_tia:       Number(m.delta_tia ?? 0),
      start_voltage:   Number(m.start_voltage ?? scan.start_voltage ?? -0.2),
      end_voltage:     Number(m.end_voltage ?? scan.end_voltage ?? 0.6),
      step_voltage:    Number(m.step_voltage ?? scan.step_voltage ?? 0.005),
      scan_rate:       Number(m.scan_rate ?? scan.scan_rate ?? 0.05),
      pulse_amplitude: Number(m.pulse_amplitude ?? scan.pulse_amplitude ?? 0.025),
      duration_seconds: Number(m.duration_seconds ?? scan.duration_seconds ?? 0),
      device:          m.device ?? null,
      location:        m.location ?? null,
      created_at:      m.created_at ?? null,
      points: Array.isArray(m.points)
        ? m.points.map(p => ({
            n: Number(p.sequence_number ?? 0),
            v: Number(p.voltage ?? 0),
            i: Number(p.current ?? 0),
          }))
        : [],
    }
  }

  const latest = computed(() => normalizeMeasurement(latestRaw.value))
  const voltammogramPoints = computed(() => latest.value?.points ?? [])

  async function fetchLatest() {
    if (latestRaw.value === null) latestLoading.value = true
    try {
      const result = await measurementApi.latest()
      latestRaw.value = result
      latestError.value = null
    } catch (e) {
      latestError.value = e
    } finally {
      latestLoading.value = false
    }
  }

  function startPollingLatest(intervalMs = 5000) {
    stopPollingLatest()
    fetchLatest()
    latestTimer = setInterval(fetchLatest, intervalMs)
  }

  function stopPollingLatest() {
    if (latestTimer) { clearInterval(latestTimer); latestTimer = null }
  }

  // ===== LIST WITH FILTERS =====
  const listRaw       = ref([])
  const listTotal     = ref(0)
  const listPage      = ref(1)
  const listPerPage   = ref(10)
  const listLoading   = ref(false)
  const listError     = ref(null)
  const filters = ref({ search: '', status: 'all', method: 'all' })

  /**
   * Robust extraction from paginated response.
   * Handles:
   *   - { data: [...], meta: {...} }
   *   - [ ... ]
   *   - { data: { data: [...] } }
   */
  function extractList(response) {
    if (!response) return { items: [], total: 0 }
    if (Array.isArray(response)) {
      return { items: response, total: response.length }
    }
    if (Array.isArray(response.data)) {
      return { items: response.data, total: response.meta?.total ?? response.data.length }
    }
    if (Array.isArray(response.data?.data)) {
      return { items: response.data.data, total: response.data.meta?.total ?? response.data.data.length }
    }
    return { items: [], total: 0 }
  }

  const list = computed(() => listRaw.value.map(normalizeMeasurement).filter(Boolean))

  async function fetchList() {
    listLoading.value = true
    listError.value = null
    try {
      const params = { page: listPage.value, per_page: listPerPage.value }
      if (filters.value.search)           params.search = filters.value.search
      if (filters.value.status !== 'all') params.status = filters.value.status
      if (filters.value.method !== 'all') params.method = filters.value.method

      const response = await measurementApi.list(params)
      const { items, total } = extractList(response)
      listRaw.value = items
      listTotal.value = total
    } catch (e) {
      listError.value = e
      listRaw.value = []
      listTotal.value = 0
    } finally {
      listLoading.value = false
    }
  }

  function setPage(n) { listPage.value = n; return fetchList() }
  function setFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters }
    listPage.value = 1
    return fetchList()
  }
  function clearFilters() {
    filters.value = { search: '', status: 'all', method: 'all' }
    listPage.value = 1
    return fetchList()
  }

  // ===== SELECTED LOCATION (untuk measurement berikutnya) =====
  // Alat portable: tiap measurement diberi lokasi sampel oleh operator.
  // location_id ini dikirim bersama payload saat measurement dibuat.
  const selectedLocationId = ref(null)
  function setSelectedLocation(id) { selectedLocationId.value = id }

  return {
    // Latest
    latest, latestError, latestLoading, voltammogramPoints,
    fetchLatest, startPollingLatest, stopPollingLatest,
    // List
    list, listTotal, listPage, listPerPage, listLoading, listError, filters,
    fetchList, setPage, setFilters, clearFilters,
    // Location
    selectedLocationId, setSelectedLocation,
  }
})
