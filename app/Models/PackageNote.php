<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id', 'note_template_id', 'note_type', 'title', 'content', 'is_important', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_important' => 'boolean',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function noteTemplate(): BelongsTo
    {
        return $this->belongsTo(NoteTemplate::class);
    }
}
