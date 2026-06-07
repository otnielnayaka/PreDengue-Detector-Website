<script setup>
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { RouterLink } from 'vue-router'
import {
  AlertCircle, CheckCircle2, Download, CloudUpload, Printer, ArrowLeft,
  Calendar, Tag, MapPin, Cpu, Activity,
} from 'lucide-vue-next'

import VoltammogramChart from '@/components/charts/VoltammogramChart.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'

import { useMeasurementStore } from '@/stores/measurement'

const measurementStore = useMeasurementStore()
const { latest, voltammogramPoints } = storeToRefs(measurementStore)

const isPositive = computed(() => latest.value?.status === 'positive')

const result = computed(() => {
  const m = latest.value
  if (!m) return null
  const delta = m.peak_current - m.threshold
  return {
    status: m.status,
    label: isPositive.value ? 'NS1 DETECTED' : 'NO NS1 DETECTED',
    sub: isPositive.value
      ? 'Antigen NS1 ditemukan di atas threshold deteksi'
      : 'Sinyal di bawah threshold deteksi NS1',
    peakCurrent: m.peak_current,
    peakVoltage: m.peak_voltage,
    threshold: m.threshold,
    method: m.method,
    timestamp: m.created_at,
    sampleId: m.sample_id,
    device: m.device?.device_id ?? '—',
    location: m.location ? `${m.location.kecamatan} / ${m.location.desa ?? '—'}` : '—',
    delta: (delta >= 0 ? '+' : '') + delta.toFixed(3) + ' µA',
  }
})

const formattedTime = computed(() => {
  if (!result.value?.timestamp) return ''
  return new Date(result.value.timestamp).toLocaleString('en-GB', {
    weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
})
</script>

<template>
  <div class="space-y-6">
    <div v-if="!result" class="lab-card p-12 text-center">
      <Activity class="h-10 w-10 text-ink-faint mx-auto mb-3" :stroke-width="1.5" />
      <p class="display-md text-ink-muted">Belum ada hasil scan</p>
      <p class="text-sm text-ink-subtle mt-1">Hasil akan muncul setelah scan pertama.</p>
    </div>

    <template v-else>
      <!-- HERO RESULT -->
      <div class="lab-card overflow-hidden relative"
           :class="isPositive ? 'border-primary-200' : 'border-emerald-200'">
        <div class="absolute left-0 top-0 bottom-0 w-1" :class="isPositive ? 'bg-primary-600' : 'bg-emerald-500'" />
        <div class="p-8 lg:p-10">
          <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
            <div class="flex items-start gap-5">
              <div class="h-16 w-16 rounded-2xl flex items-center justify-center shrink-0"
                   :class="isPositive ? 'bg-primary-50 border border-primary-200' : 'bg-emerald-50 border border-emerald-200'">
                <component :is="isPositive ? AlertCircle : CheckCircle2"
                           class="h-8 w-8"
                           :class="isPositive ? 'text-primary-600' : 'text-emerald-600'"
                           :stroke-width="1.75" />
              </div>
              <div>
                <p class="eyebrow" :class="isPositive ? 'text-primary-600' : 'text-emerald-600'">Result</p>
                <h1 class="display-xl mt-1"
                    :class="isPositive ? 'text-primary-700' : 'text-emerald-700'">
                  {{ result.label }}
                </h1>
                <p class="text-ink-subtle mt-3 text-base">{{ result.sub }}</p>
                <p class="font-mono text-xs text-ink-faint mt-1">{{ formattedTime }}</p>
              </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <button class="btn-secondary text-sm"><Printer class="h-3.5 w-3.5" :stroke-width="1.75" />Print</button>
              <button class="btn-secondary text-sm"><Download class="h-3.5 w-3.5" :stroke-width="1.75" />Export</button>
              <button class="btn-primary text-sm"><CloudUpload class="h-3.5 w-3.5" :stroke-width="1.75" />Sync Cloud</button>
            </div>
          </div>

          <div class="mt-8 pt-6 border-t" :class="isPositive ? 'border-primary-100' : 'border-emerald-100'">
            <div class="flex items-center justify-between">
              <p class="eyebrow">vs. Threshold</p>
              <p class="font-mono text-lg font-semibold" :class="isPositive ? 'text-primary-600' : 'text-emerald-600'">
                {{ result.delta }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="space-y-6">
          <div class="lab-card p-6">
            <p class="eyebrow mb-4">Measurement Metrics</p>
            <dl class="space-y-4">
              <div>
                <dt class="text-xs text-ink-subtle">Peak Current</dt>
                <dd class="font-mono text-2xl font-semibold text-ink mt-0.5">
                  {{ result.peakCurrent.toFixed(3) }}<span class="text-sm text-ink-faint ml-1">µA</span>
                </dd>
              </div>
              <div class="border-t border-line pt-4">
                <dt class="text-xs text-ink-subtle">Peak Potential</dt>
                <dd class="font-mono text-2xl font-semibold text-ink mt-0.5">
                  {{ result.peakVoltage.toFixed(4) }}<span class="text-sm text-ink-faint ml-1">V</span>
                </dd>
              </div>
              <div class="border-t border-line pt-4">
                <dt class="text-xs text-ink-subtle">Threshold</dt>
                <dd class="font-mono text-2xl font-semibold text-ink mt-0.5">
                  {{ result.threshold.toFixed(4) }}<span class="text-sm text-ink-faint ml-1">µA</span>
                </dd>
              </div>
            </dl>
          </div>

          <div class="lab-card p-6">
            <p class="eyebrow mb-4">Acquisition Metadata</p>
            <dl class="space-y-3 text-sm">
              <div class="flex items-center justify-between">
                <dt class="text-ink-subtle inline-flex items-center gap-2"><Tag class="h-3.5 w-3.5" :stroke-width="1.75" />Sample</dt>
                <dd class="font-mono text-ink">{{ result.sampleId }}</dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-ink-subtle inline-flex items-center gap-2"><Activity class="h-3.5 w-3.5" :stroke-width="1.75" />Method</dt>
                <dd class="font-mono text-ink">{{ result.method }}</dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-ink-subtle inline-flex items-center gap-2"><Cpu class="h-3.5 w-3.5" :stroke-width="1.75" />Device</dt>
                <dd class="font-mono text-ink">{{ result.device }}</dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-ink-subtle inline-flex items-center gap-2"><MapPin class="h-3.5 w-3.5" :stroke-width="1.75" />Location</dt>
                <dd class="font-mono text-ink text-xs">{{ result.location }}</dd>
              </div>
              <div class="flex items-center justify-between border-t border-line pt-3 mt-3">
                <dt class="text-ink-subtle">Confidence</dt>
                <dd><StatusBadge :status="isPositive ? 'positive' : 'negative'" dot>High</StatusBadge></dd>
              </div>
            </dl>
          </div>
        </div>

        <div class="xl:col-span-2 lab-card overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-line">
            <div>
              <p class="eyebrow">Voltammogram</p>
              <p class="display-md mt-0.5">{{ result.sampleId }}</p>
            </div>
            <StatusBadge :status="result.status" dot>
              {{ isPositive ? 'NS1 DETECTED' : 'NO NS1' }}
            </StatusBadge>
          </div>

          <div class="p-5 grid-paper">
            <VoltammogramChart :points="voltammogramPoints" :height="420"
                               :threshold="result.threshold" :result-status="result.status" />
          </div>
        </div>
      </div>

      <RouterLink to="/app/data-log" class="inline-flex items-center gap-2 text-sm text-ink-subtle hover:text-ink transition-colors">
        <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2" />Back to Data Log
      </RouterLink>
    </template>
  </div>
</template>
