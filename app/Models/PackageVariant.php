<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageVariant extends Model
{
    use HasFactory;

    protected $fillable = ['package_id', 'code', 'label', 'sort_order'];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
