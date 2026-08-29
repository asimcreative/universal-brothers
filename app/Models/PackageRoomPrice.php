<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageRoomPrice extends Model
{
    use HasFactory;

    protected $fillable = ['package_price_tier_id', 'room_type', 'price', 'currency', 'sort_order'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function priceTier(): BelongsTo
    {
        return $this->belongsTo(PackagePriceTier::class, 'package_price_tier_id');
    }
}
