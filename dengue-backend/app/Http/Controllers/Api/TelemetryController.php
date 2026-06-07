<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceLogRequest;
use App\Http\Resources\DeviceLogResource;
use App\Models\Device;
use App\Models\DeviceLog;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

/**
 * Telemetry endpoints.
 *
 * Memberikan akses cepat ke telemetri device terbaru — tanpa harus
 * lewat /devices/{id}/logs. Frontend dashboard polling endpoint ini
 * setiap 2 detik.
 */
class TelemetryController extends Controller
{
    /**
     * GET /telemetry/latest
     *
     * Returns the most recent telemetry record from the most recently
     * active device, or per device_id if specified.
     *
     * Query params:
     *   - device_id (optional): filter ke device tertentu
     */
    public function latest(Request $request)
    {
        $query = DeviceLog::query()->with('device:id,device_id,status');

        if ($deviceIdString = $request->query('device_id')) {
            $device = Device::where('device_id', $deviceIdString)->first();
            if (! $device) {
                return ApiResponse::notFound("Device '$deviceIdString' tidak ditemukan");
            }
            $query->where('device_ref', $device->id);
        }

        $log = $query->latest('id')->first();

        if (! $log) {
            return ApiResponse::success(null, 'Belum ada telemetri yang masuk');
        }

        return ApiResponse::success(new DeviceLogResource($log));
    }

    /**
     * POST /telemetry
     *
     * Alias untuk POST /device-log. Disediakan supaya frontend pakai
     * URL yang konsisten dengan GET /telemetry/latest.
     */
    public function store(StoreDeviceLogRequest $request)
    {
        $data = $request->validated();

        $device = Device::where('device_id', $data['device_id'])->first();
        if (! $device) {
            return ApiResponse::notFound("Device '{$data['device_id']}' belum terdaftar");
        }

        $log = $device->deviceLogs()->create([
            'battery_voltage' => $data['battery_voltage']  ?? null,
            'battery_percent' => $data['battery_percent']  ?? null,
            'wifi_rssi'       => $data['wifi_rssi']        ?? null,
            'wifi_status'     => $data['wifi_status']      ?? null,
            'sd_status'       => $data['sd_status']        ?? null,
            'free_storage_mb' => $data['free_storage_mb']  ?? null,
            'temperature_c'   => $data['temperature_c']    ?? null,
            'humidity'        => $data['humidity']         ?? null,
            'current_ua'      => $data['current_ua']       ?? null,
            'potential_v'     => $data['potential_v']      ?? null,
            'state'           => $data['state']            ?? 'idle',
            'logged_at'       => now(),
        ]);

        $device->update(['last_online' => now()]);

        return ApiResponse::created(new DeviceLogResource($log), 'Telemetri tercatat');
    }
}
