<?php

namespace App\Models;

use App\Models\Concerns\IsLibraryRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealPlan extends Model
{
    use IsLibraryRecord;

    protected $fillable = [
        'name', 'description', 'includes_breakfast', 'includes_lunch', 'includes_dinner',
        'is_included', 'notes', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'includes_breakfast' => 'boolean',
            'includes_lunch' => 'boolean',
            'includes_dinner' => 'boolean',
            'is_included' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function accommodations(): HasMany
    {
        return $this->hasMany(PackageAccommodation::class);
    }

    public function mealsSummary(): string
    {
        $meals = array_keys(array_filter([
            'Breakfast' => $this->includes_breakfast,
            'Lunch' => $this->includes_lunch,
            'Dinner' => $this->includes_dinner,
        ]));

        return $meals ? implode(', ', $meals) : 'No meals';
    }
}
