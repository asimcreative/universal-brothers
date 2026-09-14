<?php

namespace App\Models;

use App\Models\Concerns\IsLibraryRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportOption extends Model
{
    use IsLibraryRecord;

    /**
     * The keys `package_transportation.transport_type` stores, in plain words.
     * PackageTransportation::transportLabel() is the public-facing wording.
     */
    public const TYPES = [
        'airport_transfer' => 'Airport transfer',
        'mashaer' => 'Mina, Arafat & Muzdalifah transport',
        'train_or_bus' => 'Train or bus between cities',
        'car_taxi' => 'Car / taxi',
        'vip_gmc' => 'VIP GMC',
        'bus' => 'Bus',
        'other' => 'Other',
    ];

    protected $fillable = [
        'name', 'transport_type', 'from_location', 'to_location', 'description', 'is_included',
        'price', 'currency', 'price_basis', 'notes', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_included' => 'boolean',
            'is_active' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function packageRows(): HasMany
    {
        return $this->hasMany(PackageTransportation::class);
    }
}
