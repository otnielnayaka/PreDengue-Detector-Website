<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateMeasurementLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi sesungguhnya dijamin middleware role:admin di route.
        return true;
    }

    public function rules(): array
    {
        return [
            // Wajib: location_name (inti data), district/village (kolom
            // kecamatan/desa NOT NULL di DB), latitude/longitude (inti map).
            'location_name' => ['required', 'string', 'max:150'],
            'district'      => ['required', 'string', 'max:100'],
            'village'       => ['required', 'string', 'max:100'],
            'latitude'      => ['required', 'numeric', 'between:-90,90'],
            'longitude'     => ['required', 'numeric', 'between:-180,180'],
            // Opsional
            'province'      => ['nullable', 'string', 'max:100'],
            'city_regency'  => ['nullable', 'string', 'max:100'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::validationError($validator->errors())
        );
    }
}
