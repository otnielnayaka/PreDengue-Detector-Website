<?php
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\DeviceLogController;
use App\Http\Controllers\Api\DeviceStatusController;
use App\Http\Controllers\Api\MeasurementController;
use App\Http\Controllers\Api\TelemetryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MapController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\StreamController;
use App\Http\Controllers\AuthController;      // <-- TAMBAH (folder Controllers, bukan Api)
use App\Http\Controllers\UserController;      // <-- TAMBAH

// ============================================================
// AUTH ROUTES (PUBLIC - tanpa login)
// ============================================================
Route::post('/login', [AuthController::class, 'login']);

// ============================================================
// AUTH ROUTES (perlu login)
// ============================================================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // User management - ADMIN ONLY
    Route::middleware('role:admin')->group(function () {
        Route::get   ('/users',         [UserController::class, 'index']);
        Route::post  ('/users',         [UserController::class, 'store']);
        Route::put   ('/users/{user}',  [UserController::class, 'update']);
        Route::delete('/users/{user}',  [UserController::class, 'destroy']);
    });
});

Route::prefix('v1')->group(function () {
    // ----- Dashboard -----
    Route::prefix('dashboard')->group(function () {
        Route::get('summary',          [DashboardController::class, 'summary']);
        Route::get('positives',        [DashboardController::class, 'positives']);
        Route::get('status-breakdown', [DashboardController::class, 'statusBreakdown']);
    });
    // ----- Measurements -----
    Route::prefix('measurements')->group(function () {
    // --- Streaming realtime dari ALAT (amankan dengan device key) ---
        Route::post('start',         [StreamController::class, 'start'])->middleware('device.key');
        Route::post('{id}/points',   [StreamController::class, 'points'])->middleware('device.key');
        Route::post('{id}/finish',   [StreamController::class, 'finish'])->middleware('device.key');
    // --- read realtime (boleh semua, atau tambah auth nanti) ---
        Route::get ('active',        [StreamController::class, 'active']);
        Route::get ('{id}/live',     [StreamController::class, 'live']);
    // --- route lama Anda (TETAP) ---
        Route::get ('latest',              [MeasurementController::class, 'latest']);
        Route::get ('/',                   [MeasurementController::class, 'index']);
        Route::get ('{measurement}',       [MeasurementController::class, 'show']);
        Route::get ('{measurement}/graph', [MeasurementController::class, 'graph']);
        // store dari dashboard - ADMIN ONLY (alat pakai /start di atas)
        Route::post('/',                   [MeasurementController::class, 'store'])->middleware(['auth:sanctum','role:admin']);
    });
    // ----- Devices -----
    Route::prefix('devices')->group(function () {
        Route::get('status',         DeviceStatusController::class);
        Route::get('/',              [DeviceController::class, 'index']);
        Route::get('{device}',       [DeviceController::class, 'show']);
        Route::get('{device}/logs',  [DeviceLogController::class, 'indexForDevice']);
        // tambah device - ADMIN ONLY
        Route::post('/',             [DeviceController::class, 'store'])->middleware(['auth:sanctum','role:admin']);
    });
    // ----- Telemetry -----
    Route::prefix('telemetry')->group(function () {
        Route::get('latest', [TelemetryController::class, 'latest']);
        Route::post('/',     [TelemetryController::class, 'store'])->middleware('device.key');
    });
    // Legacy alias untuk firmware yang sudah deploy
    Route::post('device-log', [DeviceLogController::class, 'store'])->middleware('device.key');
    Route::get('/locations', [LocationController::class, 'index']);
    // --- Map Monitoring (per lokasi sampel) ---
    Route::get('/map/measurements', [MapController::class, 'measurements']);
});
