<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| Konfigurasi ini menentukan domain mana yang boleh memanggil API ini.
|
| Saat development:
|   - Vue dev server jalan di http://localhost:5173 dan http://127.0.0.1:5173
|   - Keduanya harus di-allow.
|
| Saat production:
|   - Ganti 'allowed_origins' ke domain frontend yang sudah live.
|   - Jangan pakai '*' untuk endpoint yang sensitif.
|
| File ini di-publish dari paket fruitcake/laravel-cors (Laravel <9) atau
| sudah bawaan Laravel 9+. Kalau belum ada, jalankan:
|   php artisan config:publish cors
|
*/

return [

    /*
    | Path mana yang dilindungi CORS. 'api/*' artinya semua endpoint
    | di bawah /api. 'sanctum/csrf-cookie' diperlukan kalau pakai Sanctum
    | dengan SPA authentication.
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
    | HTTP methods yang diizinkan. ['*'] berarti semua.
    */
    'allowed_methods' => ['*'],

    /*
    | Origin frontend yang diizinkan.
    |
    | DEVELOPMENT — Vue dev server di Vite default port 5173:
    */
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        // Tambahkan di sini saat deploy ke production:
        // 'https://dashboard.predengue.id',
    ],

    /*
    | Pattern regex untuk origin yang diizinkan (jarang dipakai).
    */
    'allowed_origins_patterns' => [],

    /*
    | Header request yang diizinkan. ['*'] = semua.
    | Frontend kita kirim 'Content-Type', 'Accept', dan 'X-Device-Key',
    | jadi minimal itu harus masuk.
    */
    'allowed_headers' => ['*'],

    /*
    | Header response yang di-expose ke JavaScript. Biasanya kosong.
    */
    'exposed_headers' => [],

    /*
    | Berapa detik browser boleh cache hasil preflight (OPTIONS) request.
    | 0 = jangan cache. Untuk production set ke 86400 (1 hari).
    */
    'max_age' => 0,

    /*
    | True kalau frontend kirim cookies / Authorization header.
    | Pakai Sanctum SPA auth → set true. Token-based / API key → false cukup.
    */
    'supports_credentials' => false,

];
