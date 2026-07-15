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

            // Summary khusus CV — null kalau memang belum tersedia/tidak
            // bisa dihitung (tidak pernah diisi angka karangan).
            'anodic_peak_current'   => $this->anodic_peak_current !== null ? (float) $this->anodic_peak_current : null,
            'cathodic_peak_current' => $this->cathodic_peak_current !== null ? (float) $this->cathodic_peak_current : null,
            'anodic_peak_voltage'   => $this->anodic_peak_voltage !== null ? (float) $this->anodic_peak_voltage : null,
            'cathodic_peak_voltage' => $this->cathodic_peak_voltage !== null ? (float) $this->cathodic_peak_voltage : null,
            'max_current'           => $this->max_current !== null ? (float) $this->max_current : null,
            'min_current'           => $this->min_current !== null ? (float) $this->min_current : null,
            'max_abs_current'       => $this->max_abs_current !== null ? (float) $this->max_abs_current : null,

            // Scan parameters
            'start_voltage'    => (float) $this->start_voltage,
            'end_voltage'      => (float) $this->end_voltage,
            'step_voltage'     => (float) $this->step_voltage,
            'scan_rate'        => (float) $this->scan_rate,
            'pulse_amplitude'  => (float) $this->pulse_amplitude,
            'duration_seconds' => (int) $this->duration_seconds,
            // Khusus CV — null untuk DPV/SWV.
            'cycles'            => $this->cycles !== null ? (int) $this->cycles : null,
            'quiet_time'        => $this->quiet_time !== null ? (float) $this->quiet_time : null,
            'sensitivity_range' => $this->sensitivity_range,

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

            // Voltammogram points — array of {sequence_number, voltage, current,
            // cycle, direction, time_seconds}. cycle/direction/time_seconds null
            // untuk DPV/SWV lama; diisi untuk CV kalau alat mengirimnya.
            'points'           => $this->whenLoaded('points', fn () =>
                $this->points->map(fn ($p) => [
                    'sequence_number' => (int) $p->sequence_number,
                    'voltage'         => (float) $p->voltage,
                    'current'         => (float) $p->current,
                    'cycle'           => $p->cycle !== null ? (int) $p->cycle : null,
                    'direction'       => $p->direction,
                    'time_seconds'    => $p->time_seconds !== null ? (float) $p->time_seconds : null,
                ])
            ),

            // Timestamps
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
