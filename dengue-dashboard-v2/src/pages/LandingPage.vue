<script setup>
import { RouterLink } from 'vue-router'
import { Activity, Cpu, CloudUpload, FlaskConical, ArrowRight, ShieldCheck, MapPin } from 'lucide-vue-next'

const features = [
  { icon: Cpu, title: 'Portable Device', desc: 'Form factor genggam berbasis Feather M4 + AirLift, dirancang untuk pemakaian lapangan di puskesmas dan klinik komunitas.' },
  { icon: Activity, title: 'Real-time Monitoring', desc: 'Voltammogram langsung di-stream ke dashboard. Operator lihat scan berjalan persis seperti instrument lab desktop.' },
  { icon: CloudUpload, title: 'Cloud Storage', desc: 'Setiap hasil tersinkron ke cloud dengan metadata lengkap — sample ID, lokasi, parameter scan, dan grafik penuh.' },
  { icon: FlaskConical, title: 'Electrochemical Analysis', desc: 'Metode DPV, CV, dan SWV mengikuti standar elektrokimia untuk deteksi antigen NS1 dengue sebelum onset gejala berat.' },
]
</script>

<template>
  <div class="min-h-screen bg-white">
    <header class="border-b border-line bg-white/80 backdrop-blur-sm sticky top-0 z-40">
      <nav class="max-w-7xl mx-auto px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="h-9 w-9 rounded-lg bg-primary-600 flex items-center justify-center shadow-sm">
            <svg viewBox="0 0 24 24" class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 12 L7 12 L9 8 L11 16 L13 12 L21 12" />
            </svg>
          </div>
          <div class="leading-tight">
            <p class="text-base font-bold tracking-tighter text-ink">PreDengue Detector</p>
            <p class="text-2xs font-semibold uppercase tracking-[0.14em] text-ink-subtle mt-0.5">NS1 · IoT Potentiostat</p>
          </div>
        </div>

        <div class="hidden md:flex items-center gap-6">
          <a href="#tentang" class="text-sm text-ink-muted hover:text-ink transition-colors">Tentang</a>
          <a href="#fitur" class="text-sm text-ink-muted hover:text-ink transition-colors">Teknologi</a>
          <RouterLink to="/app/dashboard" class="btn-primary text-sm py-2">
            Masuk Monitoring
            <ArrowRight class="h-3.5 w-3.5" :stroke-width="2.25" />
          </RouterLink>
        </div>
      </nav>
    </header>

    <!-- HERO -->
    <section class="max-w-7xl mx-auto px-6 lg:px-8 pt-20 pb-24">
      <div class="grid lg:grid-cols-12 gap-12 items-center">
        <div class="lg:col-span-7">
          <div class="inline-flex items-center gap-2 rounded-full border border-primary-200 bg-primary-50 px-3 py-1 mb-6">
            <span class="h-1.5 w-1.5 rounded-full bg-primary-600 animate-pulse-soft" />
            <span class="text-2xs uppercase tracking-[0.12em] text-primary-700 font-semibold">Active research prototype</span>
          </div>

          <h1 class="display-xl text-5xl lg:text-6xl mb-6">
            Deteksi dini<br />
            <span class="text-primary-600">demam berdarah</span><br />
            berbasis IoT.
          </h1>

          <p class="text-lg text-ink-subtle leading-relaxed max-w-xl mb-8">
            Sistem monitoring elektrokimia menggunakan Potentiostat Portable dengan
            integrasi cloud dan analisis data realtime. Dirancang untuk fasilitas
            kesehatan primer di daerah endemik dengue.
          </p>

          <div class="flex flex-wrap gap-3">
            <RouterLink to="/app/dashboard" class="btn-primary text-base px-5 py-3">
              Masuk Monitoring
              <ArrowRight class="h-4 w-4" :stroke-width="2.25" />
            </RouterLink>
            <a href="#fitur" class="btn-secondary text-base px-5 py-3">Tentang Teknologi</a>
          </div>

          <div class="flex items-center gap-8 mt-12 pt-8 border-t border-line">
            <div>
              <p class="display-lg text-3xl">3</p>
              <p class="text-2xs uppercase tracking-[0.12em] text-ink-subtle mt-1.5">Metode Voltammetri</p>
            </div>
            <div class="h-10 w-px bg-line" />
            <div>
              <p class="display-lg text-3xl">&lt;1<span class="text-lg text-ink-subtle">min</span></p>
              <p class="text-2xs uppercase tracking-[0.12em] text-ink-subtle mt-1.5">Waktu Scan</p>
            </div>
            <div class="h-10 w-px bg-line" />
            <div>
              <p class="display-lg text-3xl">NS1</p>
              <p class="text-2xs uppercase tracking-[0.12em] text-ink-subtle mt-1.5">Antigen Target</p>
            </div>
          </div>
        </div>

        <div class="lg:col-span-5">
          <div class="relative">
            <div class="absolute -top-6 -right-6 h-32 w-32 rounded-full bg-primary-100 blur-3xl opacity-60" />
            <div class="relative lab-card p-6">
              <div class="flex items-center justify-between mb-5">
                <div>
                  <p class="eyebrow">Active Device</p>
                  <p class="font-mono text-base font-semibold text-ink mt-1">POT-001 / Lab Bandung</p>
                </div>
                <span class="status-pill border-emerald-200 bg-emerald-50 text-emerald-700">
                  <span class="status-pill-dot bg-emerald-500 animate-pulse-soft" />
                  Live
                </span>
              </div>

              <div class="relative h-44 rounded-lg border border-line grid-paper overflow-hidden mb-4 p-3">
                <svg viewBox="0 0 400 160" class="w-full h-full" preserveAspectRatio="none">
                  <defs>
                    <linearGradient id="vfill" x1="0" x2="0" y1="0" y2="1">
                      <stop offset="0%" stop-color="#DC2626" stop-opacity="0.18" />
                      <stop offset="100%" stop-color="#DC2626" stop-opacity="0" />
                    </linearGradient>
                  </defs>
                  <path d="M 0 140 Q 50 138 80 132 Q 120 120 150 110 Q 180 95 200 60 Q 220 20 240 50 Q 260 95 280 110 Q 320 130 360 138 L 400 142 L 400 160 L 0 160 Z" fill="url(#vfill)" />
                  <path d="M 0 140 Q 50 138 80 132 Q 120 120 150 110 Q 180 95 200 60 Q 220 20 240 50 Q 260 95 280 110 Q 320 130 360 138 L 400 142"
                        fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" />
                  <circle cx="220" cy="20" r="4" fill="#DC2626" />
                  <circle cx="220" cy="20" r="8" fill="none" stroke="#DC2626" stroke-opacity="0.3" />
                </svg>
                <div class="absolute top-2 left-3 text-2xs font-mono text-ink-faint">DPV · 16.2 µA</div>
              </div>

              <div class="grid grid-cols-3 gap-3">
                <div><p class="eyebrow">Peak I</p><p class="font-mono text-sm font-semibold text-ink mt-0.5">16.234 µA</p></div>
                <div><p class="eyebrow">Peak V</p><p class="font-mono text-sm font-semibold text-ink mt-0.5">0.185 V</p></div>
                <div><p class="eyebrow">Result</p><p class="font-mono text-sm font-semibold text-primary-600 mt-0.5">POSITIVE</p></div>
              </div>
            </div>

            <div class="absolute -bottom-4 -left-6 lab-card px-4 py-3 hidden md:flex items-center gap-3">
              <div class="h-9 w-9 rounded-lg bg-emerald-100 flex items-center justify-center">
                <ShieldCheck class="h-4 w-4 text-emerald-700" :stroke-width="2" />
              </div>
              <div>
                <p class="text-2xs uppercase tracking-[0.12em] text-ink-subtle">24h sync</p>
                <p class="font-mono text-sm text-ink">142 samples</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ABOUT -->
    <section id="tentang" class="border-t border-line bg-surface-muted">
      <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20">
        <div class="grid lg:grid-cols-12 gap-12">
          <div class="lg:col-span-5">
            <p class="eyebrow text-primary-600">Tentang Sistem</p>
            <h2 class="display-xl mt-3 text-4xl">
              Dari sampel ke diagnostik dini, di satu perangkat.
            </h2>
          </div>
          <div class="lg:col-span-7">
            <p class="text-base text-ink-muted leading-relaxed mb-4">
              PreDengue Detector adalah platform integrasi antara hardware potentiostat
              portable berbasis Feather M4 + ESP32 AirLift dan backend cloud yang
              dirancang khusus untuk monitoring elektrokimia berskala fasilitas kesehatan.
            </p>
            <p class="text-base text-ink-muted leading-relaxed">
              Sistem ini mendukung tiga metode voltammetri standar (DPV, CV, SWV) untuk
              deteksi antigen NS1 — biomarker yang muncul lebih awal dari antibodi pada
              infeksi dengue.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- FEATURES -->
    <section id="fitur" class="max-w-7xl mx-auto px-6 lg:px-8 py-20">
      <div class="max-w-2xl mb-12">
        <p class="eyebrow text-primary-600">Teknologi</p>
        <h2 class="display-xl mt-3 text-4xl">Empat pilar arsitektur sistem.</h2>
      </div>

      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-5">
        <div v-for="(f, idx) in features" :key="f.title" class="lab-card-hover p-6 group">
          <div class="h-10 w-10 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center mb-5 group-hover:bg-primary-100 transition-colors">
            <component :is="f.icon" class="h-5 w-5 text-primary-600" :stroke-width="1.75" />
          </div>
          <p class="text-2xs font-mono text-ink-faint mb-1">0{{ idx + 1 }}</p>
          <h3 class="font-semibold text-ink text-base mb-2">{{ f.title }}</h3>
          <p class="text-sm text-ink-subtle leading-relaxed">{{ f.desc }}</p>
        </div>
      </div>
    </section>

    <section class="border-t border-line bg-white">
      <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20 text-center">
        <MapPin class="h-8 w-8 text-primary-600 mx-auto mb-4" :stroke-width="1.5" />
        <h2 class="display-xl text-4xl max-w-2xl mx-auto">
          Mulai monitoring deteksi NS1 di fasilitas Anda.
        </h2>
        <RouterLink to="/app/dashboard" class="btn-primary text-base px-6 py-3 mt-8">
          Masuk Monitoring <ArrowRight class="h-4 w-4" :stroke-width="2.25" />
        </RouterLink>
      </div>
    </section>

    <footer class="border-t border-line">
      <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 text-center">
        <p class="text-2xs uppercase tracking-[0.12em] text-ink-faint">
          © 2026 PreDengue Detector · Tugas Akhir · Bandung
        </p>
      </div>
    </footer>
  </div>
</template>
