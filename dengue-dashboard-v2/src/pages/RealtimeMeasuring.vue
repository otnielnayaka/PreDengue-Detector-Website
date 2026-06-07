<script setup>
/**
 * RealtimeMeasuring — membaca scan LIVE dari backend (sumber: alat).
 * ------------------------------------------------------------------
 * Scan dimulai dari alat (joystick). Halaman ini TIDAK memulai scan;
 * ia mencari scan yang sedang berjalan lalu polling kondisinya tiap 1 dtk:
 *   - points  -> grafik tumbuh realtime
 *   - progress-> progress bar
 *   - status  -> saat 'finished', tampilkan hasil lalu ke Result
 *
 * Variabel yang dipakai template DIPERTAHANKAN namanya:
 *   livePoints, progress, currentPoint, rollingMax, isRunning,
 *   elapsed, totalPoints, submitError, stopScan, formatElapsed
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { Square, Activity, Radio, CircuitBoard, AlertCircle } from 'lucide-vue-next'

import VoltammogramChart from '@/components/charts/VoltammogramChart.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import TelemetryRow from '@/components/ui/TelemetryRow.vue'

import { useTelemetryStore } from '@/stores/telemetry'
import { useMeasurementStore } from '@/stores/measurement'
import { measurementApi } from '@/services/api'

const router = useRouter()
const telemetryStore = useTelemetryStore()
const measurementStore = useMeasurementStore()

const { telemetry, isOnline, batteryStatus, wifiStatus } = storeToRefs(telemetryStore)

const totalPoints = 160
const livePoints = ref([])      // [{ n, v, i }]
const elapsed = ref(0)
const isRunning = ref(false)
const submitError = ref(null)
const measurementId = ref(null)
const liveThreshold = ref(8.0)
const liveStatus = ref('positive')

let pollTimer = null
let elapsedTimer = null

// progress dari backend (diisi alat); fallback ke rasio titik bila belum ada
const backendProgress = ref(0)
const progress = computed(() => {
  if (backendProgress.value > 0) return backendProgress.value
  return (livePoints.value.length / totalPoints) * 100
})
const currentPoint = computed(() => livePoints.value[livePoints.value.length - 1])
const rollingMax = computed(() =>
  livePoints.value.length ? Math.max(...livePoints.value.map(p => p.i)) : 0
)

// Mulai telemetri realtime (store sudah ada polling-nya sendiri)
telemetryStore.startPolling?.()

onMounted(async () => {
  // 1) Cari scan yang sedang berjalan (dimulai dari alat)
  try {
    const active = await measurementApi.activeScan()
    if (active && active.measurement_id) {
      measurementId.value = active.measurement_id
      isRunning.value = true
      startPolling()
      startElapsed()
    } else {
      // Tidak ada scan berjalan -> tampilkan scan terakhir (mode lihat)
      isRunning.value = false
    }
  } catch (e) {
    submitError.value = e
  }
})

onUnmounted(() => stopTimers())

function startElapsed() {
  elapsedTimer = setInterval(() => { if (isRunning.value) elapsed.value += 1 }, 1000)
}

function startPolling() {
  pollTimer = setInterval(pollLive, 1000)
  pollLive()
}

async function pollLive() {
  if (!measurementId.value) return
  try {
    const d = await measurementApi.live(measurementId.value)
    if (!d) return

    // Petakan titik backend -> format chart { n, v, i }
    livePoints.value = (d.points || []).map(p => ({
      n: p.sequence_number,
      v: Number(p.voltage),
      i: Number(p.current),
    }))
    backendProgress.value = d.progress ?? 0
    if (d.threshold != null) liveThreshold.value = Number(d.threshold)
    if (d.status) liveStatus.value = d.status

    // Selesai?
    if (d.scan_status === 'finished') {
      onFinished()
    }
  } catch (e) {
    // Polling tahan-banting: error sesaat tidak menghentikan polling
    submitError.value = e
  }
}

async function onFinished() {
  isRunning.value = false
  stopTimers()
  // Segarkan store agar Data Log & KPI dashboard ikut ter-update
  try {
    await measurementStore.fetchLatest?.()
    await measurementStore.fetchList?.()
  } catch (_) { /* abaikan */ }
  // Beri jeda singkat agar user lihat grafik final, lalu ke Result
  setTimeout(() => router.push('/app/result'), 800)
}

function stopTimers() {
  if (pollTimer)    { clearInterval(pollTimer);    pollTimer = null }
  if (elapsedTimer) { clearInterval(elapsedTimer); elapsedTimer = null }
}

// Tombol Stop: hanya menghentikan tampilan polling di sisi web.
// (Alat yang mengontrol scan sebenarnya; ini tidak menghentikan alat.)
function stopScan() {
  isRunning.value = false
  stopTimers()
}

function formatElapsed(s) {
  const m = Math.floor(s / 60), sec = s % 60
  return `${m.toString().padStart(2, '0')}:${sec.toString().padStart(2, '0')}`
}
</script>

<template>
  <div class="space-y-6">
    <div class="lab-card p-6 relative overflow-hidden">
      <div v-if="isRunning"
           class="absolute top-0 left-0 right-0 h-0.5 bg-gradient-to-r from-transparent via-primary-600 to-transparent animate-scan" />

      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="relative">
            <div class="h-12 w-12 rounded-full bg-primary-50 border-2 border-primary-200 flex items-center justify-center">
              <Activity class="h-5 w-5 text-primary-600" :stroke-width="2" />
            </div>
            <span v-if="isRunning" class="absolute -top-0.5 -right-0.5 h-3 w-3 rounded-full bg-primary-600 animate-pulse-soft ring-2 ring-white" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <p class="eyebrow">Acquisition</p>
              <StatusBadge :status="isRunning ? 'scanning' : 'idle'" dot :pulse="isRunning">
                {{ isRunning ? 'Running' : 'Complete' }}
              </StatusBadge>
            </div>
            <h2 class="display-lg mt-0.5">DPV scan in progress</h2>
          </div>
        </div>

        <div class="flex items-center gap-6">
          <div>
            <p class="eyebrow">Elapsed</p>
            <p class="font-mono text-2xl font-semibold text-ink mt-0.5">{{ formatElapsed(elapsed) }}</p>
          </div>
          <div class="h-10 w-px bg-line" />
          <div>
            <p class="eyebrow">Step</p>
            <p class="font-mono text-2xl font-semibold text-ink mt-0.5">{{ livePoints.length }} / {{ totalPoints }}</p>
          </div>
          <button @click="stopScan" class="btn-secondary text-sm py-2.5" :disabled="!isRunning">
            <Square class="h-3.5 w-3.5" :stroke-width="2.5" />Stop
          </button>
        </div>
      </div>

      <div v-if="submitError" class="mt-4 p-3 rounded-lg bg-primary-50 border border-primary-200 flex items-start gap-2">
        <AlertCircle class="h-4 w-4 text-primary-600 shrink-0 mt-0.5" :stroke-width="1.75" />
        <div class="text-xs">
          <p class="font-semibold text-primary-900">Gagal upload hasil ke backend</p>
          <p class="text-primary-700/80 mt-0.5">{{ submitError.message }}</p>
        </div>
      </div>

      <div class="mt-5">
        <div class="flex items-center justify-between mb-1.5">
          <p class="text-2xs uppercase tracking-[0.12em] text-ink-subtle font-semibold">Scan progress</p>
          <p class="font-mono text-2xs text-ink font-semibold">{{ progress.toFixed(1) }}%</p>
        </div>
        <div class="h-1.5 w-full bg-surface-muted rounded-full overflow-hidden">
          <div class="h-full bg-primary-600 transition-all duration-200" :style="{ width: progress + '%' }" />
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      <div class="xl:col-span-2 lab-card overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-line">
          <div>
            <p class="eyebrow">Live Voltammogram</p>
            <p class="display-md mt-0.5">DPV · {{ totalPoints }} points</p>
          </div>
          <div class="flex items-center gap-2">
            <Radio class="h-3.5 w-3.5 text-primary-600 animate-pulse-soft" :stroke-width="2" />
            <span class="font-mono text-2xs uppercase tracking-[0.12em] text-ink-subtle font-semibold">streaming</span>
          </div>
        </div>

        <div class="p-5 grid-paper">
          <VoltammogramChart :points="livePoints" :height="380" :threshold="8.0" :live="isRunning" result-status="positive" />
        </div>

        <div class="grid grid-cols-3 border-t border-line">
          <div class="px-5 py-4 border-r border-line">
            <p class="eyebrow">Live Potential</p>
            <p class="font-mono text-xl font-semibold text-ink mt-1">
              {{ currentPoint ? currentPoint.v.toFixed(4) : '0.0000' }}<span class="text-xs text-ink-faint ml-1">V</span>
            </p>
          </div>
          <div class="px-5 py-4 border-r border-line">
            <p class="eyebrow">Live Current</p>
            <p class="font-mono text-xl font-semibold text-ink mt-1">
              {{ currentPoint ? currentPoint.i.toFixed(4) : '0.0000' }}<span class="text-xs text-ink-faint ml-1">µA</span>
            </p>
          </div>
          <div class="px-5 py-4">
            <p class="eyebrow">Rolling Max</p>
            <p class="font-mono text-xl font-semibold text-primary-600 mt-1">
              {{ rollingMax.toFixed(3) }}<span class="text-xs text-ink-faint ml-1">µA</span>
            </p>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <div class="lab-card">
          <div class="px-5 py-4 border-b border-line">
            <div class="flex items-center justify-between">
              <p class="eyebrow">Hardware Telemetry</p>
              <StatusBadge :status="isOnline ? 'online' : 'offline'" dot :pulse="isOnline">
                {{ isOnline ? 'Live' : 'Offline' }}
              </StatusBadge>
            </div>
          </div>
          <div class="px-5 py-2">
            <TelemetryRow label="WiFi RSSI" :value="telemetry.wifi_rssi" unit="dBm" :status="wifiStatus" />
            <TelemetryRow label="Battery"
                          :value="telemetry.battery_percent !== null ? telemetry.battery_percent + '%' : null"
                          :status="batteryStatus" />
            <TelemetryRow label="Temperature" :value="telemetry.temperature_c" unit="°C" />
            <TelemetryRow label="Current (TIA)" :value="telemetry.current_ua" unit="µA" />
            <TelemetryRow label="Potential (DAC)" :value="telemetry.potential_v" unit="V" />
            <TelemetryRow label="State" :value="telemetry.state" />
          </div>
        </div>

        <div class="lab-card p-5">
          <div class="flex items-center gap-2 mb-4">
            <CircuitBoard class="h-4 w-4 text-primary-600" :stroke-width="1.75" />
            <p class="eyebrow">Scan Parameters</p>
          </div>
          <dl class="space-y-2.5 text-sm">
            <div class="flex justify-between"><dt class="text-ink-subtle">Method</dt><dd class="font-mono text-ink">DPV</dd></div>
            <div class="flex justify-between"><dt class="text-ink-subtle">Range</dt><dd class="font-mono text-ink">-0.2 → 0.6 V</dd></div>
            <div class="flex justify-between"><dt class="text-ink-subtle">Step</dt><dd class="font-mono text-ink">5 mV</dd></div>
            <div class="flex justify-between"><dt class="text-ink-subtle">Scan rate</dt><dd class="font-mono text-ink">50 mV/s</dd></div>
            <div class="flex justify-between"><dt class="text-ink-subtle">Pulse</dt><dd class="font-mono text-ink">25 mV</dd></div>
          </dl>
        </div>
      </div>
    </div>
  </div>
</template>
