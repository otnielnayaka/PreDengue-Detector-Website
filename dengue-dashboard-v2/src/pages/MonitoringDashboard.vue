<script setup>
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { RouterLink } from 'vue-router'
import {
  AlertCircle, Activity, Cpu, TrendingUp, Zap, ChevronRight, MapPin, WifiOff,
} from 'lucide-vue-next'

import KpiCard from '@/components/cards/KpiCard.vue'
import VoltammogramChart from '@/components/charts/VoltammogramChart.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import TelemetryRow from '@/components/ui/TelemetryRow.vue'

import { useDashboardStore } from '@/stores/dashboard'
import { useTelemetryStore } from '@/stores/telemetry'
import { useMeasurementStore } from '@/stores/measurement'

const dashboardStore = useDashboardStore()
const telemetryStore = useTelemetryStore()
const measurementStore = useMeasurementStore()

// Reactive refs from stores
const { kpis, loading: dashboardLoading, error: dashboardError } = storeToRefs(dashboardStore)
const { telemetry, isOnline, batteryStatus, wifiStatus, lastTimestamp } = storeToRefs(telemetryStore)
const { latest, voltammogramPoints, list: measurementList } = storeToRefs(measurementStore)

// Detect when backend completely unreachable
const isBackendDown = computed(() =>
  dashboardError.value?.isNetwork === true
)

// Format helpers — defensive against null
function formatTime(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString('en-GB', {
      day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
    })
  } catch { return '—' }
}

function formatLastUpdate(date) {
  if (!date) return '—'
  try {
    return new Date(date).toLocaleTimeString()
  } catch { return '—' }
}

// Computed: peak delta vs threshold for latest measurement
const peakDelta = computed(() => {
  const m = latest.value
  if (!m) return null
  return m.peak_current - m.threshold
})

// Today's results bar chart segments (defensive)
const todayBarSegments = computed(() => {
  const k = kpis.value
  const total = Math.max(k.totalToday, 1)
  return {
    positive: (k.positiveToday / total) * 100,
    negative: (k.negativeToday / total) * 100,
    warning:  (k.warningToday / total) * 100,
  }
})
</script>

<template>
  <div class="space-y-6">

    <!-- ============ CONNECTION ERROR BANNER ============ -->
    <div v-if="isBackendDown"
         class="lab-card p-4 border-primary-200 bg-primary-50/40 flex items-start gap-3">
      <WifiOff class="h-5 w-5 text-primary-600 shrink-0 mt-0.5" :stroke-width="1.75" />
      <div class="flex-1">
        <p class="font-semibold text-primary-900">Tidak bisa terhubung ke backend</p>
        <p class="text-sm text-primary-700/80 mt-0.5">
          Jalankan <span class="font-mono">php artisan serve --host=0.0.0.0 --port=8000</span> di terminal Laragon.
        </p>
      </div>
      <button @click="dashboardStore.fetch()" class="btn-secondary text-sm py-2">
        Coba lagi
      </button>
    </div>

    <!-- ============ TOP KPI ROW (always rendered, never blank) ============ -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <KpiCard
        label="Positive · 24h"
        :value="kpis.positiveToday"
        unit="cases"
        :icon="AlertCircle"
        status="critical"
        hint="Auto-flagged via threshold"
        :loading="dashboardLoading"
      />
      <KpiCard
        label="Total Samples · 24h"
        :value="kpis.totalToday"
        unit="scans"
        :icon="Activity"
        :hint="dashboardLoading ? 'Updating...' : 'Live count'"
        :loading="dashboardLoading"
      />
      <KpiCard
        label="Active Devices"
        :value="`${kpis.activeDevices}/${kpis.totalDevices}`"
        :icon="Cpu"
        status="positive"
        :hint="isOnline ? 'Telemetry stream live' : 'No recent telemetry'"
        :loading="dashboardLoading"
      />
      <KpiCard
        label="Cloud Sync"
        :value="kpis.positivesTotal"
        unit="positives"
        :icon="TrendingUp"
        status="positive"
        :hint="`Last sync ${formatLastUpdate(dashboardStore.lastUpdated)}`"
        :loading="dashboardLoading"
      />
    </div>

    <!-- ============ MAIN GRID ============ -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

      <!-- ===== LEFT: VOLTAMMOGRAM + RECENT TABLE ===== -->
      <div class="xl:col-span-2 space-y-6">

        <!-- Voltammogram card (always renders, chart shows empty state if no data) -->
        <div class="lab-card overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-line">
            <div>
              <p class="eyebrow">Latest Voltammogram</p>
              <div v-if="latest" class="flex items-baseline gap-2 mt-1">
                <h2 class="display-md">{{ latest.sample_id }}</h2>
                <span class="font-mono text-xs text-ink-subtle">· {{ latest.method }} · {{ latest.duration_seconds }}s</span>
              </div>
              <div v-else class="mt-1">
                <h2 class="display-md text-ink-faint">Awaiting first scan</h2>
              </div>
            </div>
            <StatusBadge v-if="latest" :status="latest.status" dot pulse>
              {{ latest.status === 'positive' ? 'NS1 DETECTED' : latest.status }}
            </StatusBadge>
          </div>

          <div class="p-5 grid-paper">
            <VoltammogramChart
              :points="voltammogramPoints"
              :height="340"
              :method="latest?.method"
              :threshold="latest?.threshold ?? 8"
              :result-status="latest?.status"
            />
          </div>

          <!-- Bottom metric strip - always rendered, shows — when no data -->
          <div class="grid grid-cols-2 md:grid-cols-4 border-t border-line">
            <div class="px-5 py-4 border-r border-line">
              <p class="eyebrow">Peak Current</p>
              <p class="font-mono text-lg font-semibold text-ink mt-1">
                {{ latest ? latest.peak_current.toFixed(3) : '—' }}<span class="text-2xs text-ink-faint ml-1">µA</span>
              </p>
            </div>
            <div class="px-5 py-4 border-r border-line">
              <p class="eyebrow">Peak Potential</p>
              <p class="font-mono text-lg font-semibold text-ink mt-1">
                {{ latest ? latest.peak_voltage.toFixed(4) : '—' }}<span class="text-2xs text-ink-faint ml-1">V</span>
              </p>
            </div>
            <div class="px-5 py-4 border-r border-line">
              <p class="eyebrow">Threshold</p>
              <p class="font-mono text-lg font-semibold text-ink mt-1">
                {{ latest ? latest.threshold.toFixed(3) : '—' }}<span class="text-2xs text-ink-faint ml-1">µA</span>
              </p>
            </div>
            <div class="px-5 py-4">
              <p class="eyebrow">Δ vs threshold</p>
              <p class="font-mono text-lg font-semibold mt-1"
                 :class="peakDelta === null ? 'text-ink-faint' : peakDelta > 0 ? 'text-primary-600' : 'text-emerald-600'">
                {{ peakDelta === null ? '—' : (peakDelta > 0 ? '+' : '') + peakDelta.toFixed(3) }}<span class="text-2xs ml-1">µA</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Recent measurements table -->
        <div class="lab-card overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-line">
            <div>
              <p class="eyebrow">Recent Measurements</p>
              <p class="display-md mt-0.5">Last 6 scans</p>
            </div>
            <RouterLink to="/app/data-log" class="text-sm font-medium text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
              View all <ChevronRight class="h-3.5 w-3.5" :stroke-width="2" />
            </RouterLink>
          </div>
          <table class="w-full text-sm">
            <thead class="bg-surface-muted">
              <tr class="text-left">
                <th class="px-5 py-2.5 eyebrow">Sample ID</th>
                <th class="px-5 py-2.5 eyebrow">Method</th>
                <th class="px-5 py-2.5 eyebrow">Peak I</th>
                <th class="px-5 py-2.5 eyebrow">Location</th>
                <th class="px-5 py-2.5 eyebrow">Time</th>
                <th class="px-5 py-2.5 eyebrow text-right">Result</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!measurementList.length">
                <td colspan="6" class="px-5 py-12 text-center">
                  <p class="text-sm text-ink-subtle">No measurements yet.</p>
                </td>
              </tr>
              <tr v-for="m in measurementList.slice(0, 6)" :key="m.id"
                  class="border-t border-line hover:bg-surface-muted/60 transition-colors">
                <td class="px-5 py-3 font-mono text-xs text-ink">{{ m.sample_id }}</td>
                <td class="px-5 py-3 font-mono text-xs text-ink-muted">{{ m.method }}</td>
                <td class="px-5 py-3 font-mono text-xs text-ink">
                  {{ m.peak_current.toFixed(3) }} <span class="text-ink-faint">µA</span>
                </td>
                <td class="px-5 py-3 text-ink-muted text-xs">
                  <span v-if="m.location" class="inline-flex items-center gap-1">
                    <MapPin class="h-3 w-3 text-ink-faint" :stroke-width="1.75" />
                    {{ m.location.kecamatan }}
                  </span>
                  <span v-else class="text-ink-faint">—</span>
                </td>
                <td class="px-5 py-3 font-mono text-xs text-ink-subtle">{{ formatTime(m.created_at) }}</td>
                <td class="px-5 py-3 text-right">
                  <StatusBadge :status="m.status" dot>{{ m.status }}</StatusBadge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ===== RIGHT: TELEMETRY + RESULTS ===== -->
      <div class="space-y-6">

        <!-- Device telemetry -->
        <div class="lab-card">
          <div class="px-5 py-4 border-b border-line">
            <div class="flex items-center justify-between">
              <div>
                <p class="eyebrow">Device Telemetry</p>
                <p class="display-md mt-0.5">{{ telemetry.device_id }}</p>
              </div>
              <StatusBadge :status="isOnline ? 'online' : 'offline'" dot :pulse="isOnline">
                {{ isOnline ? 'Online' : 'Offline' }}
              </StatusBadge>
            </div>
            <p class="text-2xs font-mono text-ink-faint mt-1">
              Last update {{ formatLastUpdate(telemetryStore.lastUpdated) }}
            </p>
          </div>

          <div class="px-5 py-2">
            <TelemetryRow label="WiFi Signal" :value="telemetry.wifi_rssi" unit="dBm" :status="wifiStatus" />
            <TelemetryRow label="Battery"
                          :value="telemetry.battery_percent !== null ? telemetry.battery_percent + '%' : null"
                          :unit="telemetry.battery_voltage ? `(${telemetry.battery_voltage} V)` : ''"
                          :status="batteryStatus" />
            <TelemetryRow label="SD Storage Free" :value="telemetry.free_storage_mb" unit="MB" status="good" />
            <TelemetryRow label="Temperature" :value="telemetry.temperature_c" unit="°C" />
            <TelemetryRow label="Humidity"
                          :value="telemetry.humidity !== null ? telemetry.humidity + '%' : null" />
            <TelemetryRow label="Current (TIA)" :value="telemetry.current_ua" unit="µA" />
            <TelemetryRow label="Potential (DAC)" :value="telemetry.potential_v" unit="V" />
            <TelemetryRow label="Device State" :value="telemetry.state" />
          </div>
        </div>

        <!-- Today's results -->
        <div class="lab-card p-5">
          <div class="flex items-center justify-between mb-4">
            <p class="eyebrow">Today's Results</p>
            <span class="font-mono text-2xs text-ink-faint">UTC+7</span>
          </div>

          <div class="space-y-3">
            <div class="flex items-center gap-3">
              <div class="h-2 w-2 rounded-full bg-primary-600" />
              <span class="text-sm text-ink-muted flex-1">Positive (NS1)</span>
              <span class="font-mono text-sm font-semibold text-ink">{{ kpis.positiveToday }}</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="h-2 w-2 rounded-full bg-emerald-500" />
              <span class="text-sm text-ink-muted flex-1">Negative</span>
              <span class="font-mono text-sm font-semibold text-ink">{{ kpis.negativeToday }}</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="h-2 w-2 rounded-full bg-amber-500" />
              <span class="text-sm text-ink-muted flex-1">Warning</span>
              <span class="font-mono text-sm font-semibold text-ink">{{ kpis.warningToday }}</span>
            </div>
          </div>

          <!-- Stacked bar -->
          <div class="mt-5 h-2 w-full rounded-full bg-surface-muted overflow-hidden flex">
            <div class="h-full bg-primary-600 transition-all duration-500"
                 :style="{ width: todayBarSegments.positive + '%' }" />
            <div class="h-full bg-emerald-500 transition-all duration-500"
                 :style="{ width: todayBarSegments.negative + '%' }" />
            <div class="h-full bg-amber-500 transition-all duration-500"
                 :style="{ width: todayBarSegments.warning + '%' }" />
          </div>
          <div class="flex justify-between mt-2 text-2xs text-ink-faint">
            <span>{{ kpis.totalToday }} total</span>
            <span class="font-mono" v-if="kpis.totalToday > 0">
              {{ Math.round((kpis.positiveToday / kpis.totalToday) * 100) }}% positivity
            </span>
          </div>
        </div>

        <!-- Quick action -->
        <RouterLink to="/app/measure" class="lab-card-hover p-5 block group">
          <div class="flex items-center gap-4">
            <div class="h-11 w-11 rounded-lg bg-primary-600 flex items-center justify-center group-hover:bg-primary-700 transition-colors">
              <Zap class="h-5 w-5 text-white" :stroke-width="2" />
            </div>
            <div class="flex-1">
              <p class="font-semibold text-ink">Start New Scan</p>
              <p class="text-xs text-ink-subtle mt-0.5">Configure parameters and run acquisition</p>
            </div>
            <ChevronRight class="h-4 w-4 text-ink-faint group-hover:text-primary-600 transition-colors" :stroke-width="2" />
          </div>
        </RouterLink>
      </div>
    </div>
  </div>
</template>
