<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah kolom opsional untuk titik CV: cycle, direction, time_seconds.
 *
 * CV bisa punya sapuan maju & balik dengan voltage yang sama (loop), jadi
 * urutan akuisisi (sequence_number, sudah ada) tetap jadi sumber kebenaran
 * untuk render grafik — kolom baru ini hanya metadata tambahan, nullable,
 * tidak mengubah cara titik DPV/SWV lama disimpan atau dibaca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voltammetry_points', function (Blueprint $table) {
            if (!Schema::hasColumn('voltammetry_points', 'cycle')) {
                $table->unsignedInteger('cycle')->nullable()->after('current');
            }
            if (!Schema::hasColumn('voltammetry_points', 'direction')) {
                // 'forward' | 'reverse' — string bebas, tidak di-enum-kan supaya
                // toleran terhadap variasi penamaan dari firmware alat.
                $table->string('direction', 10)->nullable()->after('cycle');
            }
            if (!Schema::hasColumn('voltammetry_points', 'time_seconds')) {
                $table->decimal('time_seconds', 10, 4)->nullable()->after('direction');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voltammetry_points', function (Blueprint $table) {
            foreach (['cycle', 'direction', 'time_seconds'] as $col) {
                if (Schema::hasColumn('voltammetry_points', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
