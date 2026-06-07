<?php

namespace App\Http\Requests;

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

    public function rules(): array
    {
        return [
            // The ESP32 sends its hardware device_id string; we resolve to FK.
            'device_id'        => ['required', 'string', 'exists:devices,device_id'],
            'location_id'      => ['nullable', 'integer', 'exists:locations,id'],

            'sample_id'        => ['required', 'string', 'max:64'],
            'method'           => ['required', 'in:DPV,CV,SWV'],

            'peak_current'     => ['nullable', 'numeric'],
            'peak_voltage'     => ['nullable', 'numeric'],
            'delta_tia'        => ['nullable', 'numeric'],
            'threshold'        => ['nullable', 'numeric'],

            'start_voltage'    => ['required', 'numeric'],
            'end_voltage'      => ['required', 'numeric'],
            'step_voltage'     => ['required', 'numeric'],

            'scan_rate'        => ['nullable', 'numeric'],
            'pulse_amplitude'  => ['nullable', 'numeric'],

            'duration_seconds' => ['required', 'integer', 'min:0'],
            'status'           => ['required', 'in:negative,positive,warning,invalid,inconclusive'],

            // Raw voltammogram payload
            'points'                     => ['required', 'array', 'min:1', 'max:5000'],
            'points.*.sequence_number'   => ['required', 'integer', 'min:0'],
            'points.*.voltage'           => ['required', 'numeric'],
            'points.*.current'           => ['required', 'numeric'],
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
