<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackagePriceTier extends Model
{
    use HasFactory;

    protected $fillable = ['package_id', 'label', 'hotel_note', 'sort_order'];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function roomPrices(): HasMany
    {
        return $this->hasMany(PackageRoomPrice::class)->orderBy('sort_order');
    }
}
