<?php

namespace App\Models;

use App\Models\Concerns\IsLibraryRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A reusable "included" or "not included" line. `description` is the exact
 * customer-facing sentence copied into the package; `title` is the short name
 * the admin picks it by.
 */
class ServiceItem extends Model
{
    use IsLibraryRecord;

    public const LIBRARY_TITLE_COLUMN = 'title';

    public const CATEGORIES = [
        'accommodation' => 'Accommodation',
        'meals' => 'Meals',
        'transport' => 'Transport',
        'mashaer' => 'Mina, Arafat & Muzdalifah',
        'guidance' => 'Guidance & training',
        'visa_ticket' => 'Visa & tickets',
        'qurbani' => 'Qurbani',
        'other' => 'Other',
    ];

    protected $fillable = ['type', 'title', 'description', 'category', 'icon', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeInclusions(Builder $query): Builder
    {
        return $query->where('type', 'inclusion');
    }

    public function scopeExclusions(Builder $query): Builder
    {
        return $query->where('type', 'exclusion');
    }

    public function packageRows(): HasMany
    {
        return $this->hasMany(PackageFeature::class);
    }
}
