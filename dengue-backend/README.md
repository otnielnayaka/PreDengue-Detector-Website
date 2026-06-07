# Dengue NS1 Potentiostat — Laravel Backend

Scientific IoT monitoring backend for a portable electrochemical
potentiostat (Feather M4 + ESP32 AirLift, Radiustat FeatherWing, SCPE).
Built as a clean REST API in Laravel 12 + MySQL, ready for a Vue/Tailwind
dashboard and future cloud deployment.

---

## 1. Stack

| Layer        | Choice                                 |
|--------------|----------------------------------------|
| Runtime      | PHP 8.3                                |
| Framework    | Laravel 12 (latest stable)             |
| DB           | MySQL 8.x (via Laragon)                |
| Local server | Laragon → `php artisan serve` or `nginx` |
| Frontend     | Vue 3 + Tailwind (next phase)          |
| Hardware     | Feather M4 + ESP32 AirLift, WiFiNINA   |

---

## 2. Step-by-step Laragon setup

### 2.1 Install Laravel

```bash
# inside Laragon's www folder (e.g. C:\laragon\www)
composer create-project laravel/laravel dengue-backend
cd dengue-backend
```

### 2.2 Install the API skeleton (Laravel 11/12 requires this)

```bash
php artisan install:api
```

This creates `routes/api.php` and registers it in `bootstrap/app.php`.

### 2.3 Configure `.env`

```dotenv
APP_NAME="Dengue Potentiostat"
APP_URL=http://dengue-backend.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dengue_potentiostat
DB_USERNAME=root
DB_PASSWORD=
```

### 2.4 Create the database

Open `http://localhost/phpmyadmin` (or HeidiSQL bundled with Laragon)
and create a new database `dengue_potentiostat` with `utf8mb4_unicode_ci`.

### 2.5 Drop in the project files

Copy the contents of this archive into your Laravel project root,
overwriting where files already exist. The folder mapping is:

```
database/migrations/  →  database/migrations/
database/seeders/     →  database/seeders/
app/Models/           →  app/Models/
app/Http/Controllers/ →  app/Http/Controllers/
app/Http/Requests/    →  app/Http/Requests/
app/Http/Resources/   →  app/Http/Resources/
app/Services/         →  app/Services/
app/Support/          →  app/Support/
routes/api.php        →  routes/api.php  (overwrite)
```

### 2.6 Migrate & seed

```bash
php artisan migrate
php artisan db:seed
```

### 2.7 Run

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Verify: `http://127.0.0.1:8000/api/v1/dashboard/summary` should return JSON.

---

## 3. Project structure (clean architecture)

```
app/
├── Models/                    # Eloquent — DB shape & relationships
│   ├── Device.php
│   ├── Location.php
│   ├── Measurement.php
│   ├── VoltammetryPoint.php
│   └── DeviceLog.php
│
├── Http/
│   ├── Controllers/Api/       # Thin HTTP layer — no business logic here
│   │   ├── DeviceController.php
│   │   ├── MeasurementController.php
│   │   ├── DeviceLogController.php
│   │   └── DashboardController.php
│   │
│   ├── Requests/              # Validation + JSON error envelopes
│   │   ├── StoreMeasurementRequest.php
│   │   ├── StoreDeviceLogRequest.php
│   │   └── StoreDeviceRequest.php
│   │
│   └── Resources/             # JSON serialization — the API contract
│       ├── DeviceResource.php
│       ├── LocationResource.php
│       ├── MeasurementResource.php          (no points — for lists)
│       ├── MeasurementDetailResource.php    (full — with voltammogram)
│       ├── VoltammetryPointResource.php
│       └── DeviceLogResource.php
│
├── Services/                  # Business logic — reusable across channels
│   ├── MeasurementService.php   (transactional write + bulk insert)
│   └── DashboardService.php     (aggregate queries)
│
└── Support/
    └── ApiResponse.php        # Unified JSON envelope helper

database/
├── migrations/                # All 5 tables with proper FKs & indexes
└── seeders/                   # Demo data incl. one full DPV scan

routes/
└── api.php                    # /api/v1/... routes
```

### Why this layering

- **Controllers** stay thin. Only: receive request → call service → return resource.
- **Services** own write transactions, bulk inserts, multi-model updates.
  This is what lets the same logic later run from an MQTT worker or a
  scheduled batch import without rewriting anything.
- **Resources** are the single source of truth for what JSON looks like.
  The ESP32 and the Vue frontend parse exactly one schema.
- **Form Requests** enforce input contract at the HTTP boundary.
- **Repositories** are deliberately *not* used. Eloquent already abstracts
  the storage; adding a repository layer here would be ceremony without
  payoff. Add them later only if you swap MySQL for something else.

---

## 4. Database design — the scientific reasoning

### 4.1 Why `voltammetry_points` is a separate table

A single DPV scan produces 200–1000 (V, I) sample pairs. Three reasons
to keep them in their own table rather than serializing into a JSON
column on `measurements`:

1. **Queryability.** With a real table you can `GROUP BY`, run min/max,
   detect peaks server-side, or downsample for graph rendering using SQL.
   With JSON blobs you'd pull the whole thing into PHP and parse every
   time.
2. **Indexable.** The composite index `(measurement_id, sequence_number)`
   makes "fetch this scan in order" an O(log N) seek + sequential read —
   exactly the access pattern the graph view needs.
3. **Scales.** The table will dwarf the others. Keeping it narrow
   (no extra columns, no FKs except parent) keeps row size tiny and
   B-tree depth shallow even at 10⁷+ rows.

### 4.2 Data types — why `decimal` not `float`

`float` and `double` use binary floating point. Values like 0.1 V cannot
be represented exactly, so `0.1 + 0.1 + 0.1 ≠ 0.3`. In electrochemistry
where you'll later run peak-finding, integration, or threshold tests,
this kind of drift is a bug factory.

`decimal(p, s)` stores exact base-10. Specifically used:

| Field          | Type           | Reason                                    |
|----------------|----------------|-------------------------------------------|
| voltage        | decimal(8,4)   | ±9999.9999 V — plenty for any electrochem |
| current        | decimal(14,6)  | μA with nA resolution                     |
| peak_current   | decimal(14,6)  | matches points table for direct compare   |
| battery_voltage| decimal(4,2)   | 0.00 – 99.99 V                            |
| latitude       | decimal(10,7)  | ~1cm precision                            |

### 4.3 Foreign key cascade strategy

| Relationship                            | On parent delete | Why                                          |
|-----------------------------------------|------------------|----------------------------------------------|
| `voltammetry_points` → `measurements`   | **CASCADE**      | Points are meaningless without their scan    |
| `measurements` → `devices`              | **RESTRICT**     | Don't lose historical data if device retired |
| `measurements` → `locations`            | **SET NULL**     | Location can be removed without data loss    |
| `device_logs` → `devices`               | **CASCADE**      | Telemetry only matters per active device     |

### 4.4 Indexes for realtime dashboard

```
measurements: (device_ref), (location_ref), (status), (method),
              (created_at), (status, created_at), (sample_id)
voltammetry_points: (measurement_id, sequence_number)
device_logs: (device_ref, created_at)
devices: (status), (last_online), unique(device_id)
locations: (kecamatan, desa)
```

The composite `(status, created_at)` index makes "positives today"
queries — which run on every dashboard tick — a single index scan.

### 4.5 How to optimize graph queries

```sql
SELECT sequence_number, voltage, current
FROM voltammetry_points
WHERE measurement_id = 47
ORDER BY sequence_number;
```

With the composite index, MySQL reads the rows directly off the index
in order — no filesort. For very large scans (10k+ points) on a
low-resolution chart, downsample at query time:

```sql
SELECT sequence_number, voltage, current
FROM voltammetry_points
WHERE measurement_id = 47
  AND sequence_number % 5 = 0
ORDER BY sequence_number;
```

---

## 5. API surface

All endpoints prefixed with `/api/v1`.

### Measurements

| Method | Path                          | Purpose                          |
|-------:|-------------------------------|----------------------------------|
| GET    | `/measurements`               | Paginated list (with filters)    |
| GET    | `/measurements/latest`        | Newest measurement (no points)   |
| GET    | `/measurements/{id}`          | Full detail with voltammogram    |
| GET    | `/measurements/{id}/graph`    | Points only (minimal payload)    |
| POST   | `/measurements`               | ESP32 ingest                     |

Filters supported on `/measurements`:
`?status=positive&method=DPV&device_id=POT-001&from=2026-05-01&to=2026-05-17&per_page=50`

### Devices

| Method | Path                          | Purpose                        |
|-------:|-------------------------------|--------------------------------|
| GET    | `/devices`                    | All devices + latest telemetry |
| POST   | `/devices`                    | Register new device            |
| GET    | `/devices/{id}`               | One device                     |
| GET    | `/devices/{id}/logs`          | Telemetry history              |

### Telemetry

| Method | Path                | Purpose                          |
|-------:|---------------------|----------------------------------|
| POST   | `/device-log`       | ESP32 heartbeat/telemetry        |

### Dashboard

| Method | Path                                | Purpose                       |
|-------:|-------------------------------------|-------------------------------|
| GET    | `/dashboard/summary`                | All KPIs in one call          |
| GET    | `/dashboard/positives`              | Recent positive detections    |
| GET    | `/dashboard/status-breakdown`       | Status counts today           |

---

## 6. Unified JSON response format

Every endpoint returns the same envelope:

```json
{
  "success": true,
  "message": "OK",
  "data": { ... }
}
```

On error:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": { "field": ["..."] }
}
```

This is enforced by `App\Support\ApiResponse` — no controller assembles
JSON by hand.

---

## 7. ESP32 integration

See **`ESP32_INTEGRATION.md`** for the full sample payloads, validation
error format, and the Arduino sketch (`esp32_sample/potentiostat_client.ino`).

Quick summary of the write path on the backend:

```
ESP32 POST /measurements (JSON)
  → StoreMeasurementRequest validates (rules + JSON error envelope)
  → MeasurementController calls MeasurementService::store()
  → Service opens transaction:
      1. Resolve device_id string → devices.id FK
      2. Insert measurement row
      3. Bulk-insert all voltammetry_points in chunks of 500
      4. Update device.last_online / status
  → MeasurementResource serializes the response
  → ApiResponse::created() wraps in the envelope
```

---

## 8. Future-ready architecture

The codebase is structured so each of these can be added without
rewriting existing logic:

- **Vue/Tailwind dashboard** — every endpoint is JSON-only with CORS
  config in `config/cors.php`. Drop in a Vue SPA at `/resources/js/`.
- **Realtime WebSocket** — add Laravel Reverb. Broadcast a
  `MeasurementStored` event from the end of `MeasurementService::store()`;
  the dashboard subscribes via Echo.
- **Mobile app** — same API. Auth via Sanctum tokens.
- **Docker deployment** — add a `docker-compose.yml` with `php-fpm`,
  `nginx`, `mysql`, `redis`. Nothing in the app code needs to change.
- **AWS deployment** — Laravel runs cleanly on EC2 + RDS, or on Lambda
  via Bref. Put the MySQL on RDS, the app on EC2/Fargate, and the
  static dashboard on S3 + CloudFront.
- **Cloud sync** — schedule a job that pushes `measurements` rows that
  haven't been synced yet to a remote endpoint. Add a `synced_at`
  column when needed.

---

## 9. Recommended next steps

1. **Add device authentication.** Either an `api_key` column on
   `devices` checked by a middleware on the ingest routes, or
   Laravel Sanctum tokens. The current code accepts any caller that
   knows a registered `device_id`.
2. **CORS** — edit `config/cors.php` to allow your dashboard origin.
3. **Rate limiting** — already on by default in Laravel 11+, but tune
   `RateLimiter::for('api', ...)` in `bootstrap/app.php` for IoT
   devices that may burst on reconnect.
4. **Logging** — point Laravel's logger at a dedicated channel for
   measurement ingest so you can audit field uploads separately.
5. **Soft deletes** — add `softDeletes()` to `measurements` and `devices`
   if you ever need an audit trail before purge.
