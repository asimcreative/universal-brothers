<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageAziziya extends Model
{
    use HasFactory;

    protected $table = 'package_aziziya';

    protected $fillable = [
        'package_id', 'status', 'accommodation_name', 'location_note',
        'walk_distance', 'duration_days', 'average_occupancy', 'description', 'notes',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function roomOptions(): HasMany
    {
        return $this->hasMany(PackageAziziyaRoomOption::class)->orderBy('sort_order');
    }

    public function services(): HasMany
    {
        return $this->hasMany(PackageAziziyaService::class)->orderBy('sort_order');
    }
}
