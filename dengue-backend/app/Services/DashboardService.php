<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Measurement;

/**
 * Pre-computed dashboard aggregates.
 *
 * Each method represents one card / chart on the scientific monitoring UI.
 * Methods are kept narrow (single responsibility) so they can be:
 *   - cached individually (e.g. Cache::remember on the heavier ones),
 *   - exposed as separate endpoints if the frontend wants partial refresh,
 *   - or composed into one summary response (the default).
 */
class DashboardService
{
    public function summary(): array
    {
        return [
            'devices' => [
                'total'   => Device::count(),
                'online'  => Device::online()->count(),
                'offline' => Device::where('status', 'offline')->count(),
            ],
            'measurements' => [
                'total_today'      => Measurement::today()->count(),
                'positives_today'  => Measurement::today()->positive()->count(),
                'positives_total'  => Measurement::positive()->count(),
                'by_status_today'  => $this->statusBreakdownToday(),
            ],
            'latest_measurement' => Measurement::with(['device:id,device_id', 'location:id,kecamatan,desa'])
                ->latest()
                ->first(),
        ];
    }

    public function statusBreakdownToday(): array
    {
        return Measurement::today()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    public function recentPositives(int $limit = 10)
    {
        return Measurement::with(['device:id,device_id', 'location:id,kecamatan,desa'])
            ->positive()
            ->latest()
            ->limit($limit)
            ->get();
    }
}
