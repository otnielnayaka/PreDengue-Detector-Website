<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Support\ApiResponse;

class StoreDeviceLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id'       => ['required', 'string', 'exists:devices,device_id'],
            'battery_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'battery_voltage' => ['nullable', 'numeric'],
            'wifi_rssi'       => ['nullable', 'integer', 'between:-120,0'],
            'sd_status'       => ['nullable', 'in:ok,full,missing,error'],
            'free_storage_mb' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::validationError($validator->errors())
        );
    }
}
