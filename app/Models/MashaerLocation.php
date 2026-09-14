<?php

namespace App\Models;

use App\Models\Concerns\IsLibraryRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A reusable Mina, Arafat or Muzdalifah arrangement (camp, tent, meals,
 * transport). Packages copy the fact fields into `package_mashaer_details`.
 */
class MashaerLocation extends Model
{
    use IsLibraryRecord;

    public const LOCATIONS = [
        'mina' => 'Mina',
        'arafat' => 'Arafat',
        'muzdalifah' => 'Muzdalifah',
    ];

    /** The columns a package row shares with this record. */
    public const FACT_FIELDS = [
        'maktab', 'category', 'zone', 'tent_type', 'accommodation_type', 'meal_plan',
        'bathroom', 'air_conditioning', 'transportation', 'other_services', 'notes',
    ];

    protected $fillable = [
        'name', 'location', 'maktab', 'category', 'zone', 'tent_type', 'accommodation_type', 'meal_plan',
        'bathroom', 'air_conditioning', 'transportation', 'other_services', 'description', 'notes',
        'cover_image', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function packageRows(): HasMany
    {
        return $this->hasMany(PackageMashaerDetail::class);
    }
}
