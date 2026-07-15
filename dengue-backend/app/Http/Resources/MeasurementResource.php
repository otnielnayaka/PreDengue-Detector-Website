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

            // Summary khusus CV — null untuk DPV/SWV, tidak pernah dikarang.
            'anodic_peak_current'   => $this->anodic_peak_current,
            'cathodic_peak_current' => $this->cathodic_peak_current,
            'anodic_peak_voltage'   => $this->anodic_peak_voltage,
            'cathodic_peak_voltage' => $this->cathodic_peak_voltage,
            'max_current'           => $this->max_current,
            'min_current'           => $this->min_current,
            'max_abs_current'       => $this->max_abs_current,

            'scan' => [
                'start_voltage'   => $this->start_voltage,
                'end_voltage'     => $this->end_voltage,
                'step_voltage'    => $this->step_voltage,
                'scan_rate'       => $this->scan_rate,
                'pulse_amplitude' => $this->pulse_amplitude,
                'duration_seconds'=> $this->duration_seconds,
                // Khusus CV — null untuk DPV/SWV.
                'cycles'            => $this->cycles,
                'quiet_time'        => $this->quiet_time,
                'sensitivity_range' => $this->sensitivity_range,
            ],

            'device'   => new DeviceResource($this->whenLoaded('device')),
            'location' => new LocationResource($this->whenLoaded('location')),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
