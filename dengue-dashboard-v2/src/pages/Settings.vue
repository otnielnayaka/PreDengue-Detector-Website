<script setup>
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import {
  Wifi, Clock, Beaker, Target, Tag, Cpu, ChevronRight,
  Check, AlertCircle,
} from 'lucide-vue-next'

import { useTelemetryStore } from '@/stores/telemetry'
import { useClock } from '@/composables/useClock'

const telemetryStore = useTelemetryStore()
const { telemetry, wifiStatus } = storeToRefs(telemetryStore)
const { now } = useClock()

// Sumber tunggal untuk info hardware/firmware — dipakai di daftar settings
// kiri (item "Firmware Version") dan panel "Device Information" kanan,
// supaya keduanya selalu konsisten.
const HARDWARE_LABEL = 'Airlift ESP 32'
const FIRMWARE_VERSION = '1'

const settings = computed(() => [
  {
    icon: Wifi, label: 'WiFi Network', desc: 'Koneksi jaringan device ke server',
    value: telemetry.value.wifi_status ?? 'Unknown',
    meta: telemetry.value.wifi_rssi ? `${telemetry.value.wifi_rssi} dBm` : 'Tidak ada data',
    status: wifiStatus.value === 'good' ? 'good' : 'warning',
  },
  {
    icon: Clock, label: 'Real-Time Clock', desc: 'Waktu sistem dan sinkronisasi NTP',
    value: now.value.toLocaleString('id-ID', {
      year: 'numeric', month: '2-digit', day: '2-digit',
      hour: '2-digit', minute: '2-digit', second: '2-digit',
    }),
    meta: 'WIB · synced via NTP', status: 'good',
  },
  {
    icon: Beaker, label: 'Calibration', desc: 'TIA gain dan DAC reference',
    value: 'Calibrated', meta: 'Last: 12 May 2026', status: 'good',
  },
  {
    icon: Target, label: 'Detection Threshold', desc: 'Threshold peak current untuk NS1',
    value: '8.000 µA', meta: 'Adjustable per assay', status: 'good',
  },
  {
    icon: Tag, label: 'Device ID', desc: 'Hardware identifier unik',
    value: telemetry.value.device_id, meta: 'Read-only', status: 'good',
  },
  {
    icon: Cpu, label: 'Firmware Version', desc: 'Versi firmware di Feather M4',
    value: FIRMWARE_VERSION, meta: 'Up to date', status: 'good',
  },
])

const statusStyles = {
  good:    { text: 'text-emerald-700', icon: Check },
  warning: { text: 'text-amber-700',   icon: AlertCircle },
}
</script>

<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-3">
      <button v-for="(s, idx) in settings" :key="s.label"
              class="lab-card p-5 flex items-center gap-5 hover:shadow-card-hover hover:border-line-strong
                     transition-all cursor-pointer group w-full text-left">
        <div class="h-11 w-11 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center shrink-0">
          <component :is="s.icon" class="h-5 w-5 text-primary-600" :stroke-width="1.75" />
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <p class="font-semibold text-ink">{{ s.label }}</p>
            <span class="font-mono text-2xs text-ink-faint">0{{ idx + 1 }}</span>
          </div>
          <p class="text-xs text-ink-subtle mt-0.5">{{ s.desc }}</p>
        </div>

        <div class="text-right shrink-0">
          <p class="font-mono text-sm font-semibold text-ink">{{ s.value }}</p>
          <div class="flex items-center justify-end gap-1.5 mt-1">
            <component :is="statusStyles[s.status].icon" class="h-3 w-3"
                       :class="statusStyles[s.status].text" :stroke-width="2" />
            <p class="text-2xs" :class="statusStyles[s.status].text">{{ s.meta }}</p>
          </div>
        </div>

        <ChevronRight class="h-4 w-4 text-ink-faint group-hover:text-primary-600 transition-colors shrink-0" :stroke-width="2" />
      </button>
    </div>

    <div class="space-y-6">
      <div class="lab-card p-6">
        <p class="eyebrow mb-4">Device Information</p>
        <div class="flex items-center gap-3 mb-5 pb-5 border-b border-line">
          <div class="h-12 w-12 rounded-lg bg-primary-600 flex items-center justify-center">
            <Cpu class="h-6 w-6 text-white" :stroke-width="1.75" />
          </div>
          <div>
            <p class="display-md">{{ telemetry.device_id }}</p>
            <p class="text-xs text-ink-subtle">Feather M4 + AirLift</p>
          </div>
        </div>
        <dl class="space-y-3 text-sm">
          <div class="flex justify-between"><dt class="text-ink-subtle">Hardware</dt><dd class="font-mono text-ink">{{ HARDWARE_LABEL }}</dd></div>
          <div class="flex justify-between"><dt class="text-ink-subtle">Front-end</dt><dd class="font-mono text-ink">Radiustat</dd></div>
          <div class="flex justify-between"><dt class="text-ink-subtle">Electrode</dt><dd class="font-mono text-ink">SPCE-Au</dd></div>
          <div class="flex justify-between"><dt class="text-ink-subtle">Firmware</dt><dd class="font-mono text-ink">{{ FIRMWARE_VERSION }}</dd></div>
          <div class="flex justify-between border-t border-line pt-3">
            <dt class="text-ink-subtle">Status</dt>
            <dd class="font-mono" :class="telemetryStore.isOnline ? 'text-emerald-600' : 'text-ink-faint'">
              {{ telemetryStore.isOnline ? 'Online' : 'Offline' }}
            </dd>
          </div>
        </dl>
      </div>

      <div class="rounded-xl border border-primary-200 bg-primary-50/30 p-5">
        <p class="font-semibold text-primary-900 mb-1">Danger Zone</p>
        <p class="text-xs text-primary-700/80 mb-4 leading-relaxed">
          Operasi berikut tidak dapat dibatalkan.
        </p>
        <div class="space-y-2">
          <button class="w-full text-left text-sm text-primary-700 hover:bg-primary-100 rounded-md px-3 py-2 transition-colors">
            Reset calibration to defaults
          </button>
          <button class="w-full text-left text-sm text-primary-700 hover:bg-primary-100 rounded-md px-3 py-2 transition-colors">
            Factory reset device
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
