<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Measurement;
use App\Models\DeviceLog;
use Illuminate\Http\JsonResponse;

/**
 * MapController
 * -------------
 * Data untuk halaman Map Monitoring (Leaflet).
 * Endpoint: GET /api/v1/map/measurements
 *
 * ARSITEKTUR: alat portable tunggal, lokasi melekat pada MEASUREMENT.
 * Peta menandai LOKASI SAMPEL. Untuk tiap lokasi yang memiliki koordinat
 * DAN minimal satu pengukuran, dikembalikan pengukuran TERBARU di lokasi itu.
 *
 * Marker warna ditentukan frontend berdasarkan field "result" (= status).
 *
 * CATATAN nama kolom (sesuaikan bila beda):
 *   - Measurement: location_ref (FK), status, peak_current, peak_voltage, created_at
 *   - DeviceLog  : battery_voltage / battery_percent (untuk info baterai alat)
 *   - Location   : kecamatan, desa, latitude, longitude
 */
class MapController extends Controller
{
    public function measurements(): JsonResponse
    {
        // Baterai alat (alat tunggal): ambil telemetri terbaru sekali saja.
        $lastLog = DeviceLog::query()->latest('id')->first();
        $battery = $lastLog->battery_voltage ?? $lastLog->battery_percent ?? null;

        $locations = Location::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $data = $locations->map(function (Location $loc) use ($battery) {
            // Pengukuran terbaru di lokasi ini
            $m = Measurement::query()
                ->where('location_ref', $loc->id)
                ->latest('id')
                ->first();

            if (!$m) {
                return null; // lokasi tanpa pengukuran tidak ditampilkan
            }

            $count = Measurement::query()->where('location_ref', $loc->id)->count();

            return [
                'device_id'    => $m->device->device_id ?? null,
                'result'       => $m->status,                  // positive | negative | ...
                'peak_current' => (float) $m->peak_current,
                'peak_voltage' => (float) $m->peak_voltage,
                'battery'      => $battery !== null ? (float) $battery : null,
                'kecamatan'    => $loc->kecamatan,
                'desa'         => $loc->desa,
                'latitude'     => (float) $loc->latitude,
                'longitude'    => (float) $loc->longitude,
                'sample_count' => $count,
                'updated_at'   => optional($m->created_at)->format('Y-m-d H:i:s'),
            ];
        })->filter()->values();

        return response()->json([
            'success' => true,
            'message' => 'Map measurements retrieved',
            'data'    => $data,
        ]);
    }
}
