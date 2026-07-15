<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Measurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_ref',
        'location_ref',
        'sample_id',
        'method',
        'peak_current',
        'peak_voltage',
        'delta_tia',
        'threshold',
        'start_voltage',
        'end_voltage',
        'step_voltage',
        'scan_rate',
        'pulse_amplitude',
        'duration_seconds',
        'status',
        // --- Parameter & summary khusus CV (nullable, lihat migration
        // add_cv_fields_to_measurements_table) ---
        'cycles',
        'quiet_time',
        'sensitivity_range',
        'anodic_peak_current',
        'cathodic_peak_current',
        'anodic_peak_voltage',
        'cathodic_peak_voltage',
        'max_current',
        'min_current',
        'max_abs_current',
    ];

    protected function casts(): array
    {
        return [
            'peak_current'     => 'decimal:6',
            'peak_voltage'     => 'decimal:4',
            'delta_tia'        => 'decimal:6',
            'threshold'        => 'decimal:6',
            'start_voltage'    => 'decimal:4',
            'end_voltage'      => 'decimal:4',
            'step_voltage'     => 'decimal:4',
            'scan_rate'        => 'decimal:4',
            'pulse_amplitude'  => 'decimal:4',
            'duration_seconds' => 'integer',
            'cycles'                 => 'integer',
            'quiet_time'              => 'decimal:4',
            'anodic_peak_current'     => 'decimal:6',
            'cathodic_peak_current'   => 'decimal:6',
            'anodic_peak_voltage'     => 'decimal:4',
            'cathodic_peak_voltage'   => 'decimal:4',
            'max_current'             => 'decimal:6',
            'min_current'             => 'decimal:6',
            'max_abs_current'         => 'decimal:6',
        ];
    }

    // ---------- Relationships ----------

    /**
     * Device yang melakukan measurement ini.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_ref');
    }

    /**
     * Lokasi sample diambil.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_ref');
    }

    /**
     * Voltammetry points — titik-titik (voltage, current) yang membentuk kurva.
     *
     * Diakses dengan nama 'points' supaya frontend gampang baca:
     *   $measurement->points → array of points
     *
     * Sekaligus didefinisikan ulang sebagai 'voltammetryPoints' untuk
     * kompatibilitas kalau ada code lama yang masih pakai nama lengkap.
     */
    public function points(): HasMany
    {
        return $this->hasMany(VoltammetryPoint::class, 'measurement_id')
            ->orderBy('sequence_number');
    }

    /**
     * Alias panjang — sama dengan points().
     */
    public function voltammetryPoints(): HasMany
    {
        return $this->points();
    }

    // ---------- Scopes ----------

    public function scopePositive($query)
    {
        return $query->where('status', 'positive');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }


    // (relasi device() Anda yang sudah ada tetap dipakai untuk device_id di peta)

}
