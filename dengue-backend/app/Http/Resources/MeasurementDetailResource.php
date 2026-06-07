<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Detailed measurement resource — includes voltammogram points.
 *
 * Dipakai oleh endpoint:
 *   - GET /measurements/latest
 *   - GET /measurements/{id}
 *   - POST /measurements (return)
 *
 * Untuk list (paginated), pakai MeasurementResource (tanpa points).
 */
class MeasurementDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'sample_id'        => $this->sample_id,
            'method'           => $this->method,

            // Scientific values
            'peak_current'     => (float) $this->peak_current,
            'peak_voltage'     => (float) $this->peak_voltage,
            'delta_tia'        => (float) $this->delta_tia,
            'threshold'        => (float) $this->threshold,

            // Scan parameters
            'start_voltage'    => (float) $this->start_voltage,
            'end_voltage'      => (float) $this->end_voltage,
            'step_voltage'     => (float) $this->step_voltage,
            'scan_rate'        => (float) $this->scan_rate,
            'pulse_amplitude'  => (float) $this->pulse_amplitude,
            'duration_seconds' => (int) $this->duration_seconds,

            // Result
            'status'           => $this->status,

            // Relations
            'device'           => $this->whenLoaded('device', fn () => [
                'id'        => $this->device->id,
                'device_id' => $this->device->device_id,
                'status'    => $this->device->status,
            ]),
            'location'         => $this->whenLoaded('location', fn () => $this->location ? [
                'id'        => $this->location->id,
                'kecamatan' => $this->location->kecamatan,
                'desa'      => $this->location->desa,
            ] : null),

            // Voltammogram points — array of {sequence_number, voltage, current}
            'points'           => $this->whenLoaded('points', fn () =>
                $this->points->map(fn ($p) => [
                    'sequence_number' => (int) $p->sequence_number,
                    'voltage'         => (float) $p->voltage,
                    'current'         => (float) $p->current,
                ])
            ),

            // Timestamps
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
