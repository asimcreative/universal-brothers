<?php

namespace App\Models;

use App\Support\Content\RichText;
use App\Support\PageBuilder\PageDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasFactory;

    public const STATUSES = [
        'draft' => 'Draft',
        'published' => 'Published',
        'scheduled' => 'Scheduled',
        'archived' => 'Archived',
    ];

    protected $fillable = [
        'title', 'slug', 'body', 'sections', 'draft', 'featured_image', 'template', 'status', 'is_active',
        'published_at', 'meta_title', 'meta_description', 'focus_keyword', 'canonical_url',
        'og_title', 'og_description', 'og_image', 'noindex', 'updated_by', 'published_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'noindex' => 'boolean',
            'published_at' => 'datetime',
            'sections' => 'array',
            'draft' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Older code and seeders only set `is_active`. Keep the status label
        // in step with it unless the status itself was set deliberately.
        static::saving(function (Page $page) {
            if (! $page->exists && $page->getAttribute('status') === null) {
                $page->status = $page->is_active ? 'published' : 'draft';
            } elseif ($page->isDirty('is_active') && ! $page->isDirty('status') && $page->status !== 'archived') {
                $page->status = $page->is_active ? 'published' : 'draft';
            }
        });
    }

    /** Pages a visitor may see right now: published, or scheduled for a time that has passed. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class)->latest('id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /** Built with the section builder (published), rather than the original single-body layout. */
    public function usesSections(): bool
    {
        return is_array($this->sections);
    }

    /** A saved draft that differs from what visitors see. */
    public function hasUnpublishedChanges(): bool
    {
        return is_array($this->draft)
            && ($this->displayStatus() !== 'published' || $this->draft !== PageDocument::fromPublished($this)->toArray());
    }

    public function isLive(): bool
    {
        return $this->is_active && ($this->published_at === null || $this->published_at->isPast());
    }

    /** The label an admin sees: a scheduled page whose time has come reads as published. */
    public function displayStatus(): string
    {
        if ($this->status === 'scheduled' && $this->isLive()) {
            return 'published';
        }

        return array_key_exists((string) $this->status, self::STATUSES) ? $this->status : ($this->is_active ? 'published' : 'draft');
    }

    /** Readable text of the page, for search descriptions and the AI assistant. */
    public function plainText(): string
    {
        return RichText::toPlainText($this->body);
    }
}
