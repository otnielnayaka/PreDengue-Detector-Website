# ESP32 ↔ Laravel API Integration

## Sample POST payload — Measurement

`POST /api/v1/measurements`

```json
{
  "device_id": "POT-001",
  "location_id": 1,
  "sample_id": "NS1-FIELD-001",
  "method": "DPV",
  "peak_current": 12.456789,
  "peak_voltage": 0.1850,
  "delta_tia": 1.0,
  "threshold": 8.0,
  "start_voltage": -0.2,
  "end_voltage": 0.6,
  "step_voltage": 0.005,
  "scan_rate": 0.05,
  "pulse_amplitude": 0.025,
  "duration_seconds": 32,
  "status": "positive",
  "points": [
    { "sequence_number": 0, "voltage": -0.2000, "current": 1.140000 },
    { "sequence_number": 1, "voltage": -0.1950, "current": 1.141500 },
    { "sequence_number": 2, "voltage": -0.1900, "current": 1.143000 }
    // ... up to 5000 points
  ]
}
```

### Success response (HTTP 201)

```json
{
  "success": true,
  "message": "Measurement stored",
  "data": {
    "id": 47,
    "sample_id": "NS1-FIELD-001",
    "method": "DPV",
    "status": "positive",
    "peak_current": "12.456789",
    "peak_voltage": "0.1850",
    "scan": {
      "start_voltage": "-0.2000",
      "end_voltage": "0.6000",
      "step_voltage": "0.0050",
      "scan_rate": "0.0500",
      "pulse_amplitude": "0.0250",
      "duration_seconds": 32
    },
    "device": { "id": 1, "device_id": "POT-001", "status": "online" },
    "location": { "id": 1, "kecamatan": "Coblong", "desa": "Dago" },
    "created_at": "2026-05-17T14:23:11+07:00"
  }
}
```

### Validation error (HTTP 422)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "method": ["The selected method is invalid."],
    "points": ["The points field is required."]
  }
}
```

---

## Sample POST payload — Telemetry

`POST /api/v1/device-log`

```json
{
  "device_id": "POT-001",
  "battery_percent": 82,
  "battery_voltage": 3.92,
  "wifi_rssi": -58,
  "sd_status": "ok",
  "free_storage_mb": 1450
}
```

### Response (HTTP 201)

```json
{
  "success": true,
  "message": "Telemetry stored",
  "data": {
    "id": 312,
    "device_ref": 1,
    "battery_percent": 82,
    "battery_voltage": "3.92",
    "wifi_rssi": -58,
    "sd_status": "ok",
    "free_storage_mb": 1450,
    "created_at": "2026-05-17T14:23:51+07:00"
  }
}
```

---

## Dashboard endpoints (for the Vue frontend)

| Method | Endpoint                              | Purpose                                  |
|-------:|---------------------------------------|------------------------------------------|
| GET    | `/api/v1/dashboard/summary`           | All KPIs in one payload                  |
| GET    | `/api/v1/dashboard/positives`         | Last 10 positive detections              |
| GET    | `/api/v1/dashboard/status-breakdown`  | Status counts today (for pie/bar chart)  |
| GET    | `/api/v1/measurements/latest`         | Most recent measurement (no points)      |
| GET    | `/api/v1/measurements?status=positive&from=2026-05-01` | Filtered list |
| GET    | `/api/v1/measurements/{id}`           | Full measurement with voltammogram       |
| GET    | `/api/v1/measurements/{id}/graph`     | Voltammogram only (minimal payload)      |
| GET    | `/api/v1/devices`                     | All devices + last telemetry             |
| GET    | `/api/v1/devices/{id}/logs`           | Telemetry history for one device         |

---

## ESP32 networking notes (Feather M4 + AirLift)

- Use `WiFiNINA.h` (the AirLift uses the same firmware as the Nina-W102).
- Keep `Content-Length` exact — `ArduinoHttpClient` does not auto-set it
  for streamed bodies on every platform.
- For large scans, use `DynamicJsonDocument` not `StaticJsonDocument` to
  avoid blowing the stack.
- During development, point `API_HOST` to your Laragon machine's LAN IP
  (not `localhost` / `127.0.0.1`) and start Laravel with
  `php artisan serve --host=0.0.0.0 --port=8000`.

## Recommended next step: device authentication

Currently the API trusts any caller that knows a registered `device_id`.
Before deployment, add either:

1. **API key per device** — extend the `devices` table with an `api_key`
   column, generate a random token on registration, and require it in
   an `X-Device-Key` header via a lightweight middleware.
2. **Laravel Sanctum** — issue a token per device on registration and
   protect the ingest routes with `auth:sanctum`.

Option 1 is simpler for embedded clients; option 2 integrates cleanly
with the future Vue dashboard's user login.
