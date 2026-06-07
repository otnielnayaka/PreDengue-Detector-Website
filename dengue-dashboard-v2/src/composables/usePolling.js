import { ref, onMounted, onUnmounted } from 'vue'

/**
 * ============================================================================
 *  usePolling — Production-safe realtime polling
 * ============================================================================
 *
 *  Features:
 *    - Cleanup on unmount (no memory leaks)
 *    - Pause on tab hidden (Page Visibility API)
 *    - Retry-safe (won't crash on errors)
 *    - Single timer per instance (no duplicate polling)
 *    - Optional immediate fetch on mount
 *
 *  Usage:
 *    const { data, error, loading, refresh, stop } =
 *      usePolling(() => api.get('/foo'), 2000)
 * ============================================================================
 */
export function usePolling(fetcher, intervalMs = 2000, options = {}) {
  const { immediate = true, onError = null } = options

  const data    = ref(null)
  const error   = ref(null)
  const loading = ref(false)
  const lastUpdated = ref(null)

  let timer = null
  let isUnmounted = false

  async function refresh() {
    if (isUnmounted) return
    // Don't show loading state on subsequent polls (avoid UI flicker)
    if (data.value === null) loading.value = true

    try {
      const result = await fetcher()
      if (isUnmounted) return
      data.value = result
      error.value = null
      lastUpdated.value = new Date()
    } catch (e) {
      if (isUnmounted) return
      error.value = e
      // Keep old data on error - don't blank UI
      if (onError) onError(e)
    } finally {
      if (!isUnmounted) loading.value = false
    }
  }

  function scheduleNext() {
    if (isUnmounted || document.hidden) return
    clearTimer()
    timer = setTimeout(async () => {
      await refresh()
      scheduleNext()
    }, intervalMs)
  }

  function clearTimer() {
    if (timer) {
      clearTimeout(timer)
      timer = null
    }
  }

  function stop() {
    isUnmounted = true
    clearTimer()
  }

  function handleVisibility() {
    if (document.hidden) {
      clearTimer()
    } else {
      refresh().then(scheduleNext)
    }
  }

  onMounted(() => {
    if (immediate) refresh().then(scheduleNext)
    document.addEventListener('visibilitychange', handleVisibility)
  })

  onUnmounted(() => {
    isUnmounted = true
    clearTimer()
    document.removeEventListener('visibilitychange', handleVisibility)
  })

  return { data, error, loading, lastUpdated, refresh, stop }
}
