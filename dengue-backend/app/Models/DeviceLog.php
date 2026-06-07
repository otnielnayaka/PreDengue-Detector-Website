<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_ref',
        'battery_percent',
        'battery_voltage',
        'wifi_rssi',
        'sd_status',
        'free_storage_mb',
    ];

    protected function casts(): array
    {
        return [
            'battery_percent' => 'integer',
            'battery_voltage' => 'decimal:2',
            'wifi_rssi'       => 'integer',
            'free_storage_mb' => 'integer',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_ref');
    }
}
