<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\PageRevision;
use App\Support\PageBuilder\BlockRegistry;
use App\Support\PageBuilder\PageDocument;
use App\Support\PageBuilder\PageStarters;
use App\Support\PageBuilder\SectionValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CMS pages, built from sections.
 *
 * The page's columns are what visitors see. Editing saves a draft
 * (`pages.draft`); publishing checks the draft thoroughly and copies it into
 * the columns, keeping the previous version as a revision. Nothing an admin
 * types is thrown away: a draft saves even when it is incomplete.
 */
class PageController extends Controller
{
    /** Addresses the website already uses for its own pages. */
    public const RESERVED_SLUGS = [
        'admin', 'ai', 'api', 'hajj', 'umrah', 'tourism', 'contact', 'news', 'inquiries', 'sitemap', 'sitemap-xml',
        'hajj-services', 'umrah-services', 'awards', 'affiliations', 'media', 'testimonials', 'faqs', 'storage',
        'build', 'images', 'login', 'logout', 'register', 'up', 'deploy',
    ];

    public const PREVIEW_MINUTES = 120;

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['all', ...array_keys(Page::STATUSES)], true) ? $request->query('status') : 'all';
        $search = trim((string) $request->query('q'));

        $pages = Page::query()
            ->with('editor:id,name')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%")))
            ->orderByRaw("case status when 'draft' then 0 when 'scheduled' then 1 when 'published' then 2 else 3 end")
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        $counts = Page::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.pages.index', [
            'pages' => $pages,
            'status' => $status,
            'search' => $search,
            'counts' => $counts->put('all', $counts->sum()),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.create', ['starters' => PageStarters::all()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'slug' => $this->slugRules(null),
            'starter' => ['nullable', Rule::in(array_keys(PageStarters::all()))],
        ], $this->messages())->validate();

        $document = PageDocument::make([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'sections' => PageStarters::sections($validated['starter'] ?? 'blank', $validated['title']),
        ]);

        $page = Page::create([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'template' => 'default',
            'status' => 'draft',
            'is_active' => false,
            'draft' => $document->toArray(),
            'updated_by' => $request->user()->id,
        ]);

        AdminActivity::record('page_created', $page, "Created the page \"{$page->title}\" as a draft.");

        return redirect()->route('admin.pages.edit', $page)
            ->with('status', 'Page created as a draft. Add and edit sections, preview it, then publish when it is ready.');
    }

    public function edit(Request $request, Page $page): View
    {
        $document = PageDocument::forEditing($page);
        $sections = $document->sections();

        // After a failed save, rebuild the page from what was submitted so
        // nothing typed is lost.
        if ($request->session()->hasOldInput()) {
            $document = $document->with([
                'title' => old('title'),
                'slug' => old('slug'),
                'featured_image' => old('featured_image.path'),
                'meta_title' => old('meta_title'),
                'meta_description' => old('meta_description'),
                'focus_keyword' => old('focus_keyword'),
                'canonical_url' => old('canonical_url'),
                'og_title' => old('og_title'),
                'og_description' => old('og_description'),
                'og_image' => old('og_image.path'),
                'noindex' => (bool) old('noindex'),
            ]);
            $sections = (new SectionValidator(false))->validate((array) old('sections', []))['sections'];
        }

        return view('admin.pages.builder', [
            'page' => $page,
            'document' => $document,
            'sections' => $sections,
            'groups' => BlockRegistry::grouped(),
            'savedBlocks' => ContentBlock::active()->orderBy('category')->orderBy('name')->get(),
            'revisions' => $page->revisions()->with('author:id,name')->limit(15)->get(),
            'previewUrl' => URL::temporarySignedRoute('admin.pages.preview', now()->addMinutes(self::PREVIEW_MINUTES), ['page' => $page]),
            'isLegacy' => ! $page->usesSections() && ! is_array($page->draft),
            'canLink' => $request->user()->can('link-saved-sections'),
            'sectionLinks' => $this->errorLinks($sections),
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $intent = (string) $request->input('intent', 'save');

        $validated = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'slug' => $this->slugRules($page),
            'featured_image.path' => ['nullable', 'string', 'max:255', $this->knownImageRule('page banner image')],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'focus_keyword' => ['nullable', 'string', 'max:100'],
            'canonical_url' => ['nullable', 'string', 'max:255', 'url:https,http'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:300'],
            'og_image.path' => ['nullable', 'string', 'max:255', $this->knownImageRule('sharing image')],
            'noindex' => ['nullable', 'boolean'],
            'sections' => ['nullable', 'array'],
            'publish_at' => [Rule::requiredIf($intent === 'schedule'), 'nullable', 'date', 'after:now'],
            'block_name' => [Rule::requiredIf(str_starts_with($intent, 'save_block:')), 'nullable', 'string', 'max:120'],
            'block_description' => ['nullable', 'string', 'max:500'],
            'block_category' => ['nullable', Rule::in(array_keys(ContentBlock::CATEGORIES))],
        ], $this->messages())->validate();

        $lenient = (new SectionValidator(false))->validate((array) $request->input('sections', []));

        $document = PageDocument::make([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'featured_image' => $validated['featured_image']['path'] ?? null,
            'sections' => $lenient['sections'],
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'focus_keyword' => $validated['focus_keyword'] ?? null,
            'canonical_url' => $validated['canonical_url'] ?? null,
            'og_title' => $validated['og_title'] ?? null,
            'og_description' => $validated['og_description'] ?? null,
            'og_image' => $validated['og_image']['path'] ?? null,
            'noindex' => $request->boolean('noindex'),
        ]);

        $this->saveDraft($page, $document, $request);

        $edit = redirect()->route('admin.pages.edit', $page);

        if (str_starts_with($intent, 'save_block:')) {
            return $this->saveSectionAsBlock($request, $page, $lenient['sections'], Str::after($intent, 'save_block:'), $validated);
        }

        if ($intent === 'preview') {
            return $edit->with('status', 'Draft saved. The preview shows it exactly as visitors would see it.')
                ->with('open_preview', true)
                ->with('builder_warnings', $this->warningsForView($lenient['warnings']));
        }

        if (in_array($intent, ['publish', 'schedule'], true)) {
            return $this->publishDraft($request, $page, $document, $intent === 'schedule' ? Carbon::parse($validated['publish_at']) : null);
        }

        return $edit->with('status', 'Draft saved. Visitors do not see these changes until you publish.')
            ->with('builder_warnings', $this->warningsForView($lenient['warnings']));
    }

    /** Publish the saved draft from the pages list, after the same checks as the builder. */
    public function publish(Request $request, Page $page): RedirectResponse
    {
        return $this->publishDraft($request, $page, PageDocument::forEditing($page), null);
    }

    public function unpublish(Request $request, Page $page): RedirectResponse
    {
        if (! $page->is_active) {
            return back()->with('status', 'This page is not published.');
        }

        $this->recordRevision($page, 'unpublished', PageDocument::fromPublished($page)->toArray(), $request);

        $page->forceFill(['status' => 'draft', 'is_active' => false, 'updated_by' => $request->user()->id])->save();
        AdminActivity::record('page_unpublished', $page, "Unpublished the page \"{$page->title}\".");

        return back()->with('status', "\"{$page->title}\" is no longer on the website. Its content is kept as a draft.");
    }

    public function archive(Request $request, Page $page): RedirectResponse
    {
        $page->forceFill(['status' => 'archived', 'is_active' => false, 'updated_by' => $request->user()->id])->save();
        AdminActivity::record('page_archived', $page, "Archived the page \"{$page->title}\".");

        return redirect()->route('admin.pages.index')->with('status', "\"{$page->title}\" was archived and removed from the website. Restore it from the Archived tab at any time.");
    }

    public function restore(Request $request, Page $page): RedirectResponse
    {
        $page->forceFill(['status' => 'draft', 'is_active' => false, 'updated_by' => $request->user()->id])->save();

        return redirect()->route('admin.pages.edit', $page)->with('status', "\"{$page->title}\" was restored as a draft. Publish it when you want it back on the website.");
    }

    public function discardDraft(Request $request, Page $page): RedirectResponse
    {
        if (! $page->usesSections() && ! $page->is_active) {
            return back()->with('status', 'This page has never been published, so there is no published version to go back to.');
        }

        $page->forceFill(['draft' => null, 'updated_by' => $request->user()->id])->save();

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Unpublished changes were discarded. You are now editing the published version.');
    }

    public function duplicate(Request $request, Page $page): RedirectResponse
    {
        $source = PageDocument::forEditing($page);
        $slug = $this->uniqueSlug($page->slug.'-copy');

        $sections = array_map(fn (array $s) => array_replace($s, ['id' => SectionValidator::newId()]), $source->sections());
        $document = $source->with(['title' => 'Copy of '.$page->title, 'slug' => $slug, 'sections' => $sections]);

        $copy = Page::create([
            'title' => $document->get('title'),
            'slug' => $slug,
            'template' => $page->template,
            'status' => 'draft',
            'is_active' => false,
            'featured_image' => $page->featured_image,
            'draft' => $document->toArray(),
            'updated_by' => $request->user()->id,
        ]);

        AdminActivity::record('page_duplicated', $copy, "Duplicated \"{$page->title}\" as \"{$copy->title}\".");

        return redirect()->route('admin.pages.edit', $copy)->with('status', 'A copy was created as a draft. Change its title and address before publishing.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $title = $page->title;
        $page->delete();
        AdminActivity::record('page_deleted', null, "Deleted the page \"{$title}\".");

        return redirect()->route('admin.pages.index')->with('status', "\"{$title}\" was deleted.");
    }

    public function restoreRevision(Request $request, Page $page, PageRevision $revision): RedirectResponse
    {
        abort_unless($revision->page_id === $page->id, 404);

        $document = PageDocument::make($revision->data)->with(['slug' => $page->slug]);
        $this->saveDraft($page, $document, $request);
        $this->recordRevision($page, 'restored', $document->toArray(), $request);

        return redirect()->route('admin.pages.edit', $page)->with('status', 'The version from '.$revision->created_at->timezone(config('app.display_timezone'))->format('j M Y, g:i a').' is now your draft. Preview it, then publish to put it back on the website.');
    }

    /** An administrator's signed preview of the draft. Never indexed, never visible to visitors. */
    public function preview(Page $page): Response
    {
        $document = PageDocument::forEditing($page);
        $previewPage = $page->replicate();
        $previewPage->id = $page->id;
        $document->applyTo($previewPage);

        $view = app(\App\Http\Controllers\PageController::class)->render($previewPage, [
            'preview' => true,
            'previewEditUrl' => route('admin.pages.edit', $page),
        ]);

        return response($view)->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /** One section's editing card, for "Add section" and "Insert saved section" in the builder. */
    public function sectionForm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(BlockRegistry::libraryTypes()))],
            'block' => ['nullable', 'integer', 'exists:content_blocks,id'],
            'mode' => ['nullable', Rule::in(['copy', 'linked'])],
        ]);

        if (filled($validated['block'] ?? null)) {
            $block = ContentBlock::active()->findOrFail($validated['block']);
            $linked = ($validated['mode'] ?? 'copy') === 'linked';

            abort_if($linked && $request->user()->cannot('link-saved-sections'), 403, 'Only a super admin can insert a linked saved section.');

            $section = $linked
                ? ['id' => SectionValidator::newId(), 'type' => 'saved_block', 'visible' => true, 'data' => ['block_id' => $block->id]]
                : ['id' => SectionValidator::newId(), 'type' => $block->type, 'visible' => true, 'data' => array_replace(BlockRegistry::defaults($block->type), (array) $block->data)];
        } elseif (filled($validated['type'] ?? null)) {
            $section = ['id' => SectionValidator::newId(), 'type' => $validated['type'], 'visible' => true, 'data' => BlockRegistry::defaults($validated['type'])];
        } else {
            abort(422, 'Choose a kind of section.');
        }

        return response()->json([
            'id' => $section['id'],
            'html' => view('admin.pages.partials.section', [
                'section' => $section,
                'position' => 0,
                'open' => true,
                'savedBlocks' => ContentBlock::active()->get()->keyBy('id'),
            ])->render(),
        ]);
    }

    private function saveDraft(Page $page, PageDocument $document, Request $request): void
    {
        $changes = ['draft' => $document->toArray(), 'updated_by' => $request->user()->id];

        // A page nobody can see yet takes its new title and address at once,
        // so the pages list shows what the admin is working on. A live page
        // keeps its public title and address until the draft is published.
        if (! $page->isLive()) {
            $changes['title'] = $document->get('title');
            $changes['slug'] = $document->get('slug');
        }

        $page->forceFill($changes)->save();
    }

    private function publishDraft(Request $request, Page $page, PageDocument $document, ?Carbon $publishAt): RedirectResponse
    {
        $strict = (new SectionValidator(true))->validate($document->sections());
        $errors = $strict['errors'];

        if ($strict['sections'] !== [] && collect($strict['sections'])->where('visible', true)->isEmpty()) {
            $errors['sections'] = 'Every section on this page is hidden. Show at least one section before publishing.';
        } elseif ($strict['sections'] === []) {
            $errors['sections'] = 'This page has no sections yet. Add at least one section before publishing.';
        }

        $slugTaken = Page::where('slug', $document->get('slug'))->where('id', '!=', $page->id)->exists();
        if ($slugTaken) {
            $errors['slug'] = 'Another page already uses the address /'.$document->get('slug').'. Choose a different page address.';
        }

        if ($errors !== []) {
            return redirect()->route('admin.pages.edit', $page)
                ->withErrors($errors)
                ->with('status', 'Your changes are saved as a draft, but the page was not '.($publishAt ? 'scheduled' : 'published').' yet. Fix the items below, then try again.');
        }

        DB::transaction(function () use ($request, $page, $document, $publishAt, $strict) {
            $document = $document->with(['sections' => $strict['sections']]);
            $wasLive = $page->isLive();
            $document->applyTo($page);

            $page->forceFill([
                'status' => $publishAt ? 'scheduled' : 'published',
                'is_active' => true,
                'published_at' => $publishAt ?? ($wasLive ? $page->published_at : now()),
                'published_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
                'draft' => null,
            ])->save();

            $this->recordRevision($page, $publishAt ? 'scheduled' : 'published', $document->toArray(), $request);
        });

        AdminActivity::record($publishAt ? 'page_scheduled' : 'page_published', $page, ($publishAt ? 'Scheduled' : 'Published')." the page \"{$page->title}\".");

        return redirect()->route('admin.pages.edit', $page)->with('status', $publishAt
            ? "\"{$page->title}\" will appear on the website on ".$publishAt->copy()->timezone(config('app.display_timezone'))->format('j M Y \a\t g:i a').' (Pakistan time).'
            : "\"{$page->title}\" is published and visible on the website.");
    }

    private function saveSectionAsBlock(Request $request, Page $page, array $sections, string $sectionId, array $validated): RedirectResponse
    {
        $section = collect($sections)->firstWhere('id', $sectionId);

        if (! $section || $section['type'] === 'saved_block') {
            return redirect()->route('admin.pages.edit', $page)->with('status', 'Draft saved, but that section could not be saved for reuse. A linked saved section is already reusable.');
        }

        $block = ContentBlock::create([
            'name' => $validated['block_name'],
            'description' => $validated['block_description'] ?? null,
            'category' => $validated['block_category'] ?? 'general',
            'type' => $section['type'],
            'data' => $section['data'],
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        AdminActivity::record('content_block_created', $block, "Saved the section \"{$block->name}\" for reuse.");

        return redirect()->route('admin.pages.edit', $page)->with('status', "Draft saved, and the section was saved as \"{$block->name}\". Insert it into any page from Add section → Saved sections.");
    }

    private function recordRevision(Page $page, string $action, array $data, Request $request): void
    {
        $page->revisions()->create(['action' => $action, 'data' => $data, 'created_by' => $request->user()->id]);

        $keep = $page->revisions()->limit(PageRevision::KEEP)->pluck('id');
        $page->revisions()->whereNotIn('id', $keep)->delete();
    }

    private function slugRules(?Page $page): array
    {
        return [
            'required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::notIn(self::RESERVED_SLUGS),
            Rule::unique('pages', 'slug')->ignore($page?->id),
        ];
    }

    private function knownImageRule(string $label): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($label) {
            if (filled($value) && ! SectionValidator::isKnownImage((string) $value)) {
                $fail("The {$label} could no longer be found. Choose the image again.");
            }
        };
    }

    private function messages(): array
    {
        return [
            'title.required' => 'Give the page a title. It is shown at the top of the page and in the browser tab.',
            'title.max' => 'The page title is too long. Keep it under 255 characters.',
            'slug.required' => 'Give the page an address, for example "our-story". It becomes universal-brothers.iisol.co/our-story.',
            'slug.regex' => 'The page address can only use small letters, numbers and single dashes, for example "hajj-guide-2027". No spaces or other symbols.',
            'slug.max' => 'The page address is too long. Keep it under 100 characters.',
            'slug.not_in' => 'That address is already used by another part of the website. Choose a different page address.',
            'slug.unique' => 'Another page already uses this address. Choose a different page address.',
            'canonical_url.url' => 'The canonical link must be a full web address starting with https://. Leave it empty if you are not sure.',
            'meta_description.max' => 'The search description is too long. Keep it under 255 characters; around 160 shows in full.',
            'publish_at.required' => 'Choose the date and time the page should appear.',
            'publish_at.after' => 'The scheduled time has already passed. Choose a time in the future, or publish now.',
            'block_name.required' => 'Give the saved section a name so you can find it later.',
        ];
    }

    private function uniqueSlug(string $base): string
    {
        $base = Str::limit(Str::slug($base), 90, '');
        $slug = $base;
        $n = 2;

        while (Page::where('slug', $slug)->exists() || in_array($slug, self::RESERVED_SLUGS, true)) {
            $slug = "{$base}-{$n}";
            $n++;
        }

        return $slug;
    }

    /** Validation keys that belong to a section → the element the summary should jump to. */
    private function errorLinks(array $sections): array
    {
        $links = ['title' => 'page-title', 'slug' => 'page-slug', 'sections' => 'builder-sections'];

        foreach (session('errors')?->getBag('default')->keys() ?? [] as $key) {
            if (preg_match('/^sections\.(s_[a-z0-9]+)\./', $key, $m)) {
                $links[$key] = 'section-'.$m[1];
            } elseif (str_starts_with($key, 'meta_') || str_starts_with($key, 'og_') || in_array($key, ['focus_keyword', 'canonical_url'], true)) {
                $links[$key] = 'page-seo';
            }
        }

        return $links;
    }

    private function warningsForView(array $warnings): array
    {
        return collect($warnings)
            ->map(fn (array $w) => ['message' => $w['message'], 'target' => $w['section'] ? 'section-'.$w['section'] : null])
            ->unique('message')
            ->values()
            ->all();
    }
}
