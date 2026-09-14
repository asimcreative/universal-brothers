<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PackageTemplateRequest;
use App\Models\AdminActivity;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageTemplate;
use App\Support\Library\PackageBuilderData;
use App\Support\Packages\PackageCompleteness;
use App\Support\Packages\PackageFormState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Whole-package templates. A template is edited in the same step-by-step
 * builder as a package, without the identity steps (code, web address,
 * status, photos, internal notes) — see PackageFormState::forTemplate.
 */
class PackageTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status', 'active');

        return view('admin.package-templates.index', [
            'templates' => PackageTemplate::query()
                ->with('sourcePackage:id,code,name')
                ->when($status === 'active', fn ($q) => $q->where('is_active', true))
                ->when($status === 'archived', fn ($q) => $q->where('is_active', false))
                ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q')->trim().'%'))
                ->ordered()
                ->paginate(25)
                ->withQueryString(),
            'status' => $status,
            'counts' => [
                'active' => PackageTemplate::where('is_active', true)->count(),
                'archived' => PackageTemplate::where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return $this->builder(new PackageTemplate(['is_active' => true]), PackageFormState::blank());
    }

    public function store(PackageTemplateRequest $request): RedirectResponse
    {
        $template = PackageTemplate::create([
            'name' => $request->validated('template_name'),
            'description' => $request->validated('template_description'),
            'payload' => PackageFormState::forTemplate($request->validated()),
            'is_active' => true,
            'sort_order' => (int) PackageTemplate::max('sort_order') + 1,
        ]);

        AdminActivity::record('template_created', $template, "Created the package template \"{$template->name}\".");

        return redirect()->route('admin.package-templates.edit', $template)->with('status', 'Template saved.');
    }

    public function edit(PackageTemplate $template): View
    {
        return $this->builder($template, PackageFormState::fromTemplate($template));
    }

    public function update(PackageTemplateRequest $request, PackageTemplate $template): RedirectResponse
    {
        $template->update([
            'name' => $request->validated('template_name'),
            'description' => $request->validated('template_description'),
            'payload' => PackageFormState::forTemplate($request->validated()),
        ]);

        return redirect()->route('admin.package-templates.edit', array_filter(['template' => $template, 'step' => $request->input('_step')]))
            ->with('status', 'Template saved. Packages already created from it are not changed.');
    }

    public function archive(PackageTemplate $template): RedirectResponse
    {
        $template->update(['is_active' => false]);

        return back()->with('status', "\"{$template->name}\" was archived.");
    }

    public function restore(PackageTemplate $template): RedirectResponse
    {
        $template->update(['is_active' => true]);

        return back()->with('status', "\"{$template->name}\" was restored.");
    }

    /**
     * Templates are copied, never linked, so deleting one cannot change any
     * package.
     */
    public function destroy(PackageTemplate $template): RedirectResponse
    {
        $template->delete();
        AdminActivity::record('template_deleted', null, "Deleted the package template \"{$template->name}\".");

        return redirect()->route('admin.package-templates.index')->with('status', "Deleted the template \"{$template->name}\".");
    }

    private function builder(PackageTemplate $template, array $state): View
    {
        $state = PackageFormState::withOldInput($state, session()->getOldInput() ?? []);
        $placeholder = new Package(['package_category_id' => PackageCategory::where('slug', 'hajj')->value('id')]);

        return view('admin.hajj-packages.form', [
            'package' => $placeholder,
            'template' => $template,
            'state' => $state,
            'mode' => 'template',
            'fromTemplate' => null,
            'steps' => array_diff_key(PackageCompleteness::STEPS, ['media' => true]),
            'stepStatus' => PackageCompleteness::stepStatus($state),
            'library' => PackageBuilderData::for($placeholder),
            'previewUrl' => null,
            'initialStep' => array_key_exists((string) request('step'), PackageCompleteness::STEPS) ? request('step') : 'basics',
        ]);
    }
}
