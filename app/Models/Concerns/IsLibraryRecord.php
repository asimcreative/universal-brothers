<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared behaviour for reusable package content (the "library").
 *
 * Library records are archived, never silently deleted while in use: an
 * archived record disappears from the package builder's pickers, but every
 * package that already copied it keeps its content and its link.
 *
 * A model whose display column is not `name` declares a
 * `LIBRARY_TITLE_COLUMN` constant.
 */
trait IsLibraryRecord
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy(static::libraryTitleColumn());
    }

    public function libraryTitle(): string
    {
        return (string) $this->{static::libraryTitleColumn()};
    }

    public static function libraryTitleColumn(): string
    {
        return defined(static::class.'::LIBRARY_TITLE_COLUMN') ? static::LIBRARY_TITLE_COLUMN : 'name';
    }
}
