<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Zap, X, Info, ChevronRight, Clock, TrendingUp, Ruler, FlaskConical, MapPin } from 'lucide-vue-next'
import { locationApi } from '@/services/api'
import { useMeasurementStore } from '@/stores/measurement'

const router = useRouter()
const measurementStore = useMeasurementStore()

// --- Lokasi sampel (kecamatan/desa) ---
const locations = ref([])
const selKecamatan = ref('')
const selLocationId = ref(null)

const kecamatanList = computed(() =>
  [...new Set(locations.value.map((l) => l.kecamatan))].sort()
)
const desaList = computed(() =>
  locations.value.filter((l) => l.kecamatan === selKecamatan.value)
)

function onKecamatanChange() {
  selLocationId.value = null
  measurementStore.setSelectedLocation(null)
}
function onDesaChange() {
  measurementStore.setSelectedLocation(selLocationId.value)
}

onMounted(async () => {
  try {
    locations.value = (await locationApi.list()) || []
  } catch (e) {
    locations.value = []
  }
})

// Metode yang tersedia untuk pengukuran BARU. SWV historis tetap dapat
// dibaca di Data Log/Result, tapi tidak lagi ditawarkan di sini.
const SUPPORTED_METHODS = ['DPV', 'CV']

const form = ref({
  method: 'DPV',
  sample_id: `NS1-${new Date().toISOString().slice(0, 10).replace(/-/g, '')}-${String(Math.floor(Math.random() * 999)).padStart(3, '0')}`,
  start_voltage: -0.2,
  end_voltage: 0.6,
  step_voltage: 0.005,
  scan_rate: 0.05,
  pulse_amplitude: 0.025,
  cycles: 1,
})

// Guard: kalau state method lama pernah berisi SWV, jatuhkan ke default DPV.
if (!SUPPORTED_METHODS.includes(form.value.method)) {
  form.value.method = 'DPV'
}

const methods = [
  { id: 'DPV', name: 'Differential Pulse', desc: 'High sensitivity for trace antigen detection' },
  { id: 'CV',  name: 'Cyclic Voltammetry', desc: 'Survey scan for redox characterization' },
]

const isCv = computed(() => form.value.method === 'CV')

// DPV: satu arah sapuan start -> end.
// CV: tiap cycle menyapu start -> vertex(end) -> start (dua arah), diulang `cycles` kali.
const dataPoints = computed(() => {
  const span = Math.abs(form.value.end_voltage - form.value.start_voltage)
  if (!form.value.step_voltage) return 0
  if (isCv.value) {
    const cycles = Math.max(1, Number(form.value.cycles) || 1)
    return Math.round((span * 2 / form.value.step_voltage) * cycles)
  }
  return Math.round(span / form.value.step_voltage)
})

const estimatedDuration = computed(() => {
  const span = Math.abs(form.value.end_voltage - form.value.start_voltage)
  if (!form.value.scan_rate) return 0
  if (isCv.value) {
    const cycles = Math.max(1, Number(form.value.cycles) || 1)
    return Math.round((span * 2 / form.value.scan_rate) * cycles)
  }
  return Math.round(span / form.value.scan_rate)
})

function runScan() {
  router.push('/app/measuring')
}
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

      <div class="xl:col-span-2 space-y-6">
        <!-- Method selection -->
        <div class="lab-card p-6">
          <div class="mb-5">
            <p class="eyebrow">01 · Method</p>
            <h2 class="display-md mt-1">Voltammetry Technique</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <button v-for="m in methods" :key="m.id" type="button"
                    @click="form.method = m.id"
                    class="text-left p-4 rounded-lg border-2 transition-all hover:shadow-card"
                    :class="form.method === m.id
                      ? 'border-primary-600 bg-primary-50/50 ring-2 ring-primary-100'
                      : 'border-line bg-white hover:border-line-strong'">
              <div class="flex items-center justify-between mb-2">
                <span class="font-mono text-sm font-semibold"
                      :class="form.method === m.id ? 'text-primary-700' : 'text-ink'">
                  {{ m.id }}
                </span>
                <div class="h-4 w-4 rounded-full border-2 flex items-center justify-center"
                     :class="form.method === m.id ? 'border-primary-600' : 'border-line-strong'">
                  <div v-if="form.method === m.id" class="h-2 w-2 rounded-full bg-primary-600" />
                </div>
              </div>
              <p class="text-sm font-semibold text-ink mb-1">{{ m.name }}</p>
              <p class="text-xs text-ink-subtle leading-relaxed">{{ m.desc }}</p>
            </button>
          </div>
        </div>

        <!-- Scan parameters -->
        <div class="lab-card p-6">
          <div class="mb-5">
            <p class="eyebrow">02 · Parameters</p>
            <h2 class="display-md mt-1">Scan Configuration</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
            <div class="md:col-span-2">
              <label class="field-label">Sample ID</label>
              <input v-model="form.sample_id" type="text" class="field-input font-mono" />
            </div>
            <div>
              <label class="field-label flex items-center justify-between">Start Voltage <span class="font-mono text-2xs text-ink-faint">V</span></label>
              <input v-model.number="form.start_voltage" type="number" step="0.001" class="field-input" />
            </div>
            <div>
              <label class="field-label flex items-center justify-between">{{ isCv ? 'Vertex Voltage' : 'End Voltage' }} <span class="font-mono text-2xs text-ink-faint">V</span></label>
              <input v-model.number="form.end_voltage" type="number" step="0.001" class="field-input" />
            </div>
            <div>
              <label class="field-label flex items-center justify-between">Step Voltage <span class="font-mono text-2xs text-ink-faint">V</span></label>
              <input v-model.number="form.step_voltage" type="number" step="0.001" class="field-input" />
            </div>
            <div>
              <label class="field-label flex items-center justify-between">Scan Rate <span class="font-mono text-2xs text-ink-faint">V/s</span></label>
              <input v-model.number="form.scan_rate" type="number" step="0.001" class="field-input" />
            </div>
            <div v-if="isCv">
              <label class="field-label flex items-center justify-between">Cycles <span class="font-mono text-2xs text-ink-faint">#</span></label>
              <input v-model.number="form.cycles" type="number" step="1" min="1" class="field-input" />
            </div>
            <div v-if="!isCv" class="md:col-span-2">
              <label class="field-label flex items-center justify-between">Pulse Amplitude <span class="font-mono text-2xs text-ink-faint">V</span></label>
              <input v-model.number="form.pulse_amplitude" type="number" step="0.001" class="field-input" />
            </div>
          </div>
        </div>

        <!-- Location (lokasi sampel — alat portable) -->
        <div class="lab-card p-6">
          <div class="mb-5">
            <p class="eyebrow">03 · Location</p>
            <h2 class="display-md mt-1 flex items-center gap-2">
              <MapPin class="h-5 w-5 text-primary-600" :stroke-width="2" />
              Lokasi Pengambilan Sampel
            </h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
            <div>
              <label class="field-label">Kecamatan</label>
              <select v-model="selKecamatan" @change="onKecamatanChange" class="field-input">
                <option value="">— Pilih kecamatan —</option>
                <option v-for="k in kecamatanList" :key="k" :value="k">{{ k }}</option>
              </select>
            </div>
            <div>
              <label class="field-label">Desa</label>
              <select v-model="selLocationId" @change="onDesaChange"
                      :disabled="!selKecamatan" class="field-input disabled:opacity-50">
                <option :value="null">— Pilih desa —</option>
                <option v-for="d in desaList" :key="d.id" :value="d.id">{{ d.desa }}</option>
              </select>
            </div>
          </div>
          <p v-if="!locations.length" class="text-2xs text-ink-faint mt-3">
            Daftar lokasi belum tersedia. Pastikan tabel locations sudah terisi dan endpoint /locations berjalan.
          </p>
        </div>

        <div class="flex items-center justify-between">
          <button class="btn-ghost text-sm"><X class="h-4 w-4" :stroke-width="1.75" />Cancel</button>
          <button @click="runScan" class="btn-primary text-base px-6 py-3">
            <Zap class="h-4 w-4" :stroke-width="2.25" />
            Run Scan
            <ChevronRight class="h-4 w-4" :stroke-width="2" />
          </button>
        </div>
      </div>

      <div class="space-y-6">
        <div class="lab-card p-5">
          <p class="eyebrow mb-4">Estimates</p>
          <div class="space-y-4">
            <div class="flex items-start gap-3">
              <div class="h-9 w-9 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center shrink-0">
                <Clock class="h-4 w-4 text-primary-600" :stroke-width="1.75" />
              </div>
              <div>
                <p class="eyebrow">Estimated duration</p>
                <p class="font-mono text-lg font-semibold text-ink mt-0.5">{{ estimatedDuration }}<span class="text-sm text-ink-faint ml-1">s</span></p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <div class="h-9 w-9 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center shrink-0">
                <TrendingUp class="h-4 w-4 text-primary-600" :stroke-width="1.75" />
              </div>
              <div>
                <p class="eyebrow">Data points</p>
                <p class="font-mono text-lg font-semibold text-ink mt-0.5">{{ dataPoints }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3">
              <div class="h-9 w-9 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center shrink-0">
                <Ruler class="h-4 w-4 text-primary-600" :stroke-width="1.75" />
              </div>
              <div>
                <p class="eyebrow">Range</p>
                <p class="font-mono text-lg font-semibold text-ink mt-0.5">{{ (form.end_voltage - form.start_voltage).toFixed(3) }}<span class="text-sm text-ink-faint ml-1">V</span></p>
              </div>
            </div>
          </div>
        </div>

        <div class="lab-card p-5">
          <div class="flex items-center gap-2 mb-4">
            <FlaskConical class="h-4 w-4 text-primary-600" :stroke-width="1.75" />
            <p class="eyebrow">Electrode Info</p>
          </div>
          <dl class="space-y-2.5 text-sm">
            <div class="flex justify-between"><dt class="text-ink-subtle">Working</dt><dd class="font-mono text-ink">SPCE / Au</dd></div>
            <div class="flex justify-between"><dt class="text-ink-subtle">Reference</dt><dd class="font-mono text-ink">Ag/AgCl</dd></div>
            <div class="flex justify-between"><dt class="text-ink-subtle">Counter</dt><dd class="font-mono text-ink">Carbon</dd></div>
            <div class="flex justify-between"><dt class="text-ink-subtle">Antibody</dt><dd class="font-mono text-ink">Anti-NS1</dd></div>
          </dl>
        </div>

        <div class="rounded-xl border border-primary-200 bg-primary-50/40 p-4">
          <div class="flex gap-3">
            <Info class="h-4 w-4 text-primary-600 shrink-0 mt-0.5" :stroke-width="1.75" />
            <div>
              <p class="text-sm font-semibold text-primary-900">DPV recommended</p>
              <p class="text-xs text-primary-700/80 mt-1 leading-relaxed">
                For NS1 antigen detection, Differential Pulse Voltammetry gives the cleanest peak around 0.18 V.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
