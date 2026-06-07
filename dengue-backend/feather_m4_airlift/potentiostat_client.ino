/*
 * Dengue NS1 Potentiostat — backend client
 * Hardware: Adafruit Feather M4 Express (SAMD51) + ESP32 AirLift FeatherWing
 *
 * Catatan arsitektur:
 *   Kode ini dijalankan oleh Feather M4 (SAMD51). AirLift FeatherWing
 *   hanyalah modul WiFi koprosesor — ESP32 di dalamnya menjalankan
 *   firmware NINA dan dikontrol via SPI dari Feather M4. Karena itu
 *   library yang dipakai adalah WiFiNINA, BUKAN library "WiFi.h"
 *   yang umum untuk ESP32 standalone.
 *
 * Fungsi:
 *   - POST satu hasil scan voltammetry ke /api/v1/measurements
 *   - POST telemetri (battery, RSSI, SD) tiap 60 detik ke /api/v1/device-log
 *
 * Library yang dibutuhkan (Arduino Library Manager):
 *   - WiFiNINA           (Adafruit fork lebih stabil untuk AirLift)
 *   - ArduinoHttpClient
 *   - ArduinoJson        (v6.x)
 *
 * RAM: Feather M4 punya 192 KB SRAM — cukup untuk scan ~500 titik.
 */

#include <SPI.h>
#include <WiFiNINA.h>
#include <ArduinoHttpClient.h>
#include <ArduinoJson.h>

// ===== Konfigurasi pin AirLift FeatherWing =====
// WAJIB diset SEBELUM WiFi.begin(), kalau tidak WiFiNINA akan
// melaporkan "WiFi shield not present" walau hardware sudah nempel.
#define SPIWIFI       SPI
#define SPIWIFI_SS    13   // chip select
#define SPIWIFI_ACK   11   // busy/ready
#define SPIWIFI_RESET 12   // reset
#define ESP32_GPIO0   -1   // tidak dipakai pada AirLift

// ===== Konfigurasi jaringan & backend =====
const char* WIFI_SSID     = "GANTI_SSID_ANDA";
const char* WIFI_PASSWORD = "GANTI_PASSWORD_ANDA";

const char* API_HOST  = "192.168.1.10";   // IP LAN laptop Laragon
const int   API_PORT  = 8000;             // port `php artisan serve`
const char* API_BASE  = "/api/v1";
const char* DEVICE_ID = "POT-001";

WiFiClient wifi;
HttpClient http = HttpClient(wifi, API_HOST, API_PORT);

// ===== Buffer hasil scan =====
// Ganti dengan hasil aktual dari Radiustat / SCPE
const int NUM_POINTS = 160;
float voltage_buf[NUM_POINTS];
float current_buf[NUM_POINTS];

// -------------------------------------------------------------
// Inisialisasi WiFi via AirLift
// -------------------------------------------------------------
void connectWifi() {
  // Inisialisasi pin AirLift — WAJIB sebelum WiFi.status()
  WiFi.setPins(SPIWIFI_SS, SPIWIFI_ACK, SPIWIFI_RESET, ESP32_GPIO0, &SPIWIFI);

  if (WiFi.status() == WL_NO_MODULE) {
    Serial.println("AirLift tidak terdeteksi. Cek koneksi FeatherWing.");
    while (true) delay(1000);
  }

  Serial.print("Firmware AirLift: ");
  Serial.println(WiFi.firmwareVersion());

  while (WiFi.status() != WL_CONNECTED) {
    Serial.print("Connecting ke "); Serial.print(WIFI_SSID); Serial.println(" ...");
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    delay(3000);
  }

  Serial.print("Terhubung. IP=");
  Serial.print(WiFi.localIP());
  Serial.print(" RSSI=");
  Serial.println(WiFi.RSSI());
}

// -------------------------------------------------------------
// POST /api/v1/measurements — upload satu scan voltammetry penuh
// -------------------------------------------------------------
bool postMeasurement(const char* sampleId,
                     const char* method,
                     float peakCurrent, float peakVoltage,
                     float startV, float endV, float stepV,
                     float scanRate, float pulseAmp,
                     int durationSec, const char* status)
{
  // Kapasitas: ~60 byte per titik + ~600 byte metadata
  DynamicJsonDocument doc(800 + (NUM_POINTS * 60));

  doc["device_id"]        = DEVICE_ID;
  doc["sample_id"]        = sampleId;
  doc["method"]           = method;
  doc["peak_current"]     = peakCurrent;
  doc["peak_voltage"]     = peakVoltage;
  doc["start_voltage"]    = startV;
  doc["end_voltage"]      = endV;
  doc["step_voltage"]     = stepV;
  doc["scan_rate"]        = scanRate;
  doc["pulse_amplitude"]  = pulseAmp;
  doc["duration_seconds"] = durationSec;
  doc["status"]           = status;

  JsonArray points = doc.createNestedArray("points");
  for (int i = 0; i < NUM_POINTS; i++) {
    JsonObject p = points.createNestedObject();
    p["sequence_number"] = i;
    p["voltage"]         = voltage_buf[i];
    p["current"]         = current_buf[i];
  }

  String body;
  serializeJson(doc, body);

  http.beginRequest();
  http.post(String(API_BASE) + "/measurements");
  http.sendHeader("Content-Type", "application/json");
  http.sendHeader("Accept", "application/json");
  http.sendHeader("Content-Length", body.length());
  http.beginBody();
  http.print(body);
  http.endRequest();

  int code = http.responseStatusCode();
  String response = http.responseBody();
  Serial.print("Measurement POST status: "); Serial.println(code);
  Serial.println(response);
  return code == 201;
}

// -------------------------------------------------------------
// POST /api/v1/device-log — telemetri ringan
// -------------------------------------------------------------
bool postTelemetry(int batteryPct, float batteryV, int rssi,
                   const char* sdStatus, int freeStorageMb)
{
  StaticJsonDocument<256> doc;
  doc["device_id"]       = DEVICE_ID;
  doc["battery_percent"] = batteryPct;
  doc["battery_voltage"] = batteryV;
  doc["wifi_rssi"]       = rssi;
  doc["sd_status"]       = sdStatus;
  doc["free_storage_mb"] = freeStorageMb;

  String body;
  serializeJson(doc, body);

  http.beginRequest();
  http.post(String(API_BASE) + "/device-log");
  http.sendHeader("Content-Type", "application/json");
  http.sendHeader("Accept", "application/json");
  http.sendHeader("Content-Length", body.length());
  http.beginBody();
  http.print(body);
  http.endRequest();

  int code = http.responseStatusCode();
  Serial.print("Telemetry POST status: "); Serial.println(code);
  return code == 201;
}

// -------------------------------------------------------------
void setup() {
  Serial.begin(115200);
  while (!Serial && millis() < 3000) {}

  connectWifi();

  // Demo: isi buffer dengan data sintetis.
  // Ganti dengan hasil pembacaan Radiustat/SCPE Anda.
  for (int i = 0; i < NUM_POINTS; i++) {
    voltage_buf[i] = -0.2f + (i * 0.005f);
    current_buf[i] = 1.2f + 0.3f * voltage_buf[i];
  }

  postMeasurement("NS1-FIELD-001", "DPV",
                  /*peakCurrent*/ 12.456f, /*peakVoltage*/ 0.185f,
                  /*startV*/ -0.20f, /*endV*/ 0.60f, /*stepV*/ 0.005f,
                  /*scanRate*/ 0.050f, /*pulseAmp*/ 0.025f,
                  /*duration*/ 32, /*status*/ "positive");
}

void loop() {
  // Heartbeat telemetri tiap 60 detik
  postTelemetry(82, 3.92f, WiFi.RSSI(), "ok", 1450);
  delay(60000);
}
