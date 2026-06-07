#!/usr/bin/env python3
"""
============================================================================
 simulate_stream.py — Simulator ALAT potentiostat (streaming realtime)
============================================================================
 Meniru perilaku alat asli:
   1. POST /measurements/start            -> dapat measurement_id
   2. POST /measurements/{id}/points      -> kirim batch titik + progress
   3. POST /measurements/{id}/finish      -> kirim hasil akhir
   + POST /telemetry tiap ~1 detik (baterai, sinyal, suhu)

 Tujuan: uji pipeline realtime TANPA alat asli. Saat alat asli siap,
 alat tinggal mengirim JSON dengan format yang SAMA ke endpoint yang sama.

 Cara pakai:
   pip install requests
   python simulate_stream.py
   (lalu buka halaman Measuring di dashboard untuk lihat grafik tumbuh)

 Ganti BASE bila backend Anda di alamat lain.
============================================================================
"""
import time
import math
import random
import requests

BASE = "http://127.0.0.1:8000/api/v1"   # ganti bila perlu (mis. port Docker)
DEVICE_ID = "POT-001"
TOTAL_POINTS = 160
BATCH_SIZE = 8           # kirim 8 titik per request (bukan 1) -> stabil
START_V, END_V = -0.2, 0.6
PEAK_V, PEAK_SIZE, SIGMA = 0.185, 13.0, 0.04
THRESHOLD = 8.0


def dpv_current(v):
    """Bentuk kurva DPV: baseline + puncak Gaussian + sedikit noise."""
    peak = PEAK_SIZE * math.exp(-((v - PEAK_V) ** 2) / (2 * SIGMA ** 2))
    baseline = 1.2 + 0.3 * v
    noise = (random.random() - 0.5) * 0.04
    return round(baseline + peak + noise, 6)


def send_telemetry(state="scanning", progress=0):
    payload = {
        "device_id": DEVICE_ID,
        "battery_percent": 82,
        "battery_voltage": 3.92,
        "wifi_rssi": -58,
        "wifi_status": "connected",
        "sd_status": "ok",
        "free_storage_mb": 1450,
        "temperature_c": round(30 + random.random(), 1),
        "humidity": 65,
        "state": state,
    }
    try:
        requests.post(f"{BASE}/telemetry", json=payload, timeout=4)
    except Exception as e:
        print("  ! telemetry gagal:", e)


def run_scan():
    # 1) START
    print("→ start scan ...")
    r = requests.post(f"{BASE}/measurements/start", json={
        "device_id": DEVICE_ID,
        "method": "DPV",
        "threshold": THRESHOLD,
        # "location_id": 1,   # aktifkan bila ingin set lokasi sampel
    }, timeout=6)
    r.raise_for_status()
    mid = r.json()["data"]["measurement_id"]
    print(f"  measurement_id = {mid}")

    # 2) STREAM POINTS dalam batch
    step = (END_V - START_V) / TOTAL_POINTS
    buffer = []
    peak_i, peak_v = -1e9, 0.0

    for n in range(TOTAL_POINTS):
        v = round(START_V + n * step, 4)
        i = dpv_current(v)
        if i > peak_i:
            peak_i, peak_v = i, v
        buffer.append({"sequence_number": n, "voltage": v, "current": i})

        if len(buffer) >= BATCH_SIZE or n == TOTAL_POINTS - 1:
            progress = int(((n + 1) / TOTAL_POINTS) * 100)
            try:
                requests.post(f"{BASE}/measurements/{mid}/points", json={
                    "points": buffer,
                    "progress": progress,
                }, timeout=6)
                print(f"  kirim {len(buffer)} titik  (progress {progress}%)")
            except Exception as e:
                print("  ! points gagal:", e)
            buffer = []
            send_telemetry("scanning", progress)
            time.sleep(0.5)   # jeda antar batch -> grafik terlihat tumbuh

    # 3) FINISH
    status = "positive" if peak_i > THRESHOLD else (
        "warning" if peak_i > THRESHOLD * 0.5 else "negative")
    requests.post(f"{BASE}/measurements/{mid}/finish", json={
        "peak_current": round(peak_i, 6),
        "peak_voltage": round(peak_v, 4),
        "status": status,
        "start_voltage": START_V,
        "end_voltage": END_V,
        "step_voltage": round(step, 4),
        "scan_rate": 0.05,
        "pulse_amplitude": 0.025,
        "duration_seconds": int(TOTAL_POINTS / BATCH_SIZE * 0.5),
    }, timeout=6)
    print(f"✓ selesai — status={status}, peak={peak_i:.3f} µA @ {peak_v} V")


if __name__ == "__main__":
    print("Simulator alat potentiostat (streaming). Ctrl+C untuk berhenti.\n")
    # idle telemetry sebentar, lalu scan
    send_telemetry("idle")
    time.sleep(1)
    run_scan()
    # telemetri idle setelah selesai
    send_telemetry("idle", 100)
    print("\nSelesai. Jalankan lagi untuk scan berikutnya.")
