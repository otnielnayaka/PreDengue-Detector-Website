<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            $table->string('kecamatan', 100);
            $table->string('desa', 100);

            // Geo coordinates with ~1cm precision
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            // Composite index for fast administrative lookups
            $table->index(['kecamatan', 'desa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
