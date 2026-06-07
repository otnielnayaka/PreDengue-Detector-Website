<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Location;
use App\Services\MeasurementService;
use Illuminate\Console\Command;

/**
 * Simulasi device mengirim measurement.
 *
 * Berguna untuk development backend & frontend dashboard tanpa
 * harus menunggu firmware Feather M4 selesai.
 *
 * Usage:
 *   php artisan simulate:measurement
 *   php artisan simulate:measurement --device=POT-001 --status=positive
 *   php artisan simulate:measurement --count=10
 *   php artisan simulate:measurement --count=20 --interval=5
 */
class SimulateMeasurement extends Command
{
    protected $signature = 'simulate:measurement
        {--device=POT-001 : device_id yang dipakai}
        {--status= : negative|positive|warning (random kalau kosong)}
        {--method=DPV : DPV|CV|SWV}
        {--points=160 : jumlah titik voltammogram}
        {--count=1 : jumlah measurement yang di-generate}
        {--interval=0 : jeda detik antar measurement}';

    protected $description = 'Generate synthetic measurement(s) seperti ESP32 mengirim data';

    public function handle(MeasurementService $service): int
    {
        $deviceId = $this->option('device');
        $count    = (int) $this->option('count');
        $interval = (int) $this->option('interval');

        $device = Device::where('device_id', $deviceId)->first();
        if (! $device) {
            $this->error("Device '$deviceId' tidak ditemukan. Jalankan db:seed dulu.");
            return self::FAILURE;
        }

        $location = Location::inRandomOrder()->first();

        for ($i = 1; $i <= $count; $i++) {
            $payload = $this->buildPayload($deviceId, $location?->id);
            $measurement = $service->store($payload);

            $this->info(sprintf(
                "[%d/%d] Measurement #%d created — sample=%s, status=%s, peak=%.3f μA @ %.3f V",
                $i, $count,
                $measurement->id,
                $measurement->sample_id,
                $measurement->status,
                $measurement->peak_current,
                $measurement->peak_voltage,
            ));

            if ($interval > 0 && $i < $count) {
                sleep($interval);
            }
        }

        return self::SUCCESS;
    }

    private function buildPayload(string $deviceId, ?int $locationId): array
    {
        $method = $this->option('method');
        $status = $this->option('status') ?: $this->randomStatus();
        $points = (int) $this->option('points');

        // Karakteristik kurva berdasarkan status
        // Positive: peak tinggi; negative: peak rendah/tidak ada
        $peakHeight = match ($status) {
            'positive' => mt_rand(10, 20) + (mt_rand(0, 999) / 1000),
            'warning'  => mt_rand(5, 9)  + (mt_rand(0, 999) / 1000),
            default    => mt_rand(0, 3)  + (mt_rand(0, 999) / 1000),
        };
        $peakVoltage = 0.185 + ((mt_rand(-20, 20)) / 1000);

        $startV = -0.2;
        $endV   = 0.6;
        $stepV  = ($endV - $startV) / $points;

        // Generate DPV-shaped curve: baseline + Gaussian peak + noise
        $pointsData = [];
        $sigma = 0.04;
        for ($i = 0; $i < $points; $i++) {
            $v = $startV + ($i * $stepV);
            $peakComponent = $peakHeight * exp(-(($v - $peakVoltage) ** 2) / (2 * $sigma * $sigma));
            $baseline = 1.2 + 0.3 * $v;
            $noise = mt_rand(-10, 10) / 1000;
            $i_value = $baseline + $peakComponent + $noise;

            $pointsData[] = [
                'sequence_number' => $i,
                'voltage'         => round($v, 4),
                'current'         => round($i_value, 6),
            ];
        }

        return [
            'device_id'        => $deviceId,
            'location_id'      => $locationId,
            'sample_id'        => 'NS1-SIM-' . now()->format('YmdHis') . '-' . mt_rand(100, 999),
            'method'           => $method,
            'peak_current'     => round($peakHeight + 1.5, 6),
            'peak_voltage'     => round($peakVoltage, 4),
            'delta_tia'        => 1.0,
            'threshold'        => 8.0,
            'start_voltage'    => $startV,
            'end_voltage'      => $endV,
            'step_voltage'     => round($stepV, 4),
            'scan_rate'        => 0.05,
            'pulse_amplitude'  => 0.025,
            'duration_seconds' => mt_rand(25, 40),
            'status'           => $status,
            'points'           => $pointsData,
        ];
    }

    private function randomStatus(): string
    {
        $roll = mt_rand(1, 100);
        if ($roll <= 25) return 'positive';
        if ($roll <= 35) return 'warning';
        if ($roll <= 90) return 'negative';
        return 'inconclusive';
    }
}
