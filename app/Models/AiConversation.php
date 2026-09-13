<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    protected $fillable = [
        'uuid', 'ip_hash', 'locale', 'source_page',
        'message_count', 'lead_captured', 'inquiry_id', 'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'lead_captured' => 'boolean',
            'message_count' => 'integer',
            'last_activity_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }
}
