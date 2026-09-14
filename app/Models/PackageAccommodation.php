<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageAccommodation extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id', 'variant_id', 'hotel_id', 'location', 'hotel_name', 'star_rating',
        'meal_plan', 'meal_plan_id', 'distance_note', 'nights', 'notes', 'sort_order',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(PackageVariant::class, 'variant_id');
    }

    /** The library hotel this row was copied from, if any. */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }
}
