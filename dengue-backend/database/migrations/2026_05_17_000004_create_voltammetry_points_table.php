<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voltammetry points: high-volume raw scan data.
 *
 * Each measurement typically generates 200–1000 (V, I) sample points.
 * This table will grow fastest in the system — design accordingly:
 *
 *  - Composite index (measurement_id, sequence_number) for ordered graph fetch
 *  - CASCADE delete because points are meaningless without their parent scan
 *  - No FK to anything else (kept narrow & fast for bulk INSERT)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voltammetry_points', function (Blueprint $table) {
            $table->id();

            $table->foreignId('measurement_id')
                ->constrained('measurements')
                ->cascadeOnDelete();

            // Order of acquisition along the scan
            $table->unsignedInteger('sequence_number');

            $table->decimal('voltage', 8, 4);     // V
            $table->decimal('current', 14, 6);    // μA (nA precision)

            $table->timestamps();

            // Composite index — graph rendering reads points
            // ORDER BY sequence_number for a given measurement_id
            $table->index(['measurement_id', 'sequence_number'], 'vp_measurement_seq_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voltammetry_points');
    }
};
