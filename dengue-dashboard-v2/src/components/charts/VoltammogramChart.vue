<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed, nextTick } from 'vue'
import * as echarts from 'echarts/core'
import { LineChart } from 'echarts/charts'
import {
  GridComponent, TooltipComponent, MarkPointComponent,
  MarkLineComponent, TitleComponent,
} from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
import { Activity } from 'lucide-vue-next'

echarts.use([
  LineChart,
  GridComponent, TooltipComponent, MarkPointComponent,
  MarkLineComponent, TitleComponent,
  CanvasRenderer,
])

/**
 * VoltammogramChart — Production-safe ECharts wrapper.
 *
 * Defensive features:
 *   - Never crashes on null/undefined data
 *   - Shows graceful empty state when no points
 *   - Properly disposes chart instance on unmount
 *   - Handles resize via ResizeObserver
 *   - Animation can be disabled for live streaming (no flicker)
 *   - Re-initializes if disposed accidentally
 */
const props = defineProps({
  points:       { type: Array,   default: () => [] },
  threshold:    { type: [Number, null], default: null },
  resultStatus: { type: String,  default: null },
  height:       { type: Number,  default: 360 },
  live:         { type: Boolean, default: false },
  showPeak:     { type: Boolean, default: true },
  // 'DPV' (default) mempertahankan tampilan lama persis. 'CV' mematikan
  // markPoint "PEAK" tunggal & garis threshold — keduanya konsep DPV
  // (satu peak vs. threshold pass/fail) yang tidak berlaku untuk kurva
  // loop CV — dan menambah cycle/direction/time di tooltip kalau tersedia.
  method:       { type: String,  default: 'DPV' },
})

const chartEl = ref(null)
let chart = null
let resizeObserver = null

const lineColor = computed(() => {
  switch (props.resultStatus) {
    case 'positive': return '#DC2626'
    case 'negative': return '#10B981'
    case 'warning':  return '#F59E0B'
    default:         return '#DC2626'
  }
})

const areaColor = computed(() => {
  switch (props.resultStatus) {
    case 'positive': return ['rgba(220, 38, 38, 0.14)', 'rgba(220, 38, 38, 0)']
    case 'negative': return ['rgba(16, 185, 129, 0.12)', 'rgba(16, 185, 129, 0)']
    case 'warning':  return ['rgba(245, 158, 11, 0.12)', 'rgba(245, 158, 11, 0)']
    default:         return ['rgba(220, 38, 38, 0.12)', 'rgba(220, 38, 38, 0)']
  }
})

const hasPoints = computed(() =>
  Array.isArray(props.points) && props.points.length > 0
)

const isCv = computed(() => props.method === 'CV')

function toChartData(points) {
  if (!Array.isArray(points)) return []
  return points
    .map(p => {
      // Support multiple input shapes: {v,i}, {voltage,current}, {x,y}
      const v = p.v ?? p.voltage ?? p.x
      const i = p.i ?? p.current ?? p.y
      if (v == null || i == null || isNaN(v) || isNaN(i)) return null
      // Array TIDAK di-sort — urutan input = urutan akuisisi. Untuk CV ini
      // penting supaya sapuan maju/balik (yang bisa berbagi voltage sama)
      // tetap membentuk loop yang benar, bukan garis lurus hasil sorting.
      const item = { value: [Number(v), Number(i)] }
      if (p.cycle != null) item.cycle = p.cycle
      if (p.direction != null) item.direction = p.direction
      const t = p.t ?? p.time
      if (t != null) item.time = Number(t)
      return item
    })
    .filter(Boolean)
}

function findPeak(data) {
  if (!data.length) return null
  let maxIdx = 0
  for (let i = 1; i < data.length; i++) {
    if (data[i].value[1] > data[maxIdx].value[1]) maxIdx = i
  }
  return data[maxIdx]
}

function buildOption(data) {
  // DPV: markPoint "PEAK" tunggal seperti sebelumnya. CV: kurva bisa punya
  // beberapa naik-turun (loop) — highlight satu titik tertinggi menyesatkan,
  // jadi dimatikan untuk CV.
  const peak = props.showPeak && !isCv.value ? findPeak(data) : null

  return {
    animation: !props.live,         // disable animation in live mode for smoothness
    animationDuration: 400,
    animationEasing: 'cubicOut',
    grid: { top: 30, right: 20, bottom: 48, left: 60, containLabel: false },
    tooltip: {
      trigger: 'axis',
      backgroundColor: '#FFFFFF',
      borderColor: '#E2E8F0',
      borderWidth: 1,
      padding: [8, 12],
      textStyle: { color: '#0F172A', fontFamily: 'Inter', fontSize: 12 },
      extraCssText: 'box-shadow: 0 4px 12px rgba(15,23,42,0.08); border-radius: 8px;',
      formatter: (params) => {
        if (!params || !params.length) return ''
        const p = params[0]
        if (!p.value) return ''
        const extra = p.data || {}
        const extraRows = [
          extra.time != null ? `<div><span style="color:#64748B">t</span> ${extra.time.toFixed(2)} s</div>` : '',
          extra.cycle != null ? `<div><span style="color:#64748B">Cycle</span> ${extra.cycle}</div>` : '',
          extra.direction != null ? `<div><span style="color:#64748B">Dir</span> ${extra.direction}</div>` : '',
        ].join('')
        return `<div style="font-family: 'JetBrains Mono', monospace; font-size: 11px;">
          <div style="color:#64748B; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.08em; font-family:Inter; font-size:10px;">Point ${p.dataIndex}</div>
          <div><span style="color:#64748B">V</span> ${Number(p.value[0]).toFixed(4)} V</div>
          <div><span style="color:#64748B">I</span> ${Number(p.value[1]).toFixed(4)} µA</div>
          ${extraRows}
        </div>`
      },
      axisPointer: {
        type: 'cross',
        lineStyle: { color: '#94A3B8', width: 1, type: 'dashed' },
        crossStyle: { color: '#94A3B8', width: 1 },
        label: {
          backgroundColor: '#0F172A', color: '#FFFFFF',
          fontFamily: 'JetBrains Mono', fontSize: 10, padding: [4, 8],
          formatter: ({ value }) => typeof value === 'number' ? value.toFixed(3) : value,
        },
      },
    },
    xAxis: {
      type: 'value',
      name: 'Potential (V)',
      nameLocation: 'middle',
      nameGap: 30,
      nameTextStyle: { color: '#64748B', fontFamily: 'Inter', fontSize: 11, fontWeight: 500 },
      axisLine: { lineStyle: { color: '#CBD5E1' } },
      axisTick: { lineStyle: { color: '#CBD5E1' }, length: 4 },
      splitLine: { show: true, lineStyle: { color: '#F1F5F9' } },
      axisLabel: { color: '#64748B', fontFamily: 'JetBrains Mono', fontSize: 10,
                   formatter: (v) => v.toFixed(2) },
    },
    yAxis: {
      type: 'value',
      name: 'Current (µA)',
      nameLocation: 'middle',
      nameGap: 46,
      nameRotate: 90,
      nameTextStyle: { color: '#64748B', fontFamily: 'Inter', fontSize: 11, fontWeight: 500 },
      axisLine: { show: false },
      axisTick: { show: false },
      splitLine: { show: true, lineStyle: { color: '#F1F5F9' } },
      axisLabel: { color: '#64748B', fontFamily: 'JetBrains Mono', fontSize: 10,
                   formatter: (v) => v.toFixed(1) },
    },
    series: [
      {
        name: 'Voltammogram',
        type: 'line',
        smooth: 0.3,
        showSymbol: false,
        sampling: 'lttb',
        lineStyle: { color: lineColor.value, width: 2 },
        itemStyle: { color: lineColor.value },
        areaStyle: {
          color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            { offset: 0, color: areaColor.value[0] },
            { offset: 1, color: areaColor.value[1] },
          ]),
        },
        emphasis: { focus: 'series' },
        markPoint: peak ? {
          symbol: 'pin',
          symbolSize: 36,
          itemStyle: { color: lineColor.value, borderColor: '#FFFFFF', borderWidth: 2 },
          label: {
            color: '#FFFFFF', fontFamily: 'JetBrains Mono', fontSize: 9,
            fontWeight: 600, formatter: 'PEAK',
          },
          data: [{ coord: peak.value }],
        } : undefined,
        // Threshold DPV = garis pass/fail tervalidasi. CV belum punya
        // threshold yang sama artinya (lihat aturan diagnosis di
        // ResultDetection.vue) — jangan tampilkan supaya tidak menyesatkan.
        markLine: props.threshold !== null && !isNaN(props.threshold) && !isCv.value ? {
          silent: true, symbol: 'none',
          lineStyle: { color: '#94A3B8', type: 'dashed', width: 1 },
          label: {
            position: 'end', color: '#64748B', fontFamily: 'Inter',
            fontSize: 10, fontWeight: 500, formatter: `Threshold ${props.threshold.toFixed(2)}`,
          },
          data: [{ yAxis: props.threshold }],
        } : undefined,
        data,
      },
    ],
  }
}

function initChart() {
  if (!chartEl.value) return
  if (chart && !chart.isDisposed()) return  // already initialized
  try {
    chart = echarts.init(chartEl.value, null, { renderer: 'canvas' })
    render()
  } catch (e) {
    console.error('[VoltammogramChart] init failed:', e)
  }
}

function render() {
  if (!chart || chart.isDisposed()) return
  const data = toChartData(props.points)
  try {
    chart.setOption(buildOption(data), { notMerge: true })
  } catch (e) {
    console.error('[VoltammogramChart] render failed:', e)
  }
}

function handleResize() {
  if (chart && !chart.isDisposed()) {
    try { chart.resize() } catch (e) { /* ignore */ }
  }
}

onMounted(() => {
  nextTick(() => {
    initChart()
    // ResizeObserver for responsive behavior
    if (chartEl.value && 'ResizeObserver' in window) {
      resizeObserver = new ResizeObserver(handleResize)
      resizeObserver.observe(chartEl.value)
    }
    window.addEventListener('resize', handleResize)
  })
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
  if (resizeObserver) {
    resizeObserver.disconnect()
    resizeObserver = null
  }
  if (chart && !chart.isDisposed()) {
    chart.dispose()
  }
  chart = null
})

watch(() => props.points, () => {
  // Re-init if chart was disposed somehow
  if (!chart || chart.isDisposed()) {
    nextTick(initChart)
  } else {
    render()
  }
}, { deep: false })

watch([() => props.threshold, () => props.resultStatus], render)
</script>

<template>
  <div class="relative w-full">
    <!-- Live scanning shimmer -->
    <div v-if="live && hasPoints"
         class="pointer-events-none absolute inset-x-0 top-0 h-full overflow-hidden rounded-lg z-10">
      <div class="absolute inset-y-0 w-32 animate-scan"
           :style="{ background: 'linear-gradient(to right, transparent, rgba(220,38,38,0.05), transparent)' }" />
    </div>

    <!-- Empty state — graceful, never blank -->
    <div v-if="!hasPoints"
         class="flex flex-col items-center justify-center w-full text-center"
         :style="{ height: height + 'px' }">
      <div class="h-12 w-12 rounded-full bg-surface-muted flex items-center justify-center mb-3">
        <Activity class="h-5 w-5 text-ink-faint" :stroke-width="1.5" />
      </div>
      <p class="text-sm font-medium text-ink-muted">No voltammogram data</p>
      <p class="text-xs text-ink-subtle mt-1 max-w-xs">
        Run a scan to populate the chart with realtime current vs. potential data.
      </p>
    </div>

    <!-- Chart container — always rendered but hidden when no data -->
    <div ref="chartEl"
         :style="{ height: height + 'px' }"
         :class="{ 'hidden': !hasPoints }" />
  </div>
</template>
