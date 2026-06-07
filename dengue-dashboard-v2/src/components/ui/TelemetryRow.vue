<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number, null], default: null },
  unit:  { type: String, default: '' },
  status: { type: String, default: 'default' },
})

const dotColor = {
  default:  'bg-ink-faint',
  good:     'bg-emerald-500',
  warning:  'bg-amber-500',
  critical: 'bg-primary-500',
}
</script>

<template>
  <div class="flex items-center justify-between py-2.5 border-b border-line-subtle last:border-0">
    <div class="flex items-center gap-2.5 min-w-0">
      <span class="h-1.5 w-1.5 rounded-full shrink-0" :class="dotColor[status]" />
      <span class="text-sm text-ink-muted truncate">{{ label }}</span>
    </div>
    <div class="flex items-baseline gap-1 shrink-0">
      <span class="font-mono text-sm font-semibold text-ink">{{ value ?? '—' }}</span>
      <span v-if="unit" class="text-2xs text-ink-faint">{{ unit }}</span>
    </div>
  </div>
</template>
