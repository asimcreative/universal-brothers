<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageUpgrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id', 'upgrade_option_id', 'name', 'description', 'price', 'currency',
        'price_basis', 'is_included', 'notes', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_included' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function upgradeOption(): BelongsTo
    {
        return $this->belongsTo(UpgradeOption::class);
    }
}
