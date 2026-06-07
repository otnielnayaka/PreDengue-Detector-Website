<template>
  <div class="login-wrap">
    <div class="login-card">
      <!-- Header merah-putih -->
      <div class="login-header">
        <div class="logo-circle">🩸</div>
        <h1>Dengue Monitoring</h1>
        <p>Sistem Deteksi Dini DBD — Potentiostat IoT</p>
      </div>

      <form @submit.prevent="handleLogin" class="login-form">
        <div class="field">
          <label>Email</label>
          <input v-model="email" type="email" placeholder="admin@dengue.test" required />
        </div>

        <div class="field">
          <label>Password</label>
          <input v-model="password" type="password" placeholder="••••••••" required />
        </div>

        <p v-if="error" class="error-msg">{{ error }}</p>

        <button type="submit" :disabled="loading" class="login-btn">
          {{ loading ? 'Memproses...' : 'Masuk' }}
        </button>
      </form>

      <div class="login-footer">
        <p>Tugas Akhir — IoT Monitoring Dengue</p>
      </div>
    </div>
  </div>
</template>

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

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.message
      || e.response?.data?.errors?.email?.[0]
      || 'Login gagal. Cek email & password.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
  padding: 20px;
}
.login-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  width: 100%;
  max-width: 400px;
  overflow: hidden;
}
.login-header {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  color: #fff;
  padding: 32px 24px;
  text-align: center;
}
.logo-circle {
  width: 64px; height: 64px;
  background: #fff;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 32px;
  margin: 0 auto 16px;
}
.login-header h1 { margin: 0; font-size: 22px; font-weight: 700; }
.login-header p { margin: 6px 0 0; font-size: 13px; opacity: 0.9; }
.login-form { padding: 28px 24px; }
.field { margin-bottom: 18px; }
.field label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
.field input {
  width: 100%;
  padding: 12px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  box-sizing: border-box;
}
.field input:focus { outline: none; border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,0.1); }
.error-msg { color: #dc2626; font-size: 13px; margin: 0 0 14px; }
.login-btn {
  width: 100%;
  padding: 13px;
  background: #dc2626;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
}
.login-btn:hover:not(:disabled) { background: #b91c1c; }
.login-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.login-footer { text-align: center; padding: 0 24px 24px; }
.login-footer p { font-size: 12px; color: #9ca3af; margin: 0; }
</style>
