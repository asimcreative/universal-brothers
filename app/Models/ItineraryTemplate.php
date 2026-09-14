<?php

namespace App\Models;

use App\Models\Concerns\IsLibraryRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * A reusable journey plan. Applying it copies the days into a package; the
 * package is never linked back, so later edits to either side are independent.
 */
class ItineraryTemplate extends Model
{
    use IsLibraryRecord;

    protected $fillable = ['name', 'description', 'days', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'days' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
