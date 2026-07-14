import { ref, onMounted, onUnmounted } from 'vue'


export function useClock() {
  const now = ref(new Date())
  let timer = null

  onMounted(() => {
    timer = setInterval(() => { now.value = new Date() }, 1000)
  })

  onUnmounted(() => {
    if (timer) clearInterval(timer)
  })

  return { now }
}
