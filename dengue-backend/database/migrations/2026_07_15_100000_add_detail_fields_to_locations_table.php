<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom detail lokasi tambahan (Section 2 — Edit Location dari Data Log).
 * Aditif & nullable — kecamatan/desa TIDAK disentuh, data lama tetap valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (!Schema::hasColumn('locations', 'province')) {
                $table->string('province', 100)->nullable()->after('desa');
            }
            if (!Schema::hasColumn('locations', 'city_regency')) {
                $table->string('city_regency', 100)->nullable()->after('province');
            }
            if (!Schema::hasColumn('locations', 'location_name')) {
                $table->string('location_name', 150)->nullable()->after('city_regency');
            }
            if (!Schema::hasColumn('locations', 'notes')) {
                $table->text('notes')->nullable()->after('longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            foreach (['province', 'city_regency', 'location_name', 'notes'] as $col) {
                if (Schema::hasColumn('locations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
