<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();

            // Foreign keys (using project-specific _ref naming convention)
            $table->foreignId('device_ref')
                ->constrained('devices')
                ->restrictOnDelete();   // Preserve historical measurements

            $table->foreignId('location_ref')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();       // Location may be removed without losing data

            // Sample tracking identifier from the laboratory workflow
            $table->string('sample_id', 64);

            // Electrochemical method: Differential Pulse, Cyclic, Square Wave Voltammetry
            $table->enum('method', ['DPV', 'CV', 'SWV']);

            // Result metrics — high precision for nA/μA current ranges
            $table->decimal('peak_current', 14, 6)->nullable();   // μA
            $table->decimal('peak_voltage', 8, 4)->nullable();    // V
            $table->decimal('delta_tia', 10, 4)->nullable();      // TIA gain delta
            $table->decimal('threshold', 14, 6)->nullable();      // detection threshold

            // Scan parameters (V)
            $table->decimal('start_voltage', 8, 4);
            $table->decimal('end_voltage', 8, 4);
            $table->decimal('step_voltage', 8, 4);

            // V/s
            $table->decimal('scan_rate', 8, 4)->nullable();

            // Pulse amplitude for DPV/SWV (V)
            $table->decimal('pulse_amplitude', 8, 4)->nullable();

            $table->unsignedInteger('duration_seconds');

            // Diagnostic result classification
            $table->enum('status', [
                'negative',
                'positive',
                'warning',
                'invalid',
                'inconclusive',
            ]);

            $table->timestamps();

            // Indexes optimized for dashboard & analytics queries
            $table->index('device_ref');
            $table->index('location_ref');
            $table->index('status');
            $table->index('method');
            $table->index('created_at');
            $table->index(['status', 'created_at']);  // "positives today" queries
            $table->index('sample_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
