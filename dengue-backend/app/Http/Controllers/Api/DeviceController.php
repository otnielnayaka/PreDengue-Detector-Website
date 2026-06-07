<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $devices = Device::with('latestLog')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('last_online')
            ->paginate($request->integer('per_page', 25));

        return ApiResponse::success(DeviceResource::collection($devices)->response()->getData(true));
    }

    public function show(Device $device): JsonResponse
    {
        $device->load('latestLog', 'latestMeasurement');
        return ApiResponse::success(new DeviceResource($device));
    }

    public function store(StoreDeviceRequest $request): JsonResponse
    {
        $device = Device::create($request->validated());
        return ApiResponse::created(new DeviceResource($device), 'Device registered');
    }
}
