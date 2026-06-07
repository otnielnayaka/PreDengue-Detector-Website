/*
 * Dengue NS1 Potentiostat — backend client (Feather M4 + ESP32 AirLift)
 *
 * Sends a completed voltammetry scan to the Laravel backend as one
 * POST /api/v1/measurements request, then periodically sends telemetry
 * to POST /api/v1/device-log.
 *
 * Libraries required (Arduino Library Manager):
 *   - WiFiNINA          (for ESP32 AirLift FeatherWing)
 *   - ArduinoHttpClient
 *   - ArduinoJson       (v6.x)
 *
 * NOTE: For payloads with hundreds of points, increase the JSON capacity
 * via DynamicJsonDocument and free RAM. The Feather M4 (SAMD51) has
 * 192 KB SRAM which comfortably handles ~500 points.
 */

#include <SPI.h>
#include <WiFiNINA.h>
#include <ArduinoHttpClient.h>
#include <ArduinoJson.h>

// ---- Config ----
const char* WIFI_SSID     = "YOUR_SSID";
const char* WIFI_PASSWORD = "YOUR_PASSWORD";

const char* API_HOST  = "192.168.1.10";   // your Laragon host LAN IP
const int   API_PORT  = 8000;             // `php artisan serve` default
const char* API_BASE  = "/api/v1";
const char* DEVICE_ID = "POT-001";

WiFiClient   wifi;
HttpClient   http = HttpClient(wifi, API_HOST, API_PORT);

// ---- Sample scan buffers ----
// Replace with your actual SCPE/Radiustat results
const int   NUM_POINTS = 160;
float voltage_buf[NUM_POINTS];
float current_buf[NUM_POINTS];

void connectWifi() {
  while (WiFi.status() != WL_CONNECTED) {
    Serial.print("Connecting to WiFi... ");
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
    delay(3000);
  }
  Serial.print("Connected. RSSI=");
  Serial.println(WiFi.RSSI());
}

// -------- POST /api/v1/measurements --------
bool postMeasurement(const char* sampleId,
                     const char* method,
                     float peakCurrent, float peakVoltage,
                     float startV, float endV, float stepV,
                     float scanRate, float pulseAmp,
                     int durationSec, const char* status)
{
  // Capacity: ~50 bytes per point + ~600 metadata. Tune as needed.
  DynamicJsonDocument doc(60 + (NUM_POINTS * 60));

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

  int statusCode = http.responseStatusCode();
  String response = http.responseBody();
  Serial.print("Measurement POST status: "); Serial.println(statusCode);
  Serial.println(response);
  return statusCode == 201;
}

// -------- POST /api/v1/device-log --------
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

void setup() {
  Serial.begin(115200);
  while (!Serial && millis() < 3000) {}
  connectWifi();

  // ---- Fill demo buffers (replace with real measurement) ----
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
  // Telemetry heartbeat every 60s
  postTelemetry(82, 3.92f, WiFi.RSSI(), "ok", 1450);
  delay(60000);
}
