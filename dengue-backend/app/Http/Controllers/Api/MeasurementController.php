<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeasurementRequest;
use App\Http\Requests\UpdateMeasurementLocationRequest;
use App\Http\Resources\MeasurementResource;
use App\Http\Resources\MeasurementDetailResource;
use App\Models\Location;
use App\Models\Measurement;
use App\Services\MeasurementService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class MeasurementController extends Controller
{
    public function __construct(private MeasurementService $service) {}

    /**
     * GET /measurements
     */
    public function index(Request $request)
    {
        $query = Measurement::query()
            ->with(['device:id,device_id,status', 'location:id,kecamatan,desa,province,city_regency,location_name,notes,latitude,longitude']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($method = $request->query('method')) {
            $query->where('method', $method);
        }
        if ($search = $request->query('search')) {
            $query->where('sample_id', 'like', "%{$search}%");
        }

        $perPage = (int) $request->query('per_page', 10);
        $paginated = $query->latest()->paginate($perPage);

        return ApiResponse::success([
            'data' => MeasurementResource::collection($paginated->items()),
            'meta' => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    /**
     * GET /measurements/latest
     *
     * Returns the most recent measurement with full voltammogram points,
     * supaya dashboard bisa langsung render grafik tanpa request kedua.
     */
    public function latest()
    {
        $measurement = Measurement::query()
            ->with([
                'device:id,device_id,status',
                'location:id,kecamatan,desa,province,city_regency,location_name,notes,latitude,longitude',
                'points' => fn ($q) => $q->orderBy('sequence_number'),
            ])
            ->latest()
            ->first();

        if (! $measurement) {
            return ApiResponse::success(null, 'Belum ada measurement');
        }

        return ApiResponse::success(new MeasurementDetailResource($measurement));
    }

    /**
     * GET /measurements/{id}
     */
    public function show(Measurement $measurement)
    {
        $measurement->load([
            'device:id,device_id,status',
            'location:id,kecamatan,desa,province,city_regency,location_name,notes,latitude,longitude',
            'points' => fn ($q) => $q->orderBy('sequence_number'),
        ]);

        return ApiResponse::success(new MeasurementDetailResource($measurement));
    }

    /**
     * GET /measurements/{id}/graph
     *
     * Returns just the voltammogram points for graph rendering.
     */
    public function graph(Measurement $measurement)
    {
        $points = $measurement->points()
            ->orderBy('sequence_number')
            ->get(['sequence_number', 'voltage', 'current']);

        return ApiResponse::success([
            'measurement_id' => $measurement->id,
            'sample_id'      => $measurement->sample_id,
            'points'         => $points,
        ]);
    }

    /**
     * POST /measurements
     */
    public function store(StoreMeasurementRequest $request)
    {
        $measurement = $this->service->store($request->validated());

        return ApiResponse::created(
            new MeasurementDetailResource($measurement->load(['device', 'location', 'points'])),
            'Measurement berhasil disimpan'
        );
    }

    /**
     * PATCH /measurements/{id}/location — ADMIN ONLY (dijamin middleware role:admin)
     *
     * Selalu membuat baris `locations` BARU (tidak update baris existing),
     * supaya edit lokasi 1 measurement tidak diam-diam mengubah lokasi
     * measurement lain yang mungkin share location_ref yang sama.
     */
    public function updateLocation(UpdateMeasurementLocationRequest $request, Measurement $measurement)
    {
        $data = $request->validated();

        $location = Location::create([
            // kecamatan/desa (NOT NULL, kolom lama) diisi dari district/village
            // yang disubmit — menjaga backward compat kode/dropdown lama.
            'kecamatan'     => $data['district'],
            'desa'          => $data['village'],
            'province'      => $data['province'] ?? null,
            'city_regency'  => $data['city_regency'] ?? null,
            'location_name' => $data['location_name'],
            'notes'         => $data['notes'] ?? null,
            'latitude'      => $data['latitude'],
            'longitude'     => $data['longitude'],
        ]);

        $measurement->location_ref = $location->id;
        $measurement->save();

        $measurement->load(['device:id,device_id,status', 'location']);

        return ApiResponse::success(
            new MeasurementDetailResource($measurement),
            'Lokasi measurement berhasil diperbarui'
        );
    }
}
