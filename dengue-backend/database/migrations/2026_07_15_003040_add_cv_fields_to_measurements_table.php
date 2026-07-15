<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah kolom untuk mendukung Cyclic Voltammetry (CV), tanpa mengubah
 * atau menghapus kolom yang sudah ada (DPV/SWV historis tetap utuh).
 *
 * - cycles / quiet_time / sensitivity_range : parameter scan khusus CV
 *   (nullable — DPV/SWV lama tidak terpengaruh, tetap null).
 * - anodic/cathodic_peak_current/voltage, max_current, min_current,
 *   max_abs_current : summary CV. Semua nullable — hanya diisi kalau
 *   memang bisa dihitung/diterima dari alat, tidak pernah dikarang.
 *
 * AMAN dijalankan ulang (pakai hasColumn), mengikuti pola migration
 * add_streaming_to_measurements yang sudah ada di project ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            if (!Schema::hasColumn('measurements', 'cycles')) {
                $table->unsignedInteger('cycles')->nullable()->after('pulse_amplitude');
            }
            if (!Schema::hasColumn('measurements', 'quiet_time')) {
                $table->decimal('quiet_time', 8, 4)->nullable()->after('cycles');
            }
            if (!Schema::hasColumn('measurements', 'sensitivity_range')) {
                $table->string('sensitivity_range', 32)->nullable()->after('quiet_time');
            }
            if (!Schema::hasColumn('measurements', 'anodic_peak_current')) {
                $table->decimal('anodic_peak_current', 14, 6)->nullable()->after('sensitivity_range');
            }
            if (!Schema::hasColumn('measurements', 'cathodic_peak_current')) {
                $table->decimal('cathodic_peak_current', 14, 6)->nullable()->after('anodic_peak_current');
            }
            if (!Schema::hasColumn('measurements', 'anodic_peak_voltage')) {
                $table->decimal('anodic_peak_voltage', 8, 4)->nullable()->after('cathodic_peak_current');
            }
            if (!Schema::hasColumn('measurements', 'cathodic_peak_voltage')) {
                $table->decimal('cathodic_peak_voltage', 8, 4)->nullable()->after('anodic_peak_voltage');
            }
            if (!Schema::hasColumn('measurements', 'max_current')) {
                $table->decimal('max_current', 14, 6)->nullable()->after('cathodic_peak_voltage');
            }
            if (!Schema::hasColumn('measurements', 'min_current')) {
                $table->decimal('min_current', 14, 6)->nullable()->after('max_current');
            }
            if (!Schema::hasColumn('measurements', 'max_abs_current')) {
                $table->decimal('max_abs_current', 14, 6)->nullable()->after('min_current');
            }
        });
    }

    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            foreach ([
                'cycles', 'quiet_time', 'sensitivity_range',
                'anodic_peak_current', 'cathodic_peak_current',
                'anodic_peak_voltage', 'cathodic_peak_voltage',
                'max_current', 'min_current', 'max_abs_current',
            ] as $col) {
                if (Schema::hasColumn('measurements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
