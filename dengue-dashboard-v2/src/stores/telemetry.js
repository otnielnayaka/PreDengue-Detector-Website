import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { telemetryApi } from '@/services/api'

/**
 * ============================================================================
 *  Telemetry Store — Realtime device data
 * ============================================================================
 *
 *  Polled every 2 seconds. Computes derived status indicators
 *  (battery/wifi/online) based on raw values.
 * ============================================================================
 */
export const useTelemetryStore = defineStore('telemetry', () => {
  const data        = ref(null)
  const error       = ref(null)
  const loading     = ref(false)
  const lastUpdated = ref(null)

  // Online window: 5 minutes (lenient for dev with tinker-inserted data)
  const ONLINE_WINDOW_MS = 5 * 60 * 1000

  let pollTimer = null

  // --- Computed status helpers ---
  const batteryStatus = computed(() => {
    const pct = data.value?.battery_percent
    if (pct == null) return 'default'
    if (pct < 15) return 'critical'
    if (pct < 30) return 'warning'
    return 'good'
  })

  const wifiStatus = computed(() => {
    const rssi = data.value?.wifi_rssi
    if (rssi == null) return 'default'
    if (rssi > -60) return 'good'
    if (rssi > -75) return 'warning'
    return 'critical'
  })

  const lastTimestamp = computed(() => {
    const d = data.value
    if (!d) return null
    return d.timestamp ?? d.logged_at ?? d.created_at ?? null
  })

  const isOnline = computed(() => {
    const ts = lastTimestamp.value
    if (!ts) return false
    const last = new Date(ts).getTime()
    if (isNaN(last)) return false
    return Date.now() - last < ONLINE_WINDOW_MS
  })

  // --- Defensive normalized telemetry (always returns object, never null) ---
  const telemetry = computed(() => {
    const t = data.value || {}
    return {
      battery_percent: t.battery_percent ?? null,
      battery_voltage: t.battery_voltage ?? null,
      wifi_rssi:       t.wifi_rssi ?? null,
      wifi_status:     t.wifi_status ?? '—',
      sd_status:       t.sd_status ?? '—',
      free_storage_mb: t.free_storage_mb ?? null,
      temperature_c:   t.temperature_c ?? null,
      humidity:        t.humidity ?? null,
      current_ua:      t.current_ua ?? null,
      potential_v:     t.potential_v ?? null,
      state:           t.state ?? 'unknown',
      device_id:       t.device?.device_id ?? t.device_id ?? 'POT-001',
    }
  })

  // --- Actions ---
  async function fetch(deviceId = null) {
    if (data.value === null) loading.value = true
    try {
      const result = await telemetryApi.latest(deviceId)
      data.value = result
      error.value = null
      lastUpdated.value = new Date()
    } catch (e) {
      error.value = e
    } finally {
      loading.value = false
    }
  }

  function startPolling(intervalMs = 2000) {
    stopPolling()
    fetch()
    pollTimer = setInterval(() => fetch(), intervalMs)
  }

  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
  }

  return {
    data, error, loading, lastUpdated,
    telemetry, batteryStatus, wifiStatus, isOnline, lastTimestamp,
    fetch, startPolling, stopPolling,
  }
})
