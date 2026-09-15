<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HajjPackageRequest;
use App\Models\AdminActivity;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageTemplate;
use App\Support\Content\RichText;
use App\Support\HajjPackagePage;
use App\Support\Library\PackageBuilderData;
use App\Support\Packages\HajjPackageWriter;
use App\Support\Packages\PackageCompleteness;
use App\Support\Packages\PackageDuplicator;
use App\Support\Packages\PackageFormState;
use App\Support\Packages\PackagePreview;
use App\Support\Packages\PackageReview;
use App\Support\Packages\PackageTemplates;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * The Hajj package builder and listing.
 *
 * Hajj packages have their own admin surface, separate from the generic
 * Admin\PackageController that Umrah and Tourism use: options A/B/C, room
 * prices in three currencies, Aziziya, Mina/Arafat/Muzdalifah, transport,
 * notes and upgrades have no place in the generic schema. Saving goes through
 * HajjPackageWriter; this controller decides status, files, redirects and the
 * record of who did what.
 */
class HajjPackageController extends Controller
{
    public function __construct(
        private HajjPackageWriter $writer,
        private PackageDuplicator $duplicator,
        private PackageTemplates $templates,
    ) {}

    public function index(Request $request): View
    {
        $category = $this->hajjCategory();
        $base = Package::where('package_category_id', $category->id);

        $status = $request->string('status')->toString();
        $sort = $request->string('sort')->toString() ?: 'order';

        $packages = (clone $base)
            ->with(array_merge(['series'], PackageFormState::RELATIONS))
            ->when($status === 'archived', fn ($q) => $q->archived(), fn ($q) => $q->notArchived())
            ->when(in_array($status, ['published', 'draft'], true), fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $q->where(fn ($inner) => $inner->where('name', 'like', $term)->orWhere('code', 'like', $term));
            })
            ->when($request->input('featured') === 'yes', fn ($q) => $q->where('is_featured', true))
            ->when($request->input('featured') === 'no', fn ($q) => $q->where('is_featured', false))
            ->when($request->filled('series'), fn ($q) => $q->where('package_series_id', $request->integer('series')))
            ->when($request->input('arrival') === 'madinah', fn ($q) => $q->where('medinah_first', true))
            ->when($request->input('arrival') === 'makkah', fn ($q) => $q->where('medinah_first', false))
            ->when($request->filled('aziziya'), fn ($q) => $q->whereHas('aziziya', fn ($a) => $a->where('status', $request->string('aziziya'))))
            ->when($request->filled('days'), fn ($q) => $q->where('duration_days', $request->integer('days')))
            ->when($sort === 'updated', fn ($q) => $q->latest('updated_at'))
            ->when($sort === 'title', fn ($q) => $q->orderBy('name'))
            ->when($sort === 'order', fn ($q) => $q->orderBy('sort_order')->orderBy('name'))
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => (clone $base)->notArchived()->count(),
            'published' => (clone $base)->notArchived()->where('status', 'published')->count(),
            'draft' => (clone $base)->notArchived()->where('status', 'draft')->count(),
            'archived' => (clone $base)->archived()->count(),
        ];

        return view('admin.hajj-packages.index', [
            'packages' => $packages,
            'progress' => $packages->getCollection()->mapWithKeys(fn (Package $p) => [$p->id => PackageReview::forPackage($p)]),
            'counts' => $counts,
            'series' => $category->series()->orderBy('sort_order')->get(),
            'durations' => (clone $base)->whereNotNull('duration_days')->distinct()->orderBy('duration_days')->pluck('duration_days'),
            'templates' => PackageTemplate::active()->ordered()->get(['id', 'name']),
            'filtersActive' => $request->hasAny(['q', 'featured', 'series', 'arrival', 'aziziya', 'days']),
        ]);
    }

    public function create(Request $request): View
    {
        $category = $this->hajjCategory();
        $template = $request->filled('template') ? PackageTemplate::active()->find($request->integer('template')) : null;

        $state = $template ? PackageFormState::fromTemplate($template) : PackageFormState::blank();
        $state['season_year'] ??= (int) now()->year + 1;

        return $this->builder(new Package(['package_category_id' => $category->id]), $state, $template);
    }

    public function store(HajjPackageRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $package = new Package;
        $package->package_category_id = $this->hajjCategory()->id;
        $package->currency = 'USD';
        $package->sort_order = $data['sort_order'] ?? ((int) Package::where('package_category_id', $package->package_category_id)->max('sort_order') + 1);
        $this->fillBasics($package, $data, $request);
        $package->save();

        $this->writer->syncNested($package, $data);
        $this->syncMedia($package, $request);
        $this->recordProgress($package, $request);

        AdminActivity::record('created', $package, "Created Hajj package {$package->code} — {$package->name} as ".($package->isPublished() ? 'published' : 'a draft').'.');

        return $this->afterSave($package, $request, created: true);
    }

    public function edit(Request $request, Package $package): View|RedirectResponse
    {
        if (! $package->isHajj()) {
            return redirect()->route('admin.packages.edit', $package);
        }

        return $this->builder($package, PackageFormState::fromPackage($package));
    }

    /**
     * How complete the form is right now, without saving anything: the
     * checklist, step marks, publishing problems, advice and the review
     * screen. The builder calls this as the admin types, so what it shows is
     * always the server's own verdict — the same one publishing uses.
     */
    public function assess(Request $request, ?Package $package = null): JsonResponse
    {
        if ($package) {
            $this->ensureHajj($package);
        }

        $base = $package ? PackageFormState::fromPackage($package) : PackageFormState::blank();
        $input = $request->except(['_token', '_method', '_intent', '_step', '_reviewed', '_new_cover', '_new_media']);
        $state = PackageFormState::withOldInput($base, $input);

        $review = new PackageReview($state, $package, [
            'new_cover' => $request->boolean('_new_cover'),
            'new_media' => $request->integer('_new_media'),
            'remove_cover' => $request->boolean('remove_cover_image'),
            'reviewed' => $request->boolean('_reviewed'),
        ]);

        return response()->json([
            'percent' => $review->percent(),
            'checklist' => $review->checklist(),
            'steps' => $review->stepStatus(),
            'problems' => $review->problems(),
            'warnings' => $review->warnings(),
            'can_publish' => $review->canPublish(),
            'review_html' => view('admin.hajj-packages.partials.review-body', [
                'review' => $review,
                'steps' => PackageCompleteness::STEPS,
            ])->render(),
        ]);
    }

    public function update(HajjPackageRequest $request, Package $package): RedirectResponse
    {
        $this->ensureHajj($package);

        $data = $request->validated();
        $wasPublished = $package->isPublished();

        $this->fillBasics($package, $data, $request);
        $package->save();

        $this->writer->syncNested($package, $data);
        $this->syncMedia($package, $request);
        $this->recordProgress($package, $request);

        if ($wasPublished !== $package->isPublished()) {
            AdminActivity::record($package->isPublished() ? 'published' : 'unpublished', $package, ($package->isPublished() ? 'Published' : 'Moved to draft').": {$package->code} — {$package->name}.");
        }

        return $this->afterSave($package, $request, created: false);
    }

    public function destroy(Package $package): RedirectResponse
    {
        $this->ensureHajj($package);

        if ($package->isPublished()) {
            return back()->withErrors(['package' => "{$package->name} is live on the website, so it cannot be deleted. Move it to draft or archive it first."]);
        }

        $package->delete();
        AdminActivity::record('deleted', $package, "Deleted Hajj package {$package->code} — {$package->name}.");

        return redirect()->route('admin.hajj-packages.index')->with('status', "Deleted {$package->name}.");
    }

    /**
     * One-click actions from the listing and the builder's toolbar.
     */
    public function quick(Request $request, Package $package, string $action): RedirectResponse
    {
        $this->ensureHajj($package);
        $name = trim("{$package->code} — {$package->name}", ' —');

        switch ($action) {
            case 'publish':
                $problems = PackageCompleteness::problems(PackageFormState::fromPackage($package));
                if ($problems !== []) {
                    return back()->withErrors(collect($problems)->mapWithKeys(fn ($p, $i) => ["publish.{$p['step']}.{$i}" => $p['message']])->all())
                        ->with('publish_blocked', $package->id);
                }
                $package->forceFill([
                    'status' => 'published',
                    'published_at' => $package->published_at ?? now(),
                    'archived_at' => null,
                ])->save();
                $message = "{$package->name} is now live on the website.";
                break;

            case 'unpublish':
                $package->forceFill(['status' => 'draft'])->save();
                $message = "{$package->name} is now a draft and hidden from the website.";
                break;

            case 'feature':
                $package->forceFill(['is_featured' => true])->save();
                $message = "{$package->name} is now featured.";
                break;

            case 'unfeature':
                $package->forceFill(['is_featured' => false])->save();
                $message = "{$package->name} is no longer featured.";
                break;

            case 'archive':
                $package->forceFill(['archived_at' => now(), 'status' => 'draft', 'is_featured' => false])->save();
                $message = "{$package->name} was archived and removed from the website. You can restore it from the Archived tab.";
                break;

            case 'restore':
                $package->forceFill(['archived_at' => null])->save();
                $message = "{$package->name} was restored as a draft.";
                break;

            default:
                abort(404);
        }

        $done = ['publish' => 'published', 'unpublish' => 'unpublished', 'feature' => 'featured', 'unfeature' => 'unfeatured', 'archive' => 'archived', 'restore' => 'restored'][$action];
        AdminActivity::record($done, $package, ucfirst($done).": {$name}.");

        return back()->with('status', $message);
    }

    public function duplicate(Package $package): RedirectResponse
    {
        $this->ensureHajj($package);

        $copy = $this->duplicator->duplicate($package);

        AdminActivity::record('duplicated', $copy, "Copied {$package->code} — {$package->name} into a new draft ({$copy->code}).");

        return redirect()
            ->route('admin.hajj-packages.edit', $copy)
            ->with('status', 'A copy was created as a draft. Change its title, code and dates, then publish it when ready. The original package was not changed.');
    }

    /**
     * The package exactly as visitors would see it, reachable only by a
     * signed, expiring link AND a logged-in admin — see the route definition.
     */
    public function preview(Package $package): Response
    {
        $this->ensureHajj($package);

        $view = view('packages.show-hajj', array_merge(HajjPackagePage::data($package), [
            'preview' => true,
            'previewEditUrl' => route('admin.hajj-packages.edit', $package),
        ]));

        return response($view)->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * The content sections of a package as JSON, for "copy from another
     * package" in the builder. Identity, photos and internal notes are left
     * out — the same boundary a template has.
     */
    public function content(Package $package): JsonResponse
    {
        $this->ensureHajj($package);

        return response()->json([
            'name' => $package->name,
            'code' => $package->code,
            'content' => PackageFormState::forTemplate(PackageFormState::fromPackage($package)),
        ]);
    }

    public function saveAsTemplate(Request $request, Package $package): RedirectResponse
    {
        $this->ensureHajj($package);

        $validated = $request->validate([
            'template_name' => ['required', 'string', 'max:255'],
            'template_description' => ['nullable', 'string', 'max:2000'],
        ]);

        $template = $this->templates->saveFromPackage($package, $validated['template_name'], $validated['template_description'] ?? null);
        AdminActivity::record('template_saved', $template, "Saved {$package->code} — {$package->name} as the template \"{$template->name}\".");

        return back()->with('status', "Saved as the template \"{$template->name}\". Use it from Package Templates or when adding a new package.");
    }

    public function applyTemplate(Request $request, Package $package): RedirectResponse
    {
        $this->ensureHajj($package);

        $validated = $request->validate(['template_id' => ['required', 'integer', 'exists:package_templates,id']]);
        $template = PackageTemplate::findOrFail($validated['template_id']);

        try {
            $this->templates->applyToDraft($package, $template);
        } catch (DomainException $e) {
            return back()->withErrors(['template_id' => $e->getMessage()]);
        }

        AdminActivity::record('template_applied', $package, "Applied the template \"{$template->name}\" to the draft {$package->code} — {$package->name}.");

        return redirect()->route('admin.hajj-packages.edit', $package)
            ->with('status', "The template \"{$template->name}\" was applied. Check each step, then save.");
    }

    private function builder(Package $package, array $state, ?PackageTemplate $fromTemplate = null): View
    {
        $state = PackageFormState::withOldInput($state, session()->getOldInput() ?? []);
        $review = new PackageReview($state, $package->exists ? $package : null, [
            'reviewed' => $package->exists && PackageReview::isReviewed($package),
        ]);

        // An unfinished draft opens where the admin stopped last time; an
        // explicit ?step always wins.
        $requested = (string) request('step');
        $resumed = ! array_key_exists($requested, PackageCompleteness::STEPS)
            && $package->exists && ! $package->isPublished()
            && array_key_exists((string) $package->builder_step, PackageCompleteness::STEPS)
            && $package->builder_step !== 'basics';
        $initialStep = match (true) {
            array_key_exists($requested, PackageCompleteness::STEPS) => $requested,
            $resumed => $package->builder_step,
            default => 'basics',
        };

        return view('admin.hajj-packages.form', [
            'package' => $package,
            'state' => $state,
            'mode' => 'package',
            'fromTemplate' => $fromTemplate,
            'steps' => PackageCompleteness::STEPS,
            'stepStatus' => $review->stepStatus(),
            'review' => $review,
            'resumed' => $resumed,
            'library' => PackageBuilderData::for($package),
            'previewUrl' => $package->exists ? $this->previewUrl($package) : null,
            'initialStep' => $initialStep,
        ]);
    }

    /**
     * Remembers the step to reopen, and — when the admin saved from the Review
     * step with nothing blocking — fingerprints the content they reviewed. Any
     * later change to that content un-ticks "Final review completed".
     */
    private function recordProgress(Package $package, Request $request): void
    {
        $step = $request->input('_intent') === 'continue' ? $this->nextStep($request->input('_step')) : $request->input('_step');
        $fresh = $package->fresh();
        $attributes = ['builder_step' => array_key_exists((string) $step, PackageCompleteness::STEPS) ? $step : $package->builder_step];

        if ($request->boolean('_reviewed') && PackageReview::forPackage($fresh)->canPublish()) {
            $attributes['reviewed_hash'] = PackageReview::fingerprint($fresh);
        }

        // Not a content change: leave updated_at alone so "last saved" and the
        // browser's unsaved-copy check stay tied to the real save.
        $package->forceFill($attributes);
        $package->timestamps = false;
        $package->saveQuietly();
        $package->timestamps = true;
    }

    private function previewUrl(Package $package): string
    {
        return PackagePreview::url($package);
    }

    private function fillBasics(Package $package, array $data, Request $request): void
    {
        $package->fill(collect($data)->only([
            'code', 'name', 'package_type', 'slug', 'summary', 'description', 'duration_days', 'duration_label',
            'season_year', 'season_label', 'status', 'package_series_id', 'meta_title', 'meta_description', 'internal_notes',
        ])->all());
        $package->description = RichText::clean($data['description'] ?? null, 'standard');

        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null) {
            $package->sort_order = $data['sort_order'];
        }

        foreach (['is_shifting', 'medinah_first', 'is_featured'] as $flag) {
            $package->{$flag} = $request->boolean($flag);
        }

        if ($package->isPublished()) {
            $package->published_at ??= now();
            $package->archived_at = null;
        }

        foreach (['cover_image' => 'packages', 'social_image' => 'packages/social'] as $field => $folder) {
            if ($request->hasFile($field)) {
                if ($package->{$field}) {
                    Storage::disk('public')->delete($package->{$field});
                }
                $package->{$field} = $request->file($field)->store($folder, 'public');
            } elseif ($request->boolean("remove_{$field}") && $package->{$field}) {
                Storage::disk('public')->delete($package->{$field});
                $package->{$field} = null;
            }
        }
    }

    /**
     * A file input can never be pre-filled, so an empty file on an existing
     * row means "keep the current photo", not "delete it". Each row carries
     * its media id; only rows no longer submitted are deleted, with their
     * files (FINAL_CODE_REVIEW_HAJJ_REDESIGN.md C-2).
     */
    private function syncMedia(Package $package, Request $request): void
    {
        $submittedIds = [];

        foreach ($request->input('media', []) as $i => $row) {
            $file = $request->file("media.{$i}.file");
            $existingId = $row['id'] ?? null;
            $existing = $existingId ? $package->media()->find($existingId) : null;

            if (! $file && ! $existing && blank($row['video_url'] ?? null)) {
                continue;
            }

            $attributes = [
                'media_type' => $row['media_type'] ?? 'gallery',
                'video_url' => $row['video_url'] ?? null,
                'alt_text' => $row['alt_text'] ?? null,
                'caption' => $row['caption'] ?? null,
                'sort_order' => $i,
            ];

            if ($file) {
                if ($existing?->image_path) {
                    Storage::disk('public')->delete($existing->image_path);
                }
                $attributes['image_path'] = $file->store('packages/media', 'public');
            } elseif ($existing) {
                $attributes['image_path'] = $existing->image_path;
            }

            if ($existing) {
                $existing->update($attributes);
                $submittedIds[] = $existing->id;
            } else {
                $submittedIds[] = $package->media()->create($attributes)->id;
            }
        }

        $package->media()->whereNotIn('id', $submittedIds)->get()->each(function ($media) {
            if ($media->image_path) {
                Storage::disk('public')->delete($media->image_path);
            }
            $media->delete();
        });
    }

    private function afterSave(Package $package, Request $request, bool $created): RedirectResponse
    {
        $intent = $request->input('_intent');

        if ($intent === 'preview') {
            return redirect()->to($this->previewUrl($package));
        }

        $message = match (true) {
            $intent === 'publish' => "{$package->name} is published and live on the website.",
            $intent === 'draft' => 'Draft saved. It is not visible on the website.',
            $created => $package->isPublished() ? 'Package created and published.' : 'Package created as a draft.',
            default => $package->isPublished() ? 'Changes saved. They are live on the website now.' : 'Changes saved.',
        };

        $step = $intent === 'continue' ? $this->nextStep($request->input('_step')) : $request->input('_step');

        return redirect()
            ->route('admin.hajj-packages.edit', array_filter(['package' => $package, 'step' => $step]))
            ->with('status', $message);
    }

    private function nextStep(?string $current): string
    {
        $keys = array_keys(PackageCompleteness::STEPS);
        $index = array_search($current, $keys, true);

        return $index === false ? $keys[0] : ($keys[$index + 1] ?? $keys[$index]);
    }

    /**
     * Route model binding knows nothing about categories, so every action that
     * takes a package confirms it really is a Hajj package — the generic
     * Umrah/Tourism controller has the mirror-image guard.
     */
    private function ensureHajj(Package $package): void
    {
        abort_unless($package->isHajj(), 404);
    }

    private function hajjCategory(): PackageCategory
    {
        return PackageCategory::where('slug', 'hajj')->firstOrFail();
    }
}
