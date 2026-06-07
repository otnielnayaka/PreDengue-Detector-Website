<script setup>
defineProps({
  status: { type: String, required: true },
  dot:    { type: Boolean, default: false },
  pulse:  { type: Boolean, default: false },
})

const styles = {
  positive:     { ring: 'border-primary-200 bg-primary-50 text-primary-700', dot: 'bg-primary-500' },
  negative:     { ring: 'border-emerald-200 bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500' },
  warning:      { ring: 'border-amber-200 bg-amber-50 text-amber-700',       dot: 'bg-amber-500' },
  invalid:      { ring: 'border-line bg-surface-muted text-ink-subtle',      dot: 'bg-ink-faint' },
  inconclusive: { ring: 'border-line bg-surface-muted text-ink-subtle',      dot: 'bg-ink-faint' },
  online:       { ring: 'border-emerald-200 bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500' },
  offline:      { ring: 'border-line bg-surface-muted text-ink-subtle',      dot: 'bg-ink-faint' },
  idle:         { ring: 'border-line bg-surface-muted text-ink-subtle',      dot: 'bg-ink-faint' },
  scanning:     { ring: 'border-primary-200 bg-primary-50 text-primary-700', dot: 'bg-primary-500' },
}

const styleFor = (s) => styles[s] || styles.invalid
</script>

<template>
  <span class="status-pill" :class="styleFor(status).ring">
    <span v-if="dot" class="status-pill-dot" :class="[styleFor(status).dot, pulse && 'animate-pulse-soft']" />
    <slot>{{ status }}</slot>
  </span>
</template>
