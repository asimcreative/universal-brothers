<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One searchable chunk of approved Universal Brothers content.
 *
 * Holds text for FINDING things — titles, descriptions, hotel names, itinerary
 * cities, FAQ answers. It deliberately holds no prices: those are read live
 * from the package tables when an answer is built, so a stale index can never
 * put a superseded figure in front of a visitor.
 */
class AiKnowledgeEntry extends Model
{
    protected $fillable = [
        'source_type', 'source_id', 'reference', 'title',
        'url', 'category', 'body', 'keywords', 'weight',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
        ];
    }
}
