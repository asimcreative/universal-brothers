<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageMedia extends Model
{
    use HasFactory;

    protected $table = 'package_media';

    protected $fillable = [
        'package_id', 'media_type', 'image_path', 'video_url',
        'alt_text', 'caption', 'is_featured', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
