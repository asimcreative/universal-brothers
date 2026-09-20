<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'package_category_id', 'package_series_id', 'code', 'name', 'package_type', 'slug',
        'summary', 'description', 'duration_days', 'duration_label',
        'is_shifting', 'medinah_first', 'has_aziziya', 'season_year', 'season_label',
        'currency', 'starting_price', 'cover_image', 'gallery',
        'is_featured', 'is_seasonal', 'is_promotional', 'status',
        'published_at', 'sort_order', 'meta_title', 'meta_description',
        'internal_notes', 'social_image',
    ];

    /**
     * Admin-only commentary. Hidden from serialisation so a package passed to
     * JSON — an API response, a log line, the AI context — can never carry it.
     * Nothing public reads the column; this guards against the accidental case.
     */
    protected $hidden = ['internal_notes'];

    protected function casts(): array
    {
        return [
            'is_shifting' => 'boolean',
            'medinah_first' => 'boolean',
            'has_aziziya' => 'boolean',
            'is_featured' => 'boolean',
            'is_seasonal' => 'boolean',
            'is_promotional' => 'boolean',
            'gallery' => 'array',
            'starting_price' => 'decimal:2',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
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

    public function variants(): HasMany
    {
        return $this->hasMany(PackageVariant::class)->orderBy('sort_order');
    }

    public function accommodations(): HasMany
    {
        return $this->hasMany(PackageAccommodation::class)->orderBy('sort_order');
    }

    public function roomOptions(): HasMany
    {
        return $this->hasMany(PackageRoomOption::class)->orderBy('sort_order');
    }

    public function aziziya(): HasOne
    {
        return $this->hasOne(PackageAziziya::class);
    }

    public function mashaerDetails(): HasMany
    {
        return $this->hasMany(PackageMashaerDetail::class)->orderBy('sort_order');
    }

    public function transportation(): HasMany
    {
        return $this->hasMany(PackageTransportation::class)->orderBy('sort_order');
    }

    public function packageNotes(): HasMany
    {
        return $this->hasMany(PackageNote::class)->orderBy('sort_order');
    }

    public function upgrades(): HasMany
    {
        return $this->hasMany(PackageUpgrade::class)->orderBy('sort_order');
    }

    public function media(): HasMany
    {
        return $this->hasMany(PackageMedia::class)->orderBy('sort_order');
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

    public function isHajj(): bool
    {
        return $this->category?->slug === 'hajj';
    }

    /**
     * `summary` on all 35 Tourism packages literally stores an internal
     * data-recovery note ("Recovered from the live tourism...listing
     * pages... this content could not be recovered and is not invented
     * here") — an honest, deliberate placeholder from an earlier pass,
     * written for an internal audience, not a customer-facing sentence.
     * The raw column is left untouched (it's real documentation of why
     * these packages are incomplete), but no public page should render it
     * verbatim as if it were marketing copy. Views should call this
     * instead of `summary` wherever the value reaches a visitor.
     */
    public function publicSummary(): ?string
    {
        if ($this->summary && str_contains($this->summary, 'is not invented here')) {
            return 'Full package details are being finalized — please contact us for the latest itinerary and pricing.';
        }

        return $this->summary;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Only the packages that carry a price in this currency.
     *
     * The three brochures are separate price lists, not conversions: every
     * package has a rupee and a riyal price, and only twelve also have a
     * dollar one. Listing all of them in dollars and showing "N/A" on
     * fourteen makes the site look broken; showing twelve is the truth.
     *
     * A package with no room options at all — one being built in the admin,
     * or a tourism package priced a different way — is left in, because its
     * price does not come from this table and filtering it out would empty
     * the tourism listing.
     */
    public function scopePricedIn(Builder $query, string $currency): Builder
    {
        $column = \App\Support\Currency::column($currency);

        return $query->where(
            fn (Builder $q) => $q
                ->whereDoesntHave('roomOptions')
                ->orWhereHas('roomOptions', fn (Builder $r) => $r->whereNotNull($column)->where('is_available', true)),
        );
    }

    /**
     * The lowest published per-person price in this currency, or null.
     *
     * Read from the room options rather than the `starting_price` column,
     * which holds one number in one currency and cannot answer this.
     */
    public function startingPriceIn(string $currency): ?float
    {
        $column = \App\Support\Currency::column($currency);

        $lowest = $this->relationLoaded('roomOptions')
            ? $this->roomOptions->where('is_available', true)->whereNotNull($column)->min($column)
            : $this->roomOptions()->where('is_available', true)->whereNotNull($column)->min($column);

        // A package priced outside the room-option table (tourism, mostly)
        // falls back to its own column, but only when that column is in the
        // currency being asked for — otherwise it would quote rupees as
        // dollars.
        if ($lowest === null && strtoupper($currency) === strtoupper((string) $this->currency)) {
            return $this->starting_price !== null ? (float) $this->starting_price : null;
        }

        return $lowest !== null ? (float) $lowest : null;
    }

    /**
     * The highest published per-person price in this currency, or null.
     *
     * The pair of these is what a listing needs: a package with two hotel
     * options and three room types spans a real range, and quoting only the
     * bottom of it makes every package look like its cheapest room.
     */
    public function endingPriceIn(string $currency): ?float
    {
        $column = \App\Support\Currency::column($currency);

        $highest = $this->relationLoaded('roomOptions')
            ? $this->roomOptions->where('is_available', true)->whereNotNull($column)->max($column)
            : $this->roomOptions()->where('is_available', true)->whereNotNull($column)->max($column);

        return $highest !== null ? (float) $highest : null;
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
