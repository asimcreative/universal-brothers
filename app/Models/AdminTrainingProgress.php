<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One admin's place in one training video chapter. */
class AdminTrainingProgress extends Model
{
    protected $table = 'admin_training_progress';

    protected $fillable = [
        'user_id', 'chapter_key', 'position_seconds', 'furthest_seconds',
        'duration_seconds', 'completed_at', 'last_watched_at',
    ];

    protected function casts(): array
    {
        return [
            'position_seconds' => 'integer',
            'furthest_seconds' => 'integer',
            'duration_seconds' => 'integer',
            'completed_at' => 'datetime',
            'last_watched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
