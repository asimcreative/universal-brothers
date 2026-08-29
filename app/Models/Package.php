<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'package_category_id', 'package_series_id', 'code', 'name', 'slug',
        'summary', 'description', 'duration_days', 'duration_label',
        'is_shifting', 'has_aziziya', 'season_year', 'season_label',
        'currency', 'starting_price', 'cover_image', 'gallery',
        'is_featured', 'is_seasonal', 'is_promotional', 'status',
        'published_at', 'sort_order', 'meta_title', 'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'is_shifting' => 'boolean',
            'has_aziziya' => 'boolean',
            'is_featured' => 'boolean',
            'is_seasonal' => 'boolean',
            'is_promotional' => 'boolean',
            'gallery' => 'array',
            'starting_price' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PackageCategory::class, 'package_category_id');
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(PackageSeries::class, 'package_series_id');
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(PackagePriceTier::class)->orderBy('sort_order');
    }

    public function itineraryDays(): HasMany
    {
        return $this->hasMany(PackageItineraryDay::class)->orderBy('day_number');
    }

    public function inclusions(): HasMany
    {
        return $this->hasMany(PackageFeature::class)->where('type', 'inclusion')->orderBy('sort_order');
    }

    public function exclusions(): HasMany
    {
        return $this->hasMany(PackageFeature::class)->where('type', 'exclusion')->orderBy('sort_order');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(PackageAddon::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
