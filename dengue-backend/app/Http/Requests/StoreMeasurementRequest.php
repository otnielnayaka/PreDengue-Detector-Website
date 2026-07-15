<?php

namespace App\Http\Requests;

use App\Support\PointsNormalizer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Support\ApiResponse;

class StoreMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: device-key middleware will handle auth
    }

    /**
     * Normalisasi payload sebelum validasi:
     *  - `vertex_voltage` (alias CV) -> `end_voltage`
     *  - variasi nama field points (potential/voltage_v, current_ua/current_a,
     *    index, cycle, direction/sweep_direction, time/timestamp_ms) -> bentuk
     *    internal konsisten. Sama seperti StreamController::points(), supaya
     *    kedua endpoint (alat & dashboard) toleran terhadap nama field yang sama.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('vertex_voltage') && !$this->has('end_voltage')) {
            $this->merge(['end_voltage' => $this->input('vertex_voltage')]);
        }

        $points = $this->input('points');
        if (is_array($points)) {
            $this->merge(['points' => PointsNormalizer::normalize($points)]);
        }
    }

    public function rules(): array
    {
        return [
            // The ESP32 sends its hardware device_id string; we resolve to FK.
            'device_id'        => ['required', 'string', 'exists:devices,device_id'],
            'location_id'      => ['nullable', 'integer', 'exists:locations,id'],

            'sample_id'        => ['required', 'string', 'max:64'],
            // Method aktif untuk pengukuran BARU: DPV & CV. SWV historis
            // hanya bisa dibaca (index/show/graph), tidak bisa dibuat baru
            // lewat endpoint ini.
            'method'           => ['required', 'in:DPV,CV'],

            // Nullable untuk semua method (perilaku asli endpoint ini tidak
            // berubah). Untuk CV yang tidak mengirim nilai ini, MeasurementService
            // akan menghitungnya dari points (lihat catatan di service).
            'peak_current'     => ['nullable', 'numeric'],
            'peak_voltage'     => ['nullable', 'numeric'],
            'delta_tia'        => ['nullable', 'numeric'],
            'threshold'        => ['nullable', 'numeric'],

            'start_voltage'    => ['required', 'numeric'],
            'end_voltage'      => ['required', 'numeric'],
            'step_voltage'     => ['required', 'numeric'],

            'scan_rate'        => ['nullable', 'numeric'],
            // Khusus DPV — tidak diwajibkan untuk CV (sudah nullable untuk semua).
            'pulse_amplitude'  => ['nullable', 'numeric'],

            'duration_seconds' => ['required', 'integer', 'min:0'],
            'status'           => ['required', 'in:negative,positive,warning,invalid,inconclusive'],

            // Khusus CV — nullable untuk semua method, tidak memengaruhi DPV.
            'cycles'                => ['nullable', 'integer', 'min:1'],
            'quiet_time'            => ['nullable', 'numeric'],
            'sensitivity_range'     => ['nullable', 'string', 'max:32'],
            'anodic_peak_current'   => ['nullable', 'numeric'],
            'cathodic_peak_current' => ['nullable', 'numeric'],
            'anodic_peak_voltage'   => ['nullable', 'numeric'],
            'cathodic_peak_voltage' => ['nullable', 'numeric'],
            'max_current'           => ['nullable', 'numeric'],
            'min_current'           => ['nullable', 'numeric'],
            'max_abs_current'       => ['nullable', 'numeric'],

            // Raw voltammogram payload
            'points'                     => ['required', 'array', 'min:1', 'max:5000'],
            'points.*.sequence_number'   => ['required', 'integer', 'min:0'],
            'points.*.voltage'           => ['required', 'numeric'],
            'points.*.current'           => ['required', 'numeric'],
            // Khusus CV — opsional, null untuk DPV/SWV seperti sebelumnya.
            'points.*.cycle'             => ['nullable', 'integer', 'min:1'],
            'points.*.direction'         => ['nullable', 'string', 'in:forward,reverse'],
            'points.*.time_seconds'      => ['nullable', 'numeric'],
        ];
    }

    /**
     * Force consistent JSON error response (no HTML redirect).
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::validationError($validator->errors())
        );
    }
}
