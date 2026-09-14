<?php

namespace App\Models;

use App\Models\Concerns\IsLibraryRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A whole-package starting point: options, room types, hotels, journey plan,
 * included/not-included services, transport, notes and upgrades, stored in the
 * same shape the package builder submits (see PackageFormState). Identity
 * fields — code, web address, status — are never stored, so a template can
 * only ever create or fill a draft.
 */
class PackageTemplate extends Model
{
    use IsLibraryRecord;

    protected $fillable = ['name', 'description', 'payload', 'source_package_id', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function sourcePackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'source_package_id')->withTrashed();
    }
}
