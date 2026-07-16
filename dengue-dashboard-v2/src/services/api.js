import axios from 'axios'

/**
 * ============================================================================
 *  API SERVICE — Single source of truth for backend communication
 * ============================================================================
 *  Laravel envelope: { success, message, data }  ->  unwrapped jadi `data`
 *  Base URL utama  : /api/v1  (lewat proxy nginx -> backend-web)
 *  Auth (login/me) : /api     (di LUAR prefix v1)
 * ============================================================================
 */

const BASE_URL = import.meta.env.VITE_API_URL || '/api/v1'

const api = axios.create({
  baseURL: BASE_URL,
  timeout: 15_000,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

// --- Request interceptor: device key (alat IoT) + auth token (user) ---
api.interceptors.request.use((config) => {
  const deviceToken = localStorage.getItem('device_token')
  if (deviceToken) config.headers['X-Device-Key'] = deviceToken

  const authToken = localStorage.getItem('token')
  if (authToken) config.headers['Authorization'] = `Bearer ${authToken}`

  return config
})

// --- Response interceptor: unwrap envelope, normalize errors, auto-logout 401 ---
api.interceptors.response.use(
  (response) => {
    const payload = response.data
    if (
      payload &&
      typeof payload === 'object' &&
      'success' in payload &&
      'data' in payload
    ) {
      return payload.data
    }
    return payload
  },
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }
    return Promise.reject({
      status:    error.response?.status ?? 0,
      message:   error.response?.data?.message ?? error.message ?? 'Unknown error',
      errors:    error.response?.data?.errors ?? null,
      isNetwork: !error.response,
      isTimeout: error.code === 'ECONNABORTED',
    })
  }
)

// ============================================================================
//  AUTH instance — base /api (login/logout/me ada DI LUAR prefix v1)
// ============================================================================
const AUTH_BASE = import.meta.env.VITE_API_URL
  ? import.meta.env.VITE_API_URL.replace(/\/v1\/?$/, '')   // /api/v1 -> /api
  : '/api'

const authAxios = axios.create({
  baseURL: AUTH_BASE,
  timeout: 15_000,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

authAxios.interceptors.request.use((config) => {
  const authToken = localStorage.getItem('token')
  if (authToken) config.headers['Authorization'] = `Bearer ${authToken}`
  return config
})

export const authApi = {
  login:  (email, password) => authAxios.post('/login', { email, password }).then(r => r.data),
  logout: () => authAxios.post('/logout').then(r => r.data),
  me:     () => authAxios.get('/me').then(r => r.data),
}

// ============================================================================
//  Endpoint modules — semantic groupings
// ============================================================================

export const dashboardApi = {
  summary:         () => api.get('/dashboard/summary'),
  positives:       () => api.get('/dashboard/positives'),
  statusBreakdown: () => api.get('/dashboard/status-breakdown'),
}

export const measurementApi = {
  list:       (params = {}) => api.get('/measurements', { params }),
  latest:     () => api.get('/measurements/latest'),
  show:       (id) => api.get(`/measurements/${id}`),
  graph:      (id) => api.get(`/measurements/${id}/graph`),
  create:     (payload) => api.post('/measurements', payload),
  activeScan: () => api.get('/measurements/active'),
  live:       (id) => api.get(`/measurements/${id}/live`),
  updateLocation: (id, payload) => api.patch(`/measurements/${id}/location`, payload),
}

export const telemetryApi = {
  latest: (deviceId = null) =>
    api.get('/telemetry/latest', { params: deviceId ? { device_id: deviceId } : {} }),
  send:   (payload) => api.post('/telemetry', payload),
}

export const deviceApi = {
  list:   () => api.get('/devices'),
  status: () => api.get('/devices/status'),
  show:   (id) => api.get(`/devices/${id}`),
}

export const mapApi = {
  measurements: () => api.get('/map/measurements'),
}

export const locationApi = {
  list: () => api.get('/locations'),
}

export default api