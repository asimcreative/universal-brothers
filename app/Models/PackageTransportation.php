<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageTransportation extends Model
{
    use HasFactory;

    protected $table = 'package_transportation';

    protected $fillable = [
        'package_id', 'from_location', 'to_location', 'transport_type',
        'is_included', 'price', 'currency', 'price_basis', 'notes', 'sort_order',
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
}
