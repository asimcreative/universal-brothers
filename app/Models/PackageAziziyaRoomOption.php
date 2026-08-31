<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageAziziyaRoomOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_aziziya_id', 'variant_id', 'sharing_type', 'occupancy', 'display_label',
        'pricing_type', 'price_basis', 'price_pkr', 'price_sar', 'price_usd',
        'description', 'notes', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_pkr' => 'decimal:2',
            'price_sar' => 'decimal:2',
            'price_usd' => 'decimal:2',
        ];
    }

    public function aziziya(): BelongsTo
    {
        return $this->belongsTo(PackageAziziya::class, 'package_aziziya_id');
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
