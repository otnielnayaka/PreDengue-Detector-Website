<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoltammetryPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'measurement_id',
        'sequence_number',
        'voltage',
        'current',
        // --- Metadata opsional untuk CV (nullable, lihat migration
        // add_cv_fields_to_voltammetry_points_table). DPV/SWV lama tetap null. ---
        'cycle',
        'direction',
        'time_seconds',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'voltage'         => 'decimal:4',
            'current'         => 'decimal:6',
            'cycle'           => 'integer',
            'time_seconds'    => 'decimal:4',
        ];
    }

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class, 'measurement_id');
    }
}
