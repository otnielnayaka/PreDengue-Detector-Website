<script setup>
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { computed } from 'vue'
import {
  LayoutGrid, Activity, CircleDot, FileCheck2, Database, MapPin,
  Settings as SettingsIcon, ChevronRight,
} from 'lucide-vue-next'
import { useTelemetryStore } from '@/stores/telemetry'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const telemetryStore = useTelemetryStore()
const auth = useAuthStore()

// Tiap item bisa punya 'roles'. Tanpa 'roles' = semua role boleh lihat.
const allSections = [
  {
    label: 'Workspace',
    items: [
      { to: '/app/dashboard', label: 'Dashboard',   icon: LayoutGrid },
      { to: '/app/measure',   label: 'Measure',      icon: Activity,   roles: ['admin'] },
      { to: '/app/measuring', label: 'Acquisition',  icon: CircleDot,  roles: ['admin'] },
      { to: '/app/result',    label: 'Result',       icon: FileCheck2 },
    ],
  },
  {
    label: 'Data',
    items: [
      { to: '/app/data-log',  label: 'Data Log',        icon: Database },
      { to: '/app/map',       label: 'Map Monitoring',  icon: MapPin },
    ],
  },
  {
    label: 'System',
    items: [
      { to: '/app/settings',  label: 'Settings',  icon: SettingsIcon, roles: ['admin'] },
    ],
  },
]

// Filter berdasarkan role
const sections = computed(() =>
  allSections
    .map(section => ({
      ...section,
      items: section.items.filter(item => !item.roles || item.roles.includes(auth.role)),
    }))
    .filter(section => section.items.length > 0)
)

const isActive = (to) => route.path === to
const isOnline = computed(() => telemetryStore.isOnline)

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <aside class="hidden lg:flex lg:w-64 flex-col bg-white border-r border-line">
    <!-- ===== LOGO ===== -->
    <div class="h-16 flex items-center gap-3 px-5 border-b border-line">
      <div class="relative">
        <div class="h-9 w-9 rounded-lg bg-primary-600 flex items-center justify-center shadow-sm">
          <svg viewBox="0 0 24 24" class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12 L7 12 L9 8 L11 16 L13 12 L21 12" />
          </svg>
        </div>
        <span v-if="isOnline"
              class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white" />
      </div>

      <div class="leading-tight min-w-0">
        <p class="text-base font-bold tracking-tighter text-ink">PreDengue</p>
        <p class="text-2xs font-semibold uppercase tracking-[0.14em] text-ink-subtle mt-0.5">NS1 Detector</p>
      </div>
    </div>

    <!-- ===== ACTIVE DEVICE PILL ===== -->
    <div class="px-4 pt-5">
      <div class="rounded-lg bg-surface-muted border border-line px-3 py-2.5">
        <p class="eyebrow">Active Device</p>
        <div class="flex items-center justify-between mt-1">
          <span class="font-mono text-sm font-semibold text-ink">
            {{ telemetryStore.telemetry.device_id }}
          </span>
          <span v-if="isOnline" class="status-pill border-emerald-200 bg-emerald-50 text-emerald-700">
            <span class="status-pill-dot bg-emerald-500 animate-pulse-soft" />
            Online
          </span>
          <span v-else class="status-pill border-line bg-white text-ink-subtle">
            <span class="status-pill-dot bg-ink-faint" />
            Offline
          </span>
        </div>
      </div>
    </div>

    <!-- ===== NAVIGATION ===== -->
    <nav class="flex-1 px-3 py-5 space-y-6 overflow-y-auto">
      <div v-for="section in sections" :key="section.label">
        <p class="eyebrow px-3 mb-2">{{ section.label }}</p>
        <ul class="space-y-0.5">
          <li v-for="item in section.items" :key="item.to">
            <RouterLink
              :to="item.to"
              class="nav-item"
              :class="{ 'nav-item-active': isActive(item.to) }"
            >
              <component :is="item.icon" class="h-4 w-4 shrink-0" :stroke-width="1.75" />
              <span>{{ item.label }}</span>
              <ChevronRight v-if="isActive(item.to)" class="ml-auto h-3.5 w-3.5 text-primary-600" />
            </RouterLink>
          </li>
        </ul>
      </div>
    </nav>

    <!-- ===== FOOTER: user info + logout ===== -->
    <div class="px-5 py-4 border-t border-line space-y-3">
      <div class="flex items-center justify-between">
        <div class="leading-tight min-w-0">
          <p class="text-sm font-semibold text-ink truncate">{{ auth.user?.name }}</p>
          <p class="text-2xs font-semibold uppercase tracking-wide text-primary-600">{{ auth.role }}</p>
        </div>
        <button @click="handleLogout"
                class="text-2xs font-semibold px-2.5 py-1.5 rounded-md bg-surface-muted hover:bg-primary-50 hover:text-primary-700 text-ink-muted transition-colors">
          Logout
        </button>
      </div>
      <div class="flex items-center justify-between">
        <p class="text-2xs text-ink-faint">API</p>
        <p class="font-mono text-2xs text-ink-subtle">v1</p>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.nav-item {
  @apply flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium
         text-ink-muted hover:text-ink hover:bg-surface-muted transition-colors;
}
.nav-item-active {
  @apply bg-primary-50 text-primary-700 hover:bg-primary-50 hover:text-primary-700;
}
</style>
