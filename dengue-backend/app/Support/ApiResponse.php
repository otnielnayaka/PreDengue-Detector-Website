<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Unified API response envelope.
 *
 * Every endpoint returns the same shape:
 *   { "success": bool, "message": string, "data": mixed, "errors": mixed|null }
 *
 * Keeping this in one helper means the contract is enforced by construction,
 * not by convention — the ESP32 firmware and the Vue frontend can both parse
 * exactly one schema.
 */
class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function error(
        string $message,
        int $status = 400,
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    public static function created(mixed $data = null, string $message = 'Resource created'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 404);
    }

    public static function validationError(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return self::error($message, 422, $errors);
    }
}
