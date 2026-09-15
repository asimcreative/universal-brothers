<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One file in the admin media library.
 *
 * Collection "gallery" is the public Media page's photo and video gallery.
 * Collection "library" holds images uploaded while building pages or writing
 * formatted text: offered by every image picker, never listed on the public
 * Media page.
 */
class MediaItem extends Model
{
    use HasFactory;

    public const COLLECTIONS = [
        'gallery' => 'Website gallery',
        'library' => 'Uploaded for pages and text',
    ];

    protected $fillable = [
        'media_type', 'gallery_type', 'collection', 'title', 'alt_text', 'caption', 'file_path', 'video_url',
        'original_name', 'mime_type', 'file_size', 'width', 'height', 'sort_order', 'is_active', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function scopeGallery(Builder $query): Builder
    {
        return $query->where('collection', 'gallery');
    }

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('media_type', 'image')->whereNotNull('file_path');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * A site-relative address ("/storage/media/..."), so content that stores
     * it keeps working whatever domain or port the site is served from.
     */
    public function relativeUrl(): ?string
    {
        return $this->file_path ? '/storage/'.ltrim($this->file_path, '/') : null;
    }

    public function sizeLabel(): string
    {
        if (! $this->file_size) {
            return '';
        }

        return $this->file_size >= 1_048_576
            ? number_format($this->file_size / 1_048_576, 1).' MB'
            : max(1, (int) round($this->file_size / 1024)).' KB';
    }

    /** The shape the media picker works with. */
    public function toPickerArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->relativeUrl(),
            'path' => $this->file_path,
            'title' => $this->title ?: ($this->original_name ?: 'Image'),
            'alt' => (string) $this->alt_text,
            'caption' => (string) $this->caption,
            'width' => $this->width,
            'height' => $this->height,
            'size' => $this->sizeLabel(),
            'collection' => $this->collection,
            'collection_label' => self::COLLECTIONS[$this->collection] ?? '',
        ];
    }
}
