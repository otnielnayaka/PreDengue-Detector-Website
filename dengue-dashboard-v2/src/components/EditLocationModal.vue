<script setup>
import { ref, watch } from 'vue'
import { X, MapPin, Loader2 } from 'lucide-vue-next'
import { measurementApi } from '@/services/api'

/**
 * Modal edit lokasi untuk measurement.
 * Hanya bisa dibuka admin (penjagaan di DataLog.vue via v-if="isAdmin").
 * Backend juga enforce role:admin via middleware.
 */

const props = defineProps({
  /** Objek measurement lengkap dari store (sudah dinormalisasi) */
  measurement: { type: Object, default: null },
  /** Kontrol visibilitas modal */
  modelValue: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'saved'])

// ===== Form state =====
const form = ref({
  location_name: '',
  province: '',
  city_regency: '',
  district: '',
  village: '',
  latitude: '',
  longitude: '',
  notes: '',
})

const errors   = ref({})
const loading  = ref(false)
const apiError = ref(null)

// Pre-fill dari lokasi existing kalau ada
watch(() => props.measurement, (m) => {
  if (!m) return
  const loc = m.location ?? {}
  form.value = {
    location_name: loc.location_name ?? '',
    province:      loc.province      ?? '',
    city_regency:  loc.city_regency  ?? '',
    district:      loc.district      ?? loc.kecamatan ?? '',
    village:       loc.village       ?? loc.desa      ?? '',
    latitude:      loc.latitude  != null ? String(loc.latitude)  : '',
    longitude:     loc.longitude != null ? String(loc.longitude) : '',
    notes:         loc.notes ?? '',
  }
  errors.value   = {}
  apiError.value = null
}, { immediate: true })

// Reset saat modal ditutup
watch(() => props.modelValue, (open) => {
  if (!open) {
    errors.value   = {}
    apiError.value = null
    loading.value  = false
  }
})

// ===== Client-side validation =====
function validate() {
  const e = {}
  if (!form.value.location_name.trim())
    e.location_name = 'Nama lokasi wajib diisi.'
  if (!form.value.district.trim())
    e.district = 'Kecamatan / district wajib diisi.'
  if (!form.value.village.trim())
    e.village = 'Desa / village wajib diisi.'

  const lat = Number(form.value.latitude)
  if (form.value.latitude === '' || isNaN(lat) || lat < -90 || lat > 90)
    e.latitude = 'Latitude harus antara -90 dan 90.'

  const lng = Number(form.value.longitude)
  if (form.value.longitude === '' || isNaN(lng) || lng < -180 || lng > 180)
    e.longitude = 'Longitude harus antara -180 dan 180.'

  errors.value = e
  return Object.keys(e).length === 0
}

// ===== Submit =====
async function submit() {
  if (!validate()) return
  loading.value  = true
  apiError.value = null

  try {
    await measurementApi.updateLocation(props.measurement.id, {
      location_name: form.value.location_name.trim(),
      province:      form.value.province.trim()     || undefined,
      city_regency:  form.value.city_regency.trim() || undefined,
      district:      form.value.district.trim(),
      village:       form.value.village.trim(),
      latitude:      Number(form.value.latitude),
      longitude:     Number(form.value.longitude),
      notes:         form.value.notes.trim()        || undefined,
    })

    emit('saved')
    close()
  } catch (err) {
    // Validation errors dari backend (422)
    if (err?.status === 422 && err?.errors) {
      const be = {}
      for (const [field, msgs] of Object.entries(err.errors)) {
        be[field] = Array.isArray(msgs) ? msgs[0] : msgs
      }
      errors.value = be
    } else if (err?.status === 403) {
      apiError.value = 'Anda tidak memiliki izin untuk mengubah lokasi ini.'
    } else {
      apiError.value = err?.message ?? 'Terjadi kesalahan. Silakan coba lagi.'
    }
  } finally {
    loading.value = false
  }
}

function close() {
  emit('update:modelValue', false)
}
</script>

<template>
  <!-- Overlay -->
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="modelValue"
           class="fixed inset-0 z-50 flex items-center justify-center p-4"
           role="dialog" aria-modal="true" aria-labelledby="edit-loc-title"
           @click.self="close">

        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close" />

        <!-- Panel -->
        <div class="relative z-10 w-full max-w-lg bg-white rounded-xl shadow-2xl
                    border border-line overflow-hidden">

          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-line">
            <div class="flex items-center gap-2">
              <MapPin class="h-4 w-4 text-primary-600" :stroke-width="1.75" />
              <h2 id="edit-loc-title" class="text-sm font-semibold text-ink">
                Edit Lokasi Pengujian
              </h2>
            </div>
            <button @click="close"
                    class="p-1.5 rounded-md text-ink-muted hover:bg-surface-muted
                           hover:text-ink transition-colors"
                    aria-label="Tutup modal">
              <X class="h-4 w-4" :stroke-width="2" />
            </button>
          </div>

          <!-- Sample ID label -->
          <div v-if="measurement" class="px-6 pt-3 pb-0">
            <p class="text-xs text-ink-muted">
              Measurement:
              <span class="font-mono font-medium text-ink">{{ measurement.sample_id }}</span>
            </p>
          </div>

          <!-- API Error -->
          <div v-if="apiError"
               class="mx-6 mt-3 px-3 py-2 rounded-lg bg-red-50 border border-red-200
                      text-xs text-red-700">
            {{ apiError }}
          </div>

          <!-- Form -->
          <form class="px-6 py-4 space-y-4" @submit.prevent="submit" novalidate>

            <!-- Nama Lokasi -->
            <div>
              <label class="block text-xs font-medium text-ink-muted mb-1">
                Nama Lokasi <span class="text-red-500">*</span>
              </label>
              <input v-model="form.location_name"
                     type="text" maxlength="150" placeholder="cth. Laboratorium BRIN"
                     class="field-input"
                     :class="{ 'border-red-400 focus:ring-red-300': errors.location_name }" />
              <p v-if="errors.location_name" class="mt-1 text-xs text-red-600">
                {{ errors.location_name }}
              </p>
            </div>

            <!-- Provinsi + Kota -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-ink-muted mb-1">Provinsi</label>
                <input v-model="form.province"
                       type="text" maxlength="100" placeholder="Jawa Barat"
                       class="field-input"
                       :class="{ 'border-red-400 focus:ring-red-300': errors.province }" />
                <p v-if="errors.province" class="mt-1 text-xs text-red-600">
                  {{ errors.province }}
                </p>
              </div>
              <div>
                <label class="block text-xs font-medium text-ink-muted mb-1">
                  Kota / Kabupaten
                </label>
                <input v-model="form.city_regency"
                       type="text" maxlength="100" placeholder="Kota Bandung"
                       class="field-input"
                       :class="{ 'border-red-400 focus:ring-red-300': errors.city_regency }" />
                <p v-if="errors.city_regency" class="mt-1 text-xs text-red-600">
                  {{ errors.city_regency }}
                </p>
              </div>
            </div>

            <!-- Kecamatan + Desa -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-ink-muted mb-1">
                  Kecamatan <span class="text-red-500">*</span>
                </label>
                <input v-model="form.district"
                       type="text" maxlength="100" placeholder="Coblong"
                       class="field-input"
                       :class="{ 'border-red-400 focus:ring-red-300': errors.district }" />
                <p v-if="errors.district" class="mt-1 text-xs text-red-600">
                  {{ errors.district }}
                </p>
              </div>
              <div>
                <label class="block text-xs font-medium text-ink-muted mb-1">
                  Desa / Kelurahan <span class="text-red-500">*</span>
                </label>
                <input v-model="form.village"
                       type="text" maxlength="100" placeholder="Dago"
                       class="field-input"
                       :class="{ 'border-red-400 focus:ring-red-300': errors.village }" />
                <p v-if="errors.village" class="mt-1 text-xs text-red-600">
                  {{ errors.village }}
                </p>
              </div>
            </div>

            <!-- Koordinat -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-ink-muted mb-1">
                  Latitude <span class="text-red-500">*</span>
                </label>
                <input v-model="form.latitude"
                       type="number" step="any" min="-90" max="90"
                       placeholder="-6.8647"
                       class="field-input font-mono text-xs"
                       :class="{ 'border-red-400 focus:ring-red-300': errors.latitude }" />
                <p v-if="errors.latitude" class="mt-1 text-xs text-red-600">
                  {{ errors.latitude }}
                </p>
              </div>
              <div>
                <label class="block text-xs font-medium text-ink-muted mb-1">
                  Longitude <span class="text-red-500">*</span>
                </label>
                <input v-model="form.longitude"
                       type="number" step="any" min="-180" max="180"
                       placeholder="107.5892"
                       class="field-input font-mono text-xs"
                       :class="{ 'border-red-400 focus:ring-red-300': errors.longitude }" />
                <p v-if="errors.longitude" class="mt-1 text-xs text-red-600">
                  {{ errors.longitude }}
                </p>
              </div>
            </div>

            <!-- Catatan -->
            <div>
              <label class="block text-xs font-medium text-ink-muted mb-1">
                Catatan <span class="text-ink-faint text-xs font-normal">(opsional)</span>
              </label>
              <textarea v-model="form.notes"
                        rows="2" maxlength="1000"
                        placeholder="cth. Pengujian larutan Fe²⁺/Fe³⁺ 5 mM"
                        class="field-input resize-none"
                        :class="{ 'border-red-400 focus:ring-red-300': errors.notes }" />
              <p v-if="errors.notes" class="mt-1 text-xs text-red-600">
                {{ errors.notes }}
              </p>
            </div>

            <!-- Footer actions -->
            <div class="flex items-center justify-end gap-2 pt-2 border-t border-line">
              <button type="button" @click="close"
                      class="btn-ghost text-sm py-2" :disabled="loading">
                Batal
              </button>
              <button type="submit"
                      class="btn-primary text-sm py-2 min-w-[100px]"
                      :disabled="loading">
                <Loader2 v-if="loading" class="h-3.5 w-3.5 animate-spin" />
                <span v-else>Simpan Lokasi</span>
              </button>
            </div>
          </form>
        </div>

      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.15s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.modal-enter-active .relative,
.modal-leave-active .relative {
  transition: transform 0.15s ease;
}
.modal-enter-from .relative,
.modal-leave-to .relative {
  transform: scale(0.96);
}
</style>
