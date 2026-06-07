<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('device_ref')
                ->constrained('devices')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('battery_percent')->nullable();   // 0–100
            $table->decimal('battery_voltage', 4, 2)->nullable();         // V

            // RSSI is negative (e.g. -65 dBm)
            $table->smallInteger('wifi_rssi')->nullable();

            // 'ok', 'full', 'missing', 'error'
            $table->string('sd_status', 32)->nullable();
            $table->unsignedInteger('free_storage_mb')->nullable();

            $table->timestamps();

            // Telemetry timeline queries: "logs for device X over last hour"
            $table->index(['device_ref', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_logs');
    }
};
