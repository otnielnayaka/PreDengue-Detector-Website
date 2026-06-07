<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Support\ApiResponse;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id'        => ['required', 'string', 'max:64', 'unique:devices,device_id'],
            'firmware_version' => ['nullable', 'string', 'max:32'],
            'status'           => ['nullable', 'in:online,offline,maintenance,error'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::validationError($validator->errors())
        );
    }
}
