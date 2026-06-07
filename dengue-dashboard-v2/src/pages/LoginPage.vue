<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const showPassword = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.message
      || e.response?.data?.errors?.email?.[0]
      || 'Login gagal. Periksa email & password.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-600 to-primary-800 px-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">

      <!-- Header -->
      <div class="bg-gradient-to-br from-primary-600 to-primary-700 px-8 py-8 text-center">
        <div class="mx-auto h-16 w-16 rounded-2xl bg-white flex items-center justify-center shadow-md mb-4">
          <svg viewBox="0 0 24 24" class="h-8 w-8 text-primary-600" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 12 L7 12 L9 8 L11 16 L13 12 L21 12" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-white tracking-tight">PreDengue</h1>
        <p class="text-sm text-white/80 mt-1">Sistem Deteksi Dini NS1 — Potentiostat IoT</p>
      </div>

      <!-- Form -->
      <div class="px-8 py-8">
        <div class="space-y-5">
          <div>
            <label class="block text-sm font-semibold text-ink mb-1.5">Email</label>
            <input
              v-model="email"
              type="email"
              placeholder="admin@dengue.test"
              class="w-full px-4 py-2.5 rounded-lg border border-line focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
              @keyup.enter="handleLogin"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-ink mb-1.5">Password</label>
            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                class="w-full px-4 py-2.5 rounded-lg border border-line focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm pr-12"
                @keyup.enter="handleLogin"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-2xs font-semibold text-ink-subtle hover:text-primary-600"
              >
                {{ showPassword ? 'SEMBUNYI' : 'LIHAT' }}
              </button>
            </div>
          </div>

          <p v-if="error" class="text-sm text-primary-600 bg-primary-50 border border-primary-200 rounded-lg px-3 py-2">
            {{ error }}
          </p>

          <button
            @click="handleLogin"
            :disabled="loading"
            class="w-full py-3 rounded-lg bg-primary-600 hover:bg-primary-700 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold text-sm transition-colors"
          >
            {{ loading ? 'Memproses...' : 'Masuk' }}
          </button>
        </div>

        <p class="text-center text-2xs text-ink-faint mt-6">
          Tugas Akhir — IoT Monitoring Dengue
        </p>
      </div>
    </div>
  </div>
</template>
