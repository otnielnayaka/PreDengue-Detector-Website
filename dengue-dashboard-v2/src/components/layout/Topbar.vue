<script setup>
import { useRoute, RouterLink } from 'vue-router'
import { computed } from 'vue'
import { Bell, Search, CircleUser, Plus } from 'lucide-vue-next'
import { useClock } from '@/composables/useClock'

const route = useRoute()
const { now } = useClock()

const pageTitles = {
  dashboard: { title: 'Monitoring Dashboard', sub: 'Realtime overview of device telemetry and detection results' },
  measure:   { title: 'Measurement Setup',    sub: 'Configure voltammetry scan parameters before acquisition' },
  measuring: { title: 'Acquisition in Progress', sub: 'Live voltammogram and telemetry stream' },
  result:    { title: 'Detection Result',     sub: 'Quantitative analysis of the most recent scan' },
  'data-log':{ title: 'Data Log',             sub: 'Historical measurements across all devices and locations' },
  settings:  { title: 'Device Settings',      sub: 'Hardware configuration and calibration' },
}

const current = computed(() => pageTitles[route.name] || { title: 'Dashboard', sub: '' })

const formattedTime = computed(() => {
  return now.value.toLocaleString('en-GB', {
    weekday: 'short', day: '2-digit', month: 'short',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  })
})
</script>

<template>
  <header class="h-16 bg-white border-b border-line flex items-center px-6 lg:px-8 sticky top-0 z-30">
    <div class="flex-1 min-w-0">
      <div class="flex items-baseline gap-3">
        <h1 class="display-lg truncate">{{ current.title }}</h1>
        <span class="hidden md:inline text-2xs text-ink-faint font-mono uppercase tracking-wider">
          {{ formattedTime }}
        </span>
      </div>
      <p class="hidden md:block text-xs text-ink-subtle mt-0.5 truncate">{{ current.sub }}</p>
    </div>

    <div class="flex items-center gap-2 shrink-0">
      <div class="hidden md:flex items-center relative">
        <Search class="absolute left-3 h-4 w-4 text-ink-faint" :stroke-width="1.75" />
        <input
          type="text"
          placeholder="Search samples, devices..."
          class="w-64 rounded-lg border border-line bg-surface-muted py-1.5 pl-9 pr-3 text-sm
                 text-ink placeholder:text-ink-faint focus:bg-white focus:border-primary-500
                 focus:ring-2 focus:ring-primary-100 focus:outline-none transition-all"
        />
      </div>

      <button class="btn-ghost px-2.5 py-2.5 relative">
        <Bell class="h-4 w-4" :stroke-width="1.75" />
        <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-primary-600 ring-2 ring-white" />
      </button>

      <div class="h-6 w-px bg-line mx-1" />

      <RouterLink to="/app/measure" class="btn-primary text-sm py-2">
        <Plus class="h-4 w-4" :stroke-width="2.25" />
        <span class="hidden md:inline">New Scan</span>
      </RouterLink>

      <button class="ml-1 h-9 w-9 rounded-full bg-surface-muted border border-line flex items-center justify-center hover:bg-line transition-colors">
        <CircleUser class="h-4 w-4 text-ink-muted" :stroke-width="1.75" />
      </button>
    </div>
  </header>
</template>
