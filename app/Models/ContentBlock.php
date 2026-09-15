<?php

namespace App\Models;

use App\Support\PageBuilder\BlockRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * A saved section: one page-builder section kept so it can be inserted into
 * other pages — as an independent copy (the default), or, for super admins, as
 * a linked section that always shows this saved section's current content.
 */
class ContentBlock extends Model
{
    public const CATEGORIES = [
        'general' => 'General',
        'call-to-action' => 'Call to action',
        'trust' => 'Trust and company',
        'hajj' => 'Hajj',
        'umrah' => 'Umrah',
        'tourism' => 'Tourism',
        'policies' => 'Terms and policies',
        'contact' => 'Contact',
    ];

    protected $fillable = ['name', 'description', 'category', 'type', 'data', 'is_archived', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_archived' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function typeName(): string
    {
        return BlockRegistry::find($this->type)['name'] ?? 'Section';
    }

    /** Pages whose published sections or saved draft link to this saved section. */
    public function linkedPages(): Collection
    {
        $patterns = ['%"block_id":'.$this->id.',%', '%"block_id":'.$this->id.'}%'];

        return Page::query()
            ->where(function (Builder $q) use ($patterns) {
                foreach ($patterns as $pattern) {
                    $q->orWhere('sections', 'like', $pattern)->orWhere('draft', 'like', $pattern);
                }
            })
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'status', 'is_active', 'published_at']);
    }
}
