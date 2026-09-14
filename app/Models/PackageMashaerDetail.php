<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageMashaerDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id', 'mashaer_location_id', 'location', 'maktab', 'category', 'zone', 'tent_type',
        'accommodation_type', 'meal_plan', 'bathroom', 'air_conditioning',
        'transportation', 'other_services', 'notes', 'sort_order',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function mashaerLocation(): BelongsTo
    {
        return $this->belongsTo(MashaerLocation::class);
    }
}
