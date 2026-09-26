<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PackageTransportation extends Model
{
    use HasFactory;

    protected $table = 'package_transportation';

    protected $fillable = [
        'package_id', 'transport_option_id', 'from_location', 'to_location', 'transport_type',
        'is_included', 'price', 'currency', 'price_pkr', 'price_sar', 'price_usd',
        'price_basis', 'notes', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_included' => 'boolean',
            'price' => 'decimal:2',
            'price_pkr' => 'decimal:2',
            'price_sar' => 'decimal:2',
            'price_usd' => 'decimal:2',
        ];
    }

    /**
     * This leg's price in one currency, or null if that brochure does not
     * publish it. Never converted — see PackageUpgrade::priceIn().
     */
    public function priceIn(?string $currency = null): ?float
    {
        $value = $this->{\App\Support\Currency::column($currency ?? \App\Support\Currency::current())};

        return $value === null ? null : (float) $value;
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Human-readable name for the stored `transport_type` key.
     *
     * The column holds snake_case identifiers (`airport_transfer`, `mashaer`,
     * `train_or_bus`, `car_taxi`, `vip_gmc`) and the Hajj package detail page
     * was printing them verbatim, so every visitor read "airport_transfer" and
     * "vip_gmc" in the Transportation section of a premium package page.
     *
     * The mapping lives on the model rather than in the Blade template so any
     * other consumer — a future export, the admin UI, an API — gets the same
     * label instead of re-deriving it. An unmapped value degrades to a
     * title-cased version of itself rather than disappearing, so adding a new
     * type to the enum can never blank the row.
     */
    public function transportLabel(): string
    {
        return match ($this->transport_type) {
            'airport_transfer' => 'Airport Transfer',
            'mashaer' => 'Mashaer Transport',
            'train_or_bus' => 'Train or Bus',
            'car_taxi' => 'Car / Taxi',
            'vip_gmc' => 'VIP GMC',
            default => Str::headline((string) $this->transport_type),
        };
    }

    public function transportOption(): BelongsTo
    {
        return $this->belongsTo(TransportOption::class);
    }
}
