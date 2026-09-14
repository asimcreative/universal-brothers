<?php

namespace App\Models;

use App\Models\Concerns\IsLibraryRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A reusable hotel or accommodation — a Makkah or Madinah hotel, the Aziziya
 * building, or a Mina/Arafat camp. Packages copy these values into their own
 * `package_accommodations` rows and keep a link back through `hotel_id`.
 */
class Hotel extends Model
{
    use HasFactory, IsLibraryRecord;

    public const LOCATIONS = [
        'makkah' => 'Makkah',
        'medinah' => 'Madinah',
        'aziziya' => 'Aziziya',
        'mina' => 'Mina',
        'arafat' => 'Arafat',
        'other' => 'Other',
    ];

    protected $fillable = [
        'name', 'slug', 'city', 'location', 'address', 'star_rating', 'description', 'cover_image',
        'website_url', 'map_url', 'notes', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'star_rating' => 'integer',
        ];
    }

    public function accommodations(): HasMany
    {
        return $this->hasMany(PackageAccommodation::class);
    }

    public function locationLabel(): string
    {
        return self::LOCATIONS[$this->location] ?? ucfirst((string) $this->location);
    }
}
