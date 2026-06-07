# Feather M4 + AirLift ↔ Laravel API Integration

## Setup hardware

| Komponen              | Peran                                    |
|-----------------------|------------------------------------------|
| Adafruit Feather M4   | Mikrokontroler utama (SAMD51, 192 KB SRAM) |
| ESP32 AirLift FeatherWing | Koprosesor WiFi (firmware NINA, SPI ke M4) |
| Radiustat FeatherWing | Front-end potentiostat (ADC/DAC analog)  |

Susunan stack FeatherWing dari bawah: Feather M4 → AirLift → Radiustat
(atau bisa ditukar, asal pin SPI tidak konflik dengan pin yang dipakai
Radiustat).

## Pin AirLift pada Feather M4

| Sinyal AirLift | Pin Feather M4 |
|----------------|----------------|
| CS             | D13            |
| BUSY / ACK     | D11            |
| RESET          | D12            |
| GPIO0          | tidak dipakai  |
| MOSI/MISO/SCK  | hardware SPI bus |

Pin ini **wajib** dikonfigurasi di kode lewat `WiFi.setPins()` sebelum
pemanggilan WiFi API apa pun, kalau tidak `WiFi.status()` akan return
`WL_NO_MODULE` walau AirLift terpasang fisik.

## Library yang diperlukan

Install via Arduino IDE → **Sketch → Include Library → Manage Libraries**:

1. **WiFiNINA** (versi Adafruit lebih cocok untuk AirLift)
2. **ArduinoHttpClient**
3. **ArduinoJson** v6.x

> Catatan: Jangan pakai `WiFi.h` — itu untuk ESP32 standalone, bukan
> AirLift FeatherWing.

## Update firmware AirLift (jika perlu)

Cek versi firmware di Serial Monitor saat boot:

```
Firmware AirLift: 1.7.4
```

Kalau versi <1.7.4, update dulu — versi lama punya bug pada koneksi
HTTP panjang yang menyebabkan timeout di body besar. Panduan update:
[Adafruit AirLift firmware upgrade](https://learn.adafruit.com/upgrading-esp32-firmware).

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
  ]
}
```

### Response sukses (HTTP 201)

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

## Tips khusus untuk Feather M4 + AirLift

### RAM management

Feather M4 (SAMD51) punya 192 KB SRAM — cukup besar tapi `DynamicJsonDocument`
yang terlalu generous bisa men-fragment heap. Hitung kapasitas berdasarkan
ukuran sebenarnya: ~60 byte per titik + ~600 byte metadata. Untuk 500 titik:
`60 * 500 + 600 = 30.6 KB`. Aman.

### SPI bus sharing

Kalau Radiustat juga pakai SPI (untuk ADC eksternal misalnya), pastikan
chip select pin-nya **berbeda** dari D13 (CS AirLift). Saat satu device
sedang dipakai, yang lain CS-nya harus HIGH.

### Timeout HTTP

`ArduinoHttpClient` default timeout 30 detik. Untuk scan besar (1000+
titik) di koneksi WiFi lemah, naikkan dengan:

```cpp
http.setHttpResponseTimeout(60000); // 60 detik
```

### Networking development

Saat develop, point `API_HOST` ke IP LAN laptop Laragon Anda (bukan
`localhost` atau `127.0.0.1` — itu loopback yang tidak bisa diakses
dari luar laptop). Cari IP dengan `ipconfig` di Windows, cari "IPv4
Address" di adapter WiFi (biasanya `192.168.1.x` atau `192.168.0.x`).

Lalu jalankan Laravel dengan flag host eksplisit:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

`--host=0.0.0.0` memberitahu Laravel untuk listen di semua network
interface — tanpa ini, Feather M4 di WiFi yang sama tetap tidak bisa
konek karena server hanya bind ke loopback.

### Firewall Windows

Saat pertama kali jalankan `artisan serve`, Windows Firewall akan
nanya izin — pilih **Allow on Private networks**. Kalau di-dismiss,
ESP32 akan timeout terus. Cek/edit di **Control Panel → Windows
Defender Firewall → Allow an app**.

## Endpoint untuk Vue dashboard (next phase)

| Method | Endpoint                              | Fungsi                                  |
|-------:|---------------------------------------|----------------------------------------|
| GET    | `/api/v1/dashboard/summary`           | Semua KPI dalam satu payload            |
| GET    | `/api/v1/dashboard/positives`         | 10 deteksi positif terbaru              |
| GET    | `/api/v1/dashboard/status-breakdown`  | Hitungan status hari ini                |
| GET    | `/api/v1/measurements/latest`         | Measurement terbaru (tanpa points)      |
| GET    | `/api/v1/measurements?status=positive&from=2026-05-01` | Filter list           |
| GET    | `/api/v1/measurements/{id}`           | Detail penuh + voltammogram             |
| GET    | `/api/v1/measurements/{id}/graph`     | Voltammogram saja (payload minim)       |
| GET    | `/api/v1/devices`                     | Semua device + telemetri terbaru        |
| GET    | `/api/v1/devices/{id}/logs`           | Histori telemetri satu device           |

## Langkah berikutnya: device authentication

Saat ini API mempercayai siapa pun yang tahu `device_id` terdaftar.
Sebelum deployment, tambahkan salah satu:

1. **API key per device** — tambah kolom `api_key` di tabel `devices`,
   generate token random saat registrasi, validasi di middleware via
   header `X-Device-Key`. Lebih simpel di firmware.
2. **Laravel Sanctum** — issue token per device, lindungi route ingest
   dengan `auth:sanctum`. Integrasi lebih bersih dengan login user di
   Vue dashboard nanti.

Opsi 1 lebih ringan untuk embedded client; opsi 2 lebih kuat untuk
sistem produksi multi-user.
