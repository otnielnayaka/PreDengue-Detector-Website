<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * LocationSeeder
 * --------------
 * Mengisi koordinat wilayah (kecamatan/desa) di sekitar Kota Banjar, Jawa Barat.
 *
 * Memakai updateOrInsert berdasarkan (kecamatan, desa) sehingga:
 *   - kalau baris lokasi sudah ada  -> koordinatnya di-update
 *   - kalau belum ada               -> baris baru dibuat
 * Aman dijalankan berulang (idempotent), tidak menggandakan data.
 *
 * PENTING: koordinat di bawah adalah APROKSIMASI. Sesuaikan dengan titik
 * wilayah yang sebenarnya bila diperlukan untuk laporan.
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['kecamatan' => 'Banjar',      'desa' => 'Mekarsari',  'latitude' => -7.3660, 'longitude' => 108.5340],
            ['kecamatan' => 'Banjar',      'desa' => 'Hegarsari',  'latitude' => -7.3705, 'longitude' => 108.5388],
            ['kecamatan' => 'Banjar',      'desa' => 'Situbatu',   'latitude' => -7.3590, 'longitude' => 108.5275],
            ['kecamatan' => 'Pataruman',   'desa' => 'Pataruman',  'latitude' => -7.3902, 'longitude' => 108.5503],
            ['kecamatan' => 'Pataruman',   'desa' => 'Hegarmanah', 'latitude' => -7.3980, 'longitude' => 108.5610],
            ['kecamatan' => 'Purwaharja',  'desa' => 'Purwaharja', 'latitude' => -7.3318, 'longitude' => 108.5602],
            ['kecamatan' => 'Purwaharja',  'desa' => 'Karangpanimbal', 'latitude' => -7.3255, 'longitude' => 108.5520],
            ['kecamatan' => 'Langensari',  'desa' => 'Langensari', 'latitude' => -7.3452, 'longitude' => 108.6201],
            ['kecamatan' => 'Langensari',  'desa' => 'Waringinsari', 'latitude' => -7.3510, 'longitude' => 108.6105],
            ['kecamatan' => 'Langensari',  'desa' => 'Muktisari',  'latitude' => -7.3388, 'longitude' => 108.6288],
        ];

        foreach ($locations as $loc) {
            DB::table('locations')->updateOrInsert(
                ['kecamatan' => $loc['kecamatan'], 'desa' => $loc['desa']],
                [
                    'latitude'   => $loc['latitude'],
                    'longitude'  => $loc['longitude'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
