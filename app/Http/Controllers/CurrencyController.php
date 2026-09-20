<?php

namespace App\Http\Controllers;

use App\Support\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Which price list the visitor is reading.
 *
 * POST, never GET: this changes what the session holds and therefore what
 * every page shows, and a link that quietly rewrote someone's session would
 * be a state change through a safe method.
 */
class CurrencyController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', Rule::in(Currency::SUPPORTED)],
            // Where to go back to. Validated as a path on this site rather
            // than trusted, so the form cannot be used to bounce someone off
            // to another domain.
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        Currency::set($validated['currency']);

        $back = $validated['redirect_to'] ?? null;

        if (is_string($back) && str_starts_with($back, '/') && ! str_starts_with($back, '//')) {
            return redirect()->to($back);
        }

        return back();
    }
}
