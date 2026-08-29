<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageItineraryDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id', 'day_number', 'date_gregorian', 'date_hijri_label',
        'city', 'accommodation_a', 'accommodation_b', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_gregorian' => 'date',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
