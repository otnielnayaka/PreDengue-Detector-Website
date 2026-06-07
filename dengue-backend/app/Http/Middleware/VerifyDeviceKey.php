<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDeviceKey
{
    /**
     * Amankan endpoint IoT dengan API key sederhana.
     * Alat kirim header: X-Device-Key: <key>
     * Key disimpan di .env: DEVICE_API_KEY=xxxx
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Device-Key');
        if (!$key || $key !== config('app.device_api_key')) {
            return response()->json(['message' => 'Invalid device key'], 401);
        }
        return $next($request);
    }
}
