<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'kecamatan',
        'desa',
        'latitude',
        'longitude',
        'province',
        'city_regency',
        'location_name',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude'  => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }
    
    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class, 'location_ref');
    }
    
}
