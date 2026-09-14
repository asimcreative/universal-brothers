<?php

namespace App\Support\Packages;

use App\Models\Package;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Copies a Hajj package into a new draft.
 *
 * The copy is always a draft, never featured and never archived, with its own
 * code and web address — so duplicating can never put a second copy of a live
 * package on the website, or clash with the original's URL.
 *
 * Content goes through PackageFormState and HajjPackageWriter, the same path
 * as saving from the builder: options are recreated for the copy and every
 * hotel, room price and Aziziya row is re-pointed at the COPY's options, and
 * library links are kept.
 *
 * Images are copied as new files, not shared. The builder deletes an image's
 * file when its row is removed or replaced, so two packages pointing at one
 * file would let an edit to the copy delete the original's photograph. A file
 * that is missing from disk is simply not carried over.
 */
class PackageDuplicator
{
    public function __construct(private HajjPackageWriter $writer) {}

    public function duplicate(Package $source): Package
    {
        return DB::transaction(function () use ($source) {
            $state = PackageFormState::fromPackage($source);

            $copy = $source->replicate([
                'code', 'slug', 'status', 'published_at', 'archived_at', 'is_featured',
                'cover_image', 'social_image', 'starting_price', 'deleted_at',
            ]);

            $copy->name = Str::limit($source->name, 240, '').' (Copy)';
            $copy->code = self::uniqueCode($source->code);
            $copy->slug = self::uniqueSlug($copy->name);
            $copy->status = 'draft';
            $copy->is_featured = false;
            $copy->published_at = null;
            $copy->archived_at = null;
            $copy->sort_order = (int) Package::withTrashed()->where('package_category_id', $source->package_category_id)->max('sort_order') + 1;
            $copy->cover_image = self::copyFile($source->cover_image);
            $copy->social_image = self::copyFile($source->social_image);
            $copy->save();

            $this->writer->syncNested($copy, $state);

            foreach ($source->media as $media) {
                $path = self::copyFile($media->image_path);

                if (! $path && blank($media->video_url)) {
                    continue;
                }

                $copy->media()->create([
                    'media_type' => $media->media_type,
                    'image_path' => $path,
                    'video_url' => $media->video_url,
                    'alt_text' => $media->alt_text,
                    'caption' => $media->caption,
                    'is_featured' => $media->is_featured,
                    'sort_order' => $media->sort_order,
                ]);
            }

            return $copy;
        });
    }

    public static function uniqueCode(?string $code): ?string
    {
        if (blank($code)) {
            return null;
        }

        $base = Str::limit(strtoupper($code), 40, '').'-COPY';
        $candidate = $base;
        $i = 2;

        while (Package::withTrashed()->where('code', $candidate)->exists()) {
            $candidate = "{$base}-{$i}";
            $i++;
        }

        return $candidate;
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::limit(Str::slug($name), 200, '') ?: 'hajj-package';
        $candidate = $base;
        $i = 2;

        while (Package::withTrashed()->where('slug', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = "{$base}-{$i}";
            $i++;
        }

        return $candidate;
    }

    private static function copyFile(?string $path): ?string
    {
        if (blank($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $target = dirname($path).'/'.Str::uuid().($extension ? ".{$extension}" : '');

        return Storage::disk('public')->copy($path, $target) ? $target : null;
    }
}
