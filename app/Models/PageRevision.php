<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A copy of a whole page, kept each time it is published, scheduled, unpublished or restored. */
class PageRevision extends Model
{
    public const ACTIONS = [
        'published' => 'Published',
        'scheduled' => 'Scheduled',
        'unpublished' => 'Unpublished',
        'restored' => 'Earlier version restored as a draft',
    ];

    /** Older versions beyond this many are removed, per page. */
    public const KEEP = 30;

    protected $fillable = ['page_id', 'action', 'data', 'created_by'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
