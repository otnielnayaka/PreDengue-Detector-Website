<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeasurementResource;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    /**
     * GET /api/dashboard/summary
     * Single aggregated payload for the dashboard landing view.
     */
    public function summary(): JsonResponse
    {
        return ApiResponse::success($this->dashboard->summary());
    }

    /**
     * GET /api/dashboard/positives
     * Recent positive detections for the alert list.
     */
    public function positives(): JsonResponse
    {
        return ApiResponse::success(
            MeasurementResource::collection($this->dashboard->recentPositives(10))
        );
    }

    /**
     * GET /api/dashboard/status-breakdown
     */
    public function statusBreakdown(): JsonResponse
    {
        return ApiResponse::success($this->dashboard->statusBreakdownToday());
    }
}
