<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    public function index(): View
    {
        $settings = SiteSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        // Every key on this form comes from an existing, admin-seeded
        // SiteSetting row (see admin/settings/index.blade.php) — a genuine
        // submission never introduces a new key. Restricting to that
        // allow-list (rather than accepting/creating any client-supplied
        // key with no validation at all) closes an unvalidated write path a
        // release-gate security audit flagged.
        $knownKeys = SiteSetting::pluck('key');

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $secretKeys = SiteSetting::where('key', 'like', '%secret%')->pluck('key');

        foreach ($validated['settings'] as $key => $value) {
            if (! $knownKeys->contains($key)) {
                continue;
            }

            if (blank($value) && $secretKeys->contains($key)) {
                continue;
            }

            // Update the Eloquent model instance (fires the `saved` event,
            // which invalidates the cache — see SiteSetting::booted()), not
            // a Builder::update() mass update, which bypasses that event
            // entirely — the exact bypass that shipped this project's own
            // cache-invalidation bug the first time (FINAL_CODE_REVIEW.md
            // H-1). Only `value` is touched, so an existing row's `group`
            // ('legal'/'social'/'integrations') is never disturbed.
            SiteSetting::where('key', $key)->first()?->update(['value' => $value]);
        }

        return back()->with('status', 'Settings updated.');
    }
}
