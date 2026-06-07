<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            // Hardware identifier (e.g. MAC address or "POT-001"). Unique per device.
            $table->string('device_id', 64)->unique();

            $table->string('firmware_version', 32)->nullable();

            // Battery voltage in volts, e.g. 3.85
            $table->decimal('battery_voltage', 4, 2)->nullable();

            // 'connected', 'disconnected', 'weak'
            $table->string('wifi_status', 32)->nullable();

            $table->timestamp('last_online')->nullable();

            // 'online', 'offline', 'maintenance', 'error'
            $table->string('status', 32)->default('offline');

            $table->timestamps();

            // Indexes for dashboard queries
            $table->index('status');
            $table->index('last_online');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
