<?php

namespace App\Support;

/**
 * Menormalkan array titik voltammogram dari alat ke bentuk internal yang
 * konsisten, terlepas dari nama field yang dipakai firmware:
 *
 *   sequence_number | index                -> sequence_number (int)
 *   voltage | potential | voltage_v        -> voltage (V)
 *   current | current_ua                   -> current (µA)
 *   current_a                              -> current (µA, dikonversi × 1e6)
 *   time | time_seconds                    -> time_seconds (s)
 *   timestamp_ms                           -> time_seconds (s, dikonversi ÷ 1000)
 *   cycle                                  -> cycle (int, khusus CV)
 *   direction | sweep_direction            -> direction (string, khusus CV)
 *
 * Dipakai oleh StreamController (jalur alat) dan StoreMeasurementRequest
 * (jalur dashboard) supaya kedua endpoint menerima variasi nama field yang
 * sama, tanpa duplikasi logic mapping.
 *
 * Unit internal (disepakati dengan kolom DB & frontend):
 *   voltage -> Volt, current -> µA, time -> detik.
 */
class PointsNormalizer
{
    /**
     * @param  array<int, array<string, mixed>>  $rawPoints
     * @return array<int, array<string, mixed>>
     */
    public static function normalize(array $rawPoints): array
    {
        return array_map(fn (array $p) => self::normalizeOne($p), $rawPoints);
    }

    private static function normalizeOne(array $p): array
    {
        $sequence = $p['sequence_number'] ?? $p['index'] ?? null;

        $voltage = $p['voltage'] ?? $p['potential'] ?? $p['voltage_v'] ?? null;

        $current = null;
        if (array_key_exists('current', $p)) {
            $current = $p['current']; // sudah µA (konvensi kolom existing)
        } elseif (array_key_exists('current_ua', $p)) {
            $current = $p['current_ua'];
        } elseif (array_key_exists('current_a', $p)) {
            // Ampere -> mikroAmpere
            $current = is_numeric($p['current_a']) ? $p['current_a'] * 1_000_000 : $p['current_a'];
        }

        $time = null;
        if (array_key_exists('time_seconds', $p)) {
            $time = $p['time_seconds'];
        } elseif (array_key_exists('time', $p)) {
            $time = $p['time'];
        } elseif (array_key_exists('timestamp_ms', $p)) {
            $time = is_numeric($p['timestamp_ms']) ? $p['timestamp_ms'] / 1000 : $p['timestamp_ms'];
        }

        $cycle = $p['cycle'] ?? null;
        $direction = $p['direction'] ?? $p['sweep_direction'] ?? null;

        return [
            'sequence_number' => $sequence,
            'voltage'         => $voltage,
            'current'         => $current,
            'time_seconds'    => $time,
            'cycle'           => $cycle,
            'direction'       => $direction,
        ];
    }
}
