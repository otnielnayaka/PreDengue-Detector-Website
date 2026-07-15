import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import LandingPage from '@/pages/LandingPage.vue'
import LoginPage from '@/pages/LoginPage.vue'              // <-- BARU (buat file ini)
import ForbiddenPage from '@/pages/ForbiddenPage.vue'      // <-- BARU (buat file ini)
import DashboardLayout from '@/components/layout/DashboardLayout.vue'
import MonitoringDashboard from '@/pages/MonitoringDashboard.vue'
import MeasurementSetup from '@/pages/MeasurementSetup.vue'
import RealtimeMeasuring from '@/pages/RealtimeMeasuring.vue'
import ResultDetection from '@/pages/ResultDetection.vue'
import DataLog from '@/pages/DataLog.vue'
import MapView from '@/pages/MapView.vue'
import Settings from '@/pages/Settings.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // ---- PUBLIC ----
    { path: '/', name: 'landing', component: LandingPage, meta: { public: true } },
    { path: '/login', name: 'login', component: LoginPage, meta: { public: true } },
    { path: '/403', name: 'forbidden', component: ForbiddenPage, meta: { public: true } },

    // ---- APP (perlu login) ----
    {
      path: '/app',
      component: DashboardLayout,
      children: [
        { path: '',          redirect: '/app/dashboard' },

        // Semua role (admin + viewer)
        { path: 'dashboard', name: 'dashboard', component: MonitoringDashboard, meta: { roles: ['admin','viewer'] } },
        { path: 'result/:resultId?', name: 'result', component: ResultDetection, meta: { roles: ['admin','viewer'] } },
        { path: 'data-log',  name: 'data-log',  component: DataLog,             meta: { roles: ['admin','viewer'] } },
        { path: 'map',       name: 'map',       component: MapView,             meta: { roles: ['admin','viewer'] } },

        // Admin only
        { path: 'measure',   name: 'measure',   component: MeasurementSetup,    meta: { roles: ['admin'] } },
        { path: 'measuring', name: 'measuring', component: RealtimeMeasuring,   meta: { roles: ['admin'] } },
        { path: 'settings',  name: 'settings',  component: Settings,            meta: { roles: ['admin'] } },
        // { path: 'users', ... }  // UserManagement - aktifkan nanti
      ],
    },

    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
  scrollBehavior() { return { top: 0 } },
})

// ========== GUARD: cek login & role ==========
router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.public) {
    if (to.name === 'login' && auth.isLoggedIn) {
      return { name: 'dashboard' }
    }
    return true
  }

  if (!auth.isLoggedIn) {
    return { name: 'login' }
  }

  if (to.meta.roles && !to.meta.roles.includes(auth.role)) {
    return { name: 'forbidden' }
  }

  return true
})

export default router
