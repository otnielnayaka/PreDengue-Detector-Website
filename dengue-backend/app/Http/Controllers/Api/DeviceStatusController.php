<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use App\Support\ApiResponse;

/**
 * Endpoint khusus untuk widget "Device Status" di dashboard.
 *
 * Mengembalikan ringkasan status semua device — bukan list lengkap,
 * cukup id + status + last_online + telemetri terakhir.
 */
class DeviceStatusController extends Controller
{
    /**
     * GET /devices/status
     */
    public function __invoke()
    {
        $devices = Device::query()
            ->with(['latestLog' => function ($q) {
                $q->select(
                    'id', 'device_id', 'battery_percent', 'wifi_rssi',
                    'state', 'logged_at'
                );
            }])
            ->get();

        // Hitung agregat untuk widget ringkas
        $summary = [
            'total'   => $devices->count(),
            'online'  => $devices->where('status', 'online')->count(),
            'offline' => $devices->where('status', 'offline')->count(),
            'weak'    => $devices->where('status', 'weak')->count(),
        ];

        return ApiResponse::success([
            'summary' => $summary,
            'devices' => DeviceResource::collection($devices),
        ]);
    }
}
