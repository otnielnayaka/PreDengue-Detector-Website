<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'firmware_version',
        'battery_voltage',
        'wifi_status',
        'last_online',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'battery_voltage' => 'decimal:2',
            'last_online'    => 'datetime',
        ];
    }

    // ---------- Relationships ----------

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class, 'device_ref');
    }

    public function deviceLogs(): HasMany
    {
        return $this->hasMany(DeviceLog::class, 'device_ref');
    }

    public function latestLog()
    {
        return $this->hasOne(DeviceLog::class, 'device_ref')->latestOfMany();
    }

    public function latestMeasurement()
    {
        return $this->hasOne(Measurement::class, 'device_ref')->latestOfMany();
    }

    // ---------- Scopes ----------

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }
}
