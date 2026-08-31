<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageRoomOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id', 'variant_id', 'sharing_type', 'occupancy', 'display_label',
        'price_basis', 'price_pkr', 'price_sar', 'price_usd', 'is_available',
        'notes', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'price_pkr' => 'decimal:2',
            'price_sar' => 'decimal:2',
            'price_usd' => 'decimal:2',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(PackageVariant::class, 'variant_id');
    }

    public function priceFor(string $currency): ?string
    {
        return match (strtoupper($currency)) {
            'PKR' => $this->price_pkr,
            'SAR' => $this->price_sar,
            default => $this->price_usd,
        };
    }
}
