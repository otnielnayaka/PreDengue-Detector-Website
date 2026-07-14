<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Location;
use App\Models\Measurement;
use App\Models\VoltammetryPoint;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
        // ---- Devices ----
        $dev1 = Device::create([
            'device_id'        => 'POT-001',
            'firmware_version' => 'v0.9.3',
            'battery_voltage'  => 3.92,
            'wifi_status'      => 'connected',
            'last_online'      => now(),
            'status'           => 'online',
        ]);

        $dev2 = Device::create([
            'device_id'        => 'POT-002',
            'firmware_version' => 'v0.9.3',
            'battery_voltage'  => 3.71,
            'wifi_status'      => 'weak',
            'last_online'      => now()->subMinutes(8),
            'status'           => 'online',
        ]);

        // ---- Locations (Bandung area) ----
        $loc1 = Location::create([
            'kecamatan' => 'Coblong',
            'desa'      => 'Dago',
            'latitude'  => -6.8856,
            'longitude' => 107.6131,
        ]);

        $loc2 = Location::create([
            'kecamatan' => 'Bandung Wetan',
            'desa'      => 'Tamansari',
            'latitude'  => -6.9039,
            'longitude' => 107.6186,
        ]);

        // ---- A sample DPV measurement with synthetic voltammogram ----
        $m = Measurement::create([
            'device_ref'       => $dev1->id,
            'location_ref'     => $loc1->id,
            'sample_id'        => 'NS1-DEMO-001',
            'method'           => 'DPV',
            'peak_current'     => 12.456789,
            'peak_voltage'     => 0.1850,
            'delta_tia'        => 1.0000,
            'threshold'        => 8.000000,
            'start_voltage'    => -0.2000,
            'end_voltage'      => 0.6000,
            'step_voltage'     => 0.0050,
            'scan_rate'        => 0.0500,
            'pulse_amplitude'  => 0.0250,
            'duration_seconds' => 32,
            'status'           => 'positive',
        ]);

        // Synthetic DPV peak around V=0.185
        $rows = [];
        $steps = 160;
        for ($i = 0; $i < $steps; $i++) {
            $v = -0.2 + ($i * 0.005);
            $sigma = 0.04;
            $peak  = 12.0 * exp(-(($v - 0.185) ** 2) / (2 * $sigma * $sigma));
            $baseline = 1.2 + 0.3 * $v;
            $noise = (mt_rand(-10, 10) / 1000);
            $rows[] = [
                'measurement_id'  => $m->id,
                'sequence_number' => $i,
                'voltage'         => round($v, 4),
                'current'         => round($baseline + $peak + $noise, 6),
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }
        VoltammetryPoint::insert($rows);
    }
}
