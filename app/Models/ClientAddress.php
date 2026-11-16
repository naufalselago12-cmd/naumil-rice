<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Buku alamat pelanggan setia. Cukup disimpan sekali, bisa tambah alamat lain
 * untuk pengiriman ke tempat berbeda.
 */
class ClientAddress extends Model
{
    protected $fillable = [
        'client_id',
        'label',
        'recipient_name',
        'phone',
        'province_id',
        'province_name',
        'city_id',
        'city_name',
        'district_id',
        'district_name',
        'subdistrict_id',
        'subdistrict_name',
        'postal_code',
        'address',
        'address_note',
        'latitude',
        'longitude',
        'geo_address',
        'is_default',
    ];

    protected $casts = [
        'province_id' => 'integer',
        'city_id' => 'integer',
        'district_id' => 'integer',
        'subdistrict_id' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_default' => 'boolean',
    ];

    protected $appends = ['full_address'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->subdistrict_name,
            $this->district_name,
            $this->city_name,
            $this->province_name,
            $this->postal_code,
        ], fn ($v) => $v !== null && trim((string) $v) !== '');

        return implode(', ', $parts);
    }
}

