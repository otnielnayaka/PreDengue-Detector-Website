<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight measurement view — used for lists, dashboards, latest endpoints.
 * Does NOT include voltammetry points (use MeasurementDetailResource for that).
 */
class MeasurementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'sample_id'        => $this->sample_id,
            'method'           => $this->method,
            'status'           => $this->status,

            'peak_current'     => $this->peak_current,
            'peak_voltage'     => $this->peak_voltage,
            'delta_tia'        => $this->delta_tia,
            'threshold'        => $this->threshold,

            'scan' => [
                'start_voltage'   => $this->start_voltage,
                'end_voltage'     => $this->end_voltage,
                'step_voltage'    => $this->step_voltage,
                'scan_rate'       => $this->scan_rate,
                'pulse_amplitude' => $this->pulse_amplitude,
                'duration_seconds'=> $this->duration_seconds,
            ],

            'device'   => new DeviceResource($this->whenLoaded('device')),
            'location' => new LocationResource($this->whenLoaded('location')),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
