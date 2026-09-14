<?php

namespace App\Models;

use App\Models\Concerns\IsLibraryRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UpgradeOption extends Model
{
    use IsLibraryRecord;

    protected $fillable = [
        'name', 'description', 'price', 'currency', 'price_basis', 'conditions', 'is_included',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_included' => 'boolean',
            'is_active' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function packageRows(): HasMany
    {
        return $this->hasMany(PackageUpgrade::class);
    }
}
