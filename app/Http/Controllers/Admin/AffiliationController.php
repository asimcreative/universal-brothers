<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AffiliationController extends Controller
{
    public function index(): View
    {
        return view('admin.affiliations.index', ['affiliations' => Affiliation::orderBy('sort_order')->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.affiliations.form', ['affiliation' => new Affiliation]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('affiliations', 'public');
        }

        Affiliation::create($data);

        return redirect()->route('admin.affiliations.index')->with('status', 'Affiliation created.');
    }

    public function edit(Affiliation $affiliation): View
    {
        return view('admin.affiliations.form', compact('affiliation'));
    }

    public function update(Request $request, Affiliation $affiliation): RedirectResponse
    {
        $data = $this->validated($request, $affiliation);

        if ($request->hasFile('logo')) {
            if ($affiliation->logo) {
                Storage::disk('public')->delete($affiliation->logo);
            }
            $data['logo'] = $request->file('logo')->store('affiliations', 'public');
        }

        $affiliation->update($data);

        return redirect()->route('admin.affiliations.index')->with('status', 'Affiliation updated.');
    }

    public function destroy(Affiliation $affiliation): RedirectResponse
    {
        if ($affiliation->logo) {
            Storage::disk('public')->delete($affiliation->logo);
        }
        $affiliation->delete();

        return redirect()->route('admin.affiliations.index')->with('status', 'Affiliation deleted.');
    }

    private function validated(Request $request, ?Affiliation $affiliation): array
    {
        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:255', Rule::unique('affiliations', 'organization_name')->ignore($affiliation?->id)],
            'logo' => ['nullable', 'image', 'max:4096'],
            'description' => ['nullable', 'string'],
            'year' => ['nullable', 'string', 'max:9'],
            'link' => ['nullable', 'url', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        unset($data['logo']);

        return $data;
    }
}
