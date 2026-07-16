<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'kecamatan' => $this->kecamatan,
            'desa'      => $this->desa,
            // Alias baru (Section 2). Project belum punya kolom district/
            // village terpisah — nilainya sama dengan kecamatan/desa. Kalau
            // suatu saat ada kolom district/village asli, tinggal ganti
            // baris ini tanpa mengubah kontrak field 'district'/'village'.
            'district'      => $this->kecamatan,
            'village'       => $this->desa,
            'province'      => $this->province,
            'city_regency'  => $this->city_regency,
            'location_name' => $this->location_name,
            'notes'         => $this->notes,
            'latitude'  => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
