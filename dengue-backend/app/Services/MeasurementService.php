<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Measurement;
use Illuminate\Support\Facades\DB;

/**
 * Encapsulates the write path for a measurement + its voltammetry points.
 *
 * Why a Service (not in the Controller):
 *   - Bulk insert of points must run inside a transaction with the measurement.
 *   - We resolve device_id (hardware string) → device_ref (FK), which is
 *     business logic and doesn't belong in HTTP layer.
 *   - The same logic will be reused by future channels (MQTT ingest worker,
 *     batch import command, etc.).
 */
class MeasurementService
{
    /**
     * Persist a measurement and its voltammogram in one transaction.
     *
     * @param array $data  Validated payload from StoreMeasurementRequest
     */
    public function store(array $data): Measurement
    {
        $device = Device::where('device_id', $data['device_id'])->firstOrFail();
        $isCv = $data['method'] === 'CV';

        return DB::transaction(function () use ($device, $data, $isCv) {
            // CV: kalau dashboard/alat tidak mengirim summary current, hitung
            // dari points yang dikirim (agregat sederhana, bukan deteksi peak
            // yang butuh asumsi domain — sama seperti StreamController::finish()).
            $maxCurrent = $data['max_current'] ?? null;
            $minCurrent = $data['min_current'] ?? null;
            $maxAbsCurrent = $data['max_abs_current'] ?? null;
            $peakCurrent = $data['peak_current'] ?? null;

            if ($isCv && ($maxCurrent === null || $minCurrent === null || $maxAbsCurrent === null)) {
                $currents = array_column($data['points'], 'current');
                if (!empty($currents)) {
                    $maxCurrent ??= max($currents);
                    $minCurrent ??= min($currents);
                    $maxAbsCurrent ??= max(abs($maxCurrent), abs($minCurrent));
                }
            }
            if ($isCv && $peakCurrent === null && $maxAbsCurrent !== null) {
                // Layar lama (Dashboard/Map) masih membaca `peak_current` untuk
                // semua method; fallback ke max_abs_current supaya tidak 0/kosong.
                $peakCurrent = $maxAbsCurrent;
            }

            $measurement = Measurement::create([
                'device_ref'       => $device->id,
                'location_ref'     => $data['location_id'] ?? null,
                'sample_id'        => $data['sample_id'],
                'method'           => $data['method'],
                'peak_current'     => $peakCurrent,
                'peak_voltage'     => $data['peak_voltage']    ?? null,
                'delta_tia'        => $data['delta_tia']       ?? null,
                'threshold'        => $data['threshold']       ?? null,
                'start_voltage'    => $data['start_voltage'],
                'end_voltage'      => $data['end_voltage'],
                'step_voltage'     => $data['step_voltage'],
                'scan_rate'        => $data['scan_rate']       ?? null,
                'pulse_amplitude'  => $data['pulse_amplitude'] ?? null,
                'duration_seconds' => $data['duration_seconds'],
                'status'           => $data['status'],
                'cycles'                => $data['cycles']                ?? null,
                'quiet_time'            => $data['quiet_time']            ?? null,
                'sensitivity_range'     => $data['sensitivity_range']     ?? null,
                'anodic_peak_current'   => $data['anodic_peak_current']   ?? null,
                'cathodic_peak_current' => $data['cathodic_peak_current'] ?? null,
                'anodic_peak_voltage'   => $data['anodic_peak_voltage']   ?? null,
                'cathodic_peak_voltage' => $data['cathodic_peak_voltage'] ?? null,
                'max_current'           => $maxCurrent,
                'min_current'           => $minCurrent,
                'max_abs_current'       => $maxAbsCurrent,
            ]);

            // Bulk insert points. A single DPV scan can easily exceed 500 rows;
            // looping ->create() would be O(N) round-trips. One INSERT keeps it O(1).
            $now = now();
            $rows = array_map(fn ($p) => [
                'measurement_id'  => $measurement->id,
                'sequence_number' => $p['sequence_number'],
                'voltage'         => $p['voltage'],
                'current'         => $p['current'],
                'cycle'           => $p['cycle'] ?? null,
                'direction'       => $p['direction'] ?? null,
                'time_seconds'    => $p['time_seconds'] ?? null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ], $data['points']);

            // Chunk to stay under MySQL's max_allowed_packet on large scans.
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('voltammetry_points')->insert($chunk);
            }

            // Bump device's last_online whenever we receive a measurement
            $device->update([
                'last_online' => $now,
                'status'      => 'online',
            ]);

            return $measurement;
        });
    }
}
