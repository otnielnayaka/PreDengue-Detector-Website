<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom latitude & longitude ke tabel locations YANG SUDAH ADA.
 *
 * CATATAN: tabel `locations` sudah dibuat sebelumnya (berisi kecamatan, desa).
 * Migration ini AMAN dijalankan ulang karena memakai hasColumn() —
 * tidak akan error walau kolomnya sudah ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (!Schema::hasColumn('locations', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('desa');
            }
            if (!Schema::hasColumn('locations', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('locations', 'latitude')) {
                $table->dropColumn('latitude');
            }
        });
    }
};
