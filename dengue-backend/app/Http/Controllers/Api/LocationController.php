<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

/**
 * LocationController
 * ------------------
 * Menyediakan daftar lokasi untuk dropdown kecamatan/desa di frontend.
 * Endpoint: GET /api/v1/locations
 */
class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = Location::query()
            ->orderBy('kecamatan')
            ->orderBy('desa')
            ->get(['id', 'kecamatan', 'desa', 'latitude', 'longitude']);

        return response()->json([
            'success' => true,
            'message' => 'Locations retrieved',
            'data'    => $locations,
        ]);
    }
}
