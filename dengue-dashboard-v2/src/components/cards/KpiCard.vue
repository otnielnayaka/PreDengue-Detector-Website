<script setup>
/**
 * KpiCard — Always renders, never blank.
 *
 * Defensive defaults ensure the card displays even if value is null/undefined.
 * Skeleton is only shown when explicitly requested via `loading` prop AND no data.
 */
defineProps({
  label:  { type: String, required: true },
  value:  { type: [String, Number, null], default: null },
  unit:   { type: String, default: '' },
  icon:   { type: Object, default: null },
  hint:   { type: String, default: '' },
  trend:  { type: String, default: null }, // 'up' | 'down' | null
  trendValue: { type: String, default: '' },
  status: { type: String, default: 'default' }, // 'default' | 'critical' | 'positive' | 'warning'
  loading: { type: Boolean, default: false },
})

const statusStyles = {
  default:  { ring: 'border-line',         iconBg: 'bg-surface-muted text-ink-subtle' },
  critical: { ring: 'border-primary-200',  iconBg: 'bg-primary-100 text-primary-700' },
  positive: { ring: 'border-emerald-200',  iconBg: 'bg-emerald-100 text-emerald-700' },
  warning:  { ring: 'border-amber-200',    iconBg: 'bg-amber-100 text-amber-700' },
}
</script>

<template>
  <div
    class="lab-card relative p-5 hover:shadow-card-hover transition-shadow"
    :class="statusStyles[status].ring"
  >
    <!-- Accent bar for critical state -->
    <div
      v-if="status === 'critical'"
      class="absolute left-0 top-5 bottom-5 w-0.5 bg-primary-600 rounded-r"
    />

    <div class="flex items-start justify-between mb-3">
      <p class="eyebrow">{{ label }}</p>
      <div v-if="icon" class="h-8 w-8 rounded-lg flex items-center justify-center"
           :class="statusStyles[status].iconBg">
        <component :is="icon" class="h-4 w-4" :stroke-width="1.75" />
      </div>
    </div>

    <!-- Value: skeleton only when actually loading -->
    <div v-if="loading && value === null" class="h-8 w-24 bg-line rounded animate-pulse" />
    <div v-else class="flex items-baseline gap-1">
      <span class="metric-value">{{ value ?? 0 }}</span>
      <span v-if="unit" class="metric-unit">{{ unit }}</span>
    </div>

    <!-- Hint / trend -->
    <div v-if="hint || trend" class="mt-2.5 flex items-center justify-between">
      <p v-if="hint" class="text-xs text-ink-subtle truncate">{{ hint }}</p>
      <span v-if="trend" class="text-2xs font-mono font-semibold ml-auto"
            :class="trend === 'up' ? 'text-emerald-600' : 'text-primary-600'">
        {{ trend === 'up' ? '↑' : '↓' }} {{ trendValue }}
      </span>
    </div>
  </div>
</template>
