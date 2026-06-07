<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

function goBack() {
  router.push({ name: 'dashboard' })
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-surface-muted px-4">
    <div class="text-center">
      <div class="text-7xl font-extrabold text-primary-600">403</div>
      <h2 class="mt-2 text-xl font-bold text-ink">Akses Ditolak</h2>
      <p class="mt-2 text-sm text-ink-muted max-w-sm mx-auto">
        Halaman ini khusus untuk Admin. Akun Anda
        <span class="font-semibold text-primary-600">({{ auth.role }})</span>
        tidak memiliki izin untuk mengaksesnya.
      </p>
      <button
        @click="goBack"
        class="mt-6 inline-flex items-center px-5 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white font-semibold text-sm transition-colors"
      >
        Kembali ke Dashboard
      </button>
    </div>
  </div>
</template>
