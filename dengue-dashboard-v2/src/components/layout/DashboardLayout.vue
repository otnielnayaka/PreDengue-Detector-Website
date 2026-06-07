<script setup>
import { onMounted, onUnmounted } from 'vue'
import { RouterView } from 'vue-router'
import Sidebar from './Sidebar.vue'
import Topbar from './Topbar.vue'
import { useDashboardStore } from '@/stores/dashboard'
import { useTelemetryStore } from '@/stores/telemetry'
import { useMeasurementStore } from '@/stores/measurement'

/**
 * Layout is the single place where polling starts and stops.
 * This guarantees:
 *   - Polling starts ONCE when entering /app
 *   - Polling stops cleanly when leaving /app
 *   - All children pages just READ from stores
 */
const dashboardStore = useDashboardStore()
const telemetryStore = useTelemetryStore()
const measurementStore = useMeasurementStore()

onMounted(() => {
  // Stagger intervals to avoid request bursts
  dashboardStore.startPolling(5000)      // KPI updates every 5s
  telemetryStore.startPolling(2000)      // Telemetry every 2s
  measurementStore.startPollingLatest(5000) // Latest scan every 5s
  measurementStore.fetchList()           // Initial list load (no polling)
})

onUnmounted(() => {
  dashboardStore.stopPolling()
  telemetryStore.stopPolling()
  measurementStore.stopPollingLatest()
})
</script>

<template>
  <div class="min-h-screen flex bg-surface-muted">
    <Sidebar />
    <div class="flex-1 flex flex-col min-w-0">
      <Topbar />
      <main class="flex-1 p-6 lg:p-8">
        <RouterView v-slot="{ Component }">
          <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            mode="out-in"
          >
            <component :is="Component" />
          </transition>
        </RouterView>
      </main>
    </div>
  </div>
</template>
