# PreDengue Dashboard v2 — Refactor untuk Stabilitas

Frontend Vue 3 untuk sistem monitoring IoT Potentiostat. Versi 2 ini
**dibangun ulang dari nol** dengan fokus stabilitas, sambil tetap
mempertahankan visual design yang sudah disukai.

## Apa yang Berubah dari v1

### 1. Typography — bersih dan profesional
- **Hapus** Instrument Serif (font decorative yang terlalu fashion-magazine)
- **Pakai Inter saja** untuk semua heading dengan tracking ketat (-0.04em) + font-weight 600-700
- **JetBrains Mono** dipertahankan untuk angka scientific (tabular numerals)
- Hasilnya: feel enterprise medical seperti BioLogic EC-Lab, bukan editorial magazine

### 2. Logo — refined
- Symbol: heartbeat waveform inside red square dengan shadow halus
- Typography: "PreDengue" dengan letter-spacing tight + "NS1 Detector" sebagai eyebrow
- Online indicator dot kecil menempel di logo

### 3. State Management — Pinia Stores
- **Sebelumnya:** setiap page punya composable polling sendiri → race condition saat navigasi
- **Sekarang:** 3 Pinia stores (`dashboard`, `telemetry`, `measurement`) yang di-init sekali di `DashboardLayout`. Semua page READ dari stores.
- Benefit: tidak ada double-polling, data cached saat navigasi, lifecycle bersih.

### 4. KPI Cards — Defensive Rendering
- KPI cards **selalu render dengan angka 0** saat data belum ada, **tidak skeleton terus-menerus**
- Skeleton hanya muncul saat fetch pertama benar-benar belum balik
- Semua nilai dibungkus `Number(... ?? 0)` untuk mencegah `undefined`

### 5. VoltammogramChart — Bulletproof ECharts
- Empty state grafis (bukan blank putih) saat tidak ada data
- Proper lifecycle dengan `chart.isDisposed()` check
- ResizeObserver untuk responsive
- Animation di-disable saat live mode untuk no-flicker

### 6. Single Source of Truth untuk API
- `services/api.js` adalah satu-satunya tempat axios di-config
- Response interceptor unwrap Laravel envelope `{success, message, data}` → kembalikan data langsung
- Error interceptor normalize ke shape `{status, message, isNetwork, isTimeout}`

## Struktur Project

```
src/
├── components/
│   ├── cards/
│   │   └── KpiCard.vue            ← reliable metric card
│   ├── charts/
│   │   └── VoltammogramChart.vue  ← bulletproof ECharts wrapper
│   ├── layout/
│   │   ├── DashboardLayout.vue    ← starts polling, single owner
│   │   ├── Sidebar.vue            ← refined logo + nav
│   │   └── Topbar.vue             ← realtime clock + actions
│   └── ui/
│       ├── StatusBadge.vue
│       └── TelemetryRow.vue
├── composables/
│   ├── usePolling.js              ← production-safe polling primitive
│   └── useClock.js                ← realtime clock
├── pages/
│   ├── LandingPage.vue
│   ├── MonitoringDashboard.vue    ← reads from stores
│   ├── MeasurementSetup.vue
│   ├── RealtimeMeasuring.vue
│   ├── ResultDetection.vue
│   ├── DataLog.vue
│   └── Settings.vue
├── router/
│   └── index.js
├── services/
│   └── api.js                     ← single source of truth
├── stores/
│   ├── dashboard.js               ← KPI/summary
│   ├── telemetry.js               ← realtime device data
│   └── measurement.js             ← latest + list
├── App.vue
├── main.js
└── style.css
```

## Cara Setup

```bash
# 1. Install dependencies
npm install

# 2. Optional: configure backend URL (default uses Vite proxy)
cp .env.example .env.local

# 3. Run dev server
npm run dev

# 4. Open in browser
open http://localhost:5173
```

Pastikan backend Laravel jalan di port 8000:
```bash
cd ../dengue-backend
php artisan serve --host=0.0.0.0 --port=8000
```

## Polling Intervals

| Store        | Interval | Endpoint                  |
|--------------|----------|---------------------------|
| Dashboard    | 5s       | `/dashboard/summary`      |
| Telemetry    | 2s       | `/telemetry/latest`       |
| Latest scan  | 5s       | `/measurements/latest`    |

Polling otomatis **pause saat tab tidak aktif** (Page Visibility API)
untuk hemat resource dan tidak bombarding backend.

## Tech Stack

- **Vue 3** Composition API
- **Vite 5**
- **Pinia 2** state management
- **Vue Router 4**
- **Tailwind 3** with custom design tokens
- **ECharts 5** modular imports
- **Axios** HTTP client
- **Lucide** icons
- **Inter + JetBrains Mono** fonts (enterprise typography)
