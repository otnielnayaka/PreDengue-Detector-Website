<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Measurement;
use App\Models\VoltammetryPoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * StreamController — measurement streaming realtime (dimulai dari alat).
 * ---------------------------------------------------------------------
 * Alur:
 *   1. POST /measurements/start          -> buat sesi, balas measurement_id
 *   2. POST /measurements/{id}/points    -> kirim batch titik + progress
 *   3. POST /measurements/{id}/finish    -> isi hasil akhir, status finished
 *   4. GET  /measurements/{id}/live      -> dibaca frontend tiap 1 detik
 *   5. GET  /measurements/active         -> frontend cek ada scan berjalan?
 *
 * Catatan nama kolom: device_ref, location_ref (pola *_ref). Sesuaikan
 * bila berbeda di project Anda.
 */
class StreamController extends Controller
{
    /** 1) Alat memulai sesi scan */
    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id'    => ['required', 'string'],
            'sample_id'    => ['nullable', 'string'],
            'location_id'  => ['nullable', 'integer', 'exists:locations,id'],
            'method'       => ['nullable', 'string'],
            'threshold'    => ['nullable', 'numeric'],
        ]);

        // Cari device by device_id (string) -> id
        $device = \App\Models\Device::where('device_id', $data['device_id'])->first();

        $m = Measurement::create([
            'device_ref'   => $device?->id,
            'location_ref' => $data['location_id'] ?? null,
            'sample_id'    => $data['sample_id'] ?? ('NS1-' . now()->format('YmdHis')),
            'method'       => $data['method'] ?? 'DPV',
            'threshold'    => $data['threshold'] ?? 8.0,
            'scan_status'  => 'running',
            'progress'     => 0,
            'started_at'   => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Measurement session started',
            'data'    => ['measurement_id' => $m->id, 'sample_id' => $m->sample_id],
        ], 201);
    }

    /** 2) Alat mengirim batch titik voltammogram + update progress */
    public function points(Request $request, int $id): JsonResponse
    {
        $m = Measurement::findOrFail($id);

        $data = $request->validate([
            'points'                => ['required', 'array', 'min:1'],
            'points.*.sequence_number' => ['required', 'integer'],
            'points.*.voltage'      => ['required', 'numeric'],
            'points.*.current'      => ['required', 'numeric'],
            'progress'              => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $rows = array_map(fn ($p) => [
            'measurement_id'  => $m->id,
            'sequence_number' => $p['sequence_number'],
            'voltage'         => $p['voltage'],
            'current'         => $p['current'],
            'created_at'      => now(),
            'updated_at'      => now(),
        ], $data['points']);

        DB::transaction(function () use ($rows, $m, $data) {
            VoltammetryPoint::insert($rows);
            if (isset($data['progress'])) {
                $m->progress = $data['progress'];
                $m->save();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Points appended',
            'data'    => ['received' => count($rows), 'progress' => $m->progress],
        ]);
    }

    /** 3) Alat menyelesaikan scan: isi hasil akhir */
    public function finish(Request $request, int $id): JsonResponse
    {
        $m = Measurement::findOrFail($id);

        $data = $request->validate([
            'peak_current'    => ['required', 'numeric'],
            'peak_voltage'    => ['required', 'numeric'],
            'status'          => ['required', 'string'], // positive|negative|warning|inconclusive
            'start_voltage'   => ['nullable', 'numeric'],
            'end_voltage'     => ['nullable', 'numeric'],
            'step_voltage'    => ['nullable', 'numeric'],
            'scan_rate'       => ['nullable', 'numeric'],
            'pulse_amplitude' => ['nullable', 'numeric'],
            'duration_seconds'=> ['nullable', 'integer'],
        ]);

        $m->fill($data);
        $m->scan_status = 'finished';
        $m->progress    = 100;
        $m->finished_at = now();
        $m->save();

        return response()->json([
            'success' => true,
            'message' => 'Measurement finished',
            'data'    => ['measurement_id' => $m->id, 'status' => $m->status],
        ]);
    }

    /** 4) Frontend membaca kondisi live sesi (polling tiap 1 detik) */
    public function live(int $id): JsonResponse
    {
        $m = Measurement::with(['location'])->findOrFail($id);

        $points = VoltammetryPoint::where('measurement_id', $id)
            ->orderBy('sequence_number')
            ->get(['sequence_number', 'voltage', 'current']);

        return response()->json([
            'success' => true,
            'message' => 'Live measurement',
            'data'    => [
                'measurement_id' => $m->id,
                'sample_id'      => $m->sample_id,
                'scan_status'    => $m->scan_status,
                'progress'       => $m->progress,
                'peak_current'   => $m->peak_current !== null ? (float) $m->peak_current : null,
                'peak_voltage'   => $m->peak_voltage !== null ? (float) $m->peak_voltage : null,
                'threshold'      => $m->threshold !== null ? (float) $m->threshold : null,
                'status'         => $m->status,
                'kecamatan'      => $m->location?->kecamatan,
                'desa'           => $m->location?->desa,
                'points'         => $points,
            ],
        ]);
    }

    /** 5) Frontend menanyakan: apakah ada scan yang sedang berjalan? */
    public function active(): JsonResponse
    {
        $m = Measurement::where('scan_status', 'running')
            ->latest('started_at')
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Active scan',
            'data'    => $m ? ['measurement_id' => $m->id, 'sample_id' => $m->sample_id] : null,
        ]);
    }
}
