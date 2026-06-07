// src/stores/auth.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  // State
  const token = ref(localStorage.getItem('token') || null)
  const user  = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  // Getters
  const isLoggedIn = computed(() => !!token.value)
  const isAdmin    = computed(() => user.value?.role === 'admin')
  const isViewer   = computed(() => user.value?.role === 'viewer')
  const role       = computed(() => user.value?.role || null)

  // Actions
  async function login(email, password) {
    // authApi.login -> { message, token, user }
    const data = await authApi.login(email, password)
    token.value = data.token
    user.value  = data.user
    localStorage.setItem('token', token.value)
    localStorage.setItem('user', JSON.stringify(user.value))
    return data
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch (e) {
      // abaikan, tetap clear lokal
    }
    clearAuth()
  }

  function clearAuth() {
    token.value = null
    user.value  = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  async function fetchMe() {
    if (!token.value) return
    try {
      const me = await authApi.me()
      user.value = me
      localStorage.setItem('user', JSON.stringify(user.value))
    } catch (e) {
      clearAuth()
    }
  }

  return {
    token, user, isLoggedIn, isAdmin, isViewer, role,
    login, logout, clearAuth, fetchMe,
  }
})
