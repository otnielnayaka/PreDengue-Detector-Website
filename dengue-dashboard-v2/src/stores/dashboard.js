import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { dashboardApi } from '@/services/api'

/**
 * ============================================================================
 *  Dashboard Store — KPI summary data
 * ============================================================================
 *
 *  Why Pinia instead of per-page composable:
 *    - Single fetch shared across pages
 *    - No race conditions on navigation
 *    - Cached data when re-mounting
 *
 *  Response shape from backend:
 *    {
 *      devices: { total, online, offline },
 *      measurements: {
 *        total_today, positives_today, positives_total,
 *        by_status_today: { negative, positive, inconclusive, ... }
 *      },
 *      latest_measurement: { ... }
 *    }
 * ============================================================================
 */
export const useDashboardStore = defineStore('dashboard', () => {
  // --- Raw state ---
  const summary = ref(null)
  const error   = ref(null)
  const loading = ref(false)
  const lastUpdated = ref(null)

  // --- Polling control ---
  let pollTimer = null

  // --- Computed KPIs (defensive: always return numbers, never undefined) ---
  const kpis = computed(() => {
    const s = summary.value || {}
    const m = s.measurements || {}
    const bs = m.by_status_today || {}
    const d = s.devices || {}

    return {
      positiveToday: Number(m.positives_today ?? bs.positive ?? 0),
      negativeToday: Number(bs.negative ?? 0),
      warningToday:  Number(bs.warning ?? 0),
      inconclusiveToday: Number(bs.inconclusive ?? 0),
      totalToday:    Number(m.total_today ?? 0),
      positivesTotal: Number(m.positives_total ?? 0),
      activeDevices: Number(d.online ?? 0),
      totalDevices:  Number(d.total ?? 0),
    }
  })

  const hasData = computed(() => summary.value !== null)

  // --- Actions ---
  async function fetch() {
    if (summary.value === null) loading.value = true
    try {
      const data = await dashboardApi.summary()
      summary.value = data
      error.value = null
      lastUpdated.value = new Date()
    } catch (e) {
      error.value = e
      // Keep old data
    } finally {
      loading.value = false
    }
  }

  function startPolling(intervalMs = 5000) {
    stopPolling()
    fetch()
    pollTimer = setInterval(fetch, intervalMs)
  }

  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
  }

  return {
    summary, error, loading, lastUpdated,
    kpis, hasData,
    fetch, startPolling, stopPolling,
  }
})
