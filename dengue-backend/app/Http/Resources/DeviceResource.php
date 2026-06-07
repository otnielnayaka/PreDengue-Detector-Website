<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'device_id'         => $this->device_id,
            'firmware_version'  => $this->firmware_version,
            'battery_voltage'   => $this->battery_voltage,
            'wifi_status'       => $this->wifi_status,
            'status'            => $this->status,
            'last_online'       => $this->last_online?->toIso8601String(),
            'latest_log'        => $this->whenLoaded('latestLog', fn () => new DeviceLogResource($this->latestLog)),
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }
}
