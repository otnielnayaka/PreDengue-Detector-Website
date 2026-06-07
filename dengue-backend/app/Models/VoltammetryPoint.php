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
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'voltage'         => 'decimal:4',
            'current'         => 'decimal:6',
        ];
    }

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class, 'measurement_id');
    }
}
