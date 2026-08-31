<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageAziziyaService extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_aziziya_id', 'name', 'description', 'is_included',
        'price', 'currency', 'price_basis', 'notes', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_included' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function aziziya(): BelongsTo
    {
        return $this->belongsTo(PackageAziziya::class, 'package_aziziya_id');
    }
}
