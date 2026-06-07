<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceLogRequest;
use App\Http\Resources\DeviceLogResource;
use App\Models\Device;
use App\Models\DeviceLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceLogController extends Controller
{
    /**
     * POST /api/device-log
     * Lightweight heartbeat/telemetry from the ESP32.
     */
    public function store(StoreDeviceLogRequest $request): JsonResponse
    {
        $data   = $request->validated();
        $device = Device::where('device_id', $data['device_id'])->firstOrFail();

        $log = DeviceLog::create([
            'device_ref'      => $device->id,
            'battery_percent' => $data['battery_percent'] ?? null,
            'battery_voltage' => $data['battery_voltage'] ?? null,
            'wifi_rssi'       => $data['wifi_rssi']       ?? null,
            'sd_status'       => $data['sd_status']       ?? null,
            'free_storage_mb' => $data['free_storage_mb'] ?? null,
        ]);

        // Mirror latest battery/wifi state onto the device for fast dashboard reads
        $device->update([
            'battery_voltage' => $data['battery_voltage'] ?? $device->battery_voltage,
            'last_online'     => now(),
            'status'          => 'online',
            'wifi_status'     => $this->classifyWifi($data['wifi_rssi'] ?? null),
        ]);

        return ApiResponse::created(new DeviceLogResource($log), 'Telemetry stored');
    }

    /**
     * GET /api/devices/{device}/logs
     */
    public function indexForDevice(Device $device, Request $request): JsonResponse
    {
        $logs = $device->deviceLogs()
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return ApiResponse::success(DeviceLogResource::collection($logs)->response()->getData(true));
    }

    private function classifyWifi(?int $rssi): ?string
    {
        if ($rssi === null) return null;
        if ($rssi >= -65)   return 'connected';
        if ($rssi >= -80)   return 'weak';
        return 'poor';
    }
}
