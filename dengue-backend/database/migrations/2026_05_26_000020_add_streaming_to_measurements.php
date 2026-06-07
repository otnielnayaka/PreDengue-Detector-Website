<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah kolom untuk measurement streaming/sesi ke tabel measurements.
 *
 * - scan_status : running | finished | aborted  (status sesi scan)
 * - progress    : 0..100 (persen), diisi alat selama streaming
 * - started_at / finished_at : penanda waktu sesi
 *
 * Kolom hasil (peak_current, peak_voltage, status) sudah ada sebelumnya;
 * saat scan dimulai mereka null, lalu diisi saat finish.
 *
 * AMAN dijalankan ulang (pakai hasColumn).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            if (!Schema::hasColumn('measurements', 'scan_status')) {
                $table->string('scan_status', 20)->default('finished')->after('status');
            }
            if (!Schema::hasColumn('measurements', 'progress')) {
                $table->unsignedTinyInteger('progress')->default(0)->after('scan_status');
            }
            if (!Schema::hasColumn('measurements', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('progress');
            }
            if (!Schema::hasColumn('measurements', 'finished_at')) {
                $table->timestamp('finished_at')->nullable()->after('started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('measurements', function (Blueprint $table) {
            foreach (['finished_at', 'started_at', 'progress', 'scan_status'] as $col) {
                if (Schema::hasColumn('measurements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
