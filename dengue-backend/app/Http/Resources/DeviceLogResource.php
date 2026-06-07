<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'device_ref'      => $this->device_ref,
            'battery_percent' => $this->battery_percent,
            'battery_voltage' => $this->battery_voltage,
            'wifi_rssi'       => $this->wifi_rssi,
            'sd_status'       => $this->sd_status,
            'free_storage_mb' => $this->free_storage_mb,
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
