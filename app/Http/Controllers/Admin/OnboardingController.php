<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Guide\GuideContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * First-visit welcome and the guided tour. State is kept on the admin's own
 * account (not in the browser), so a tour paused on the office computer can be
 * resumed on a phone, and a finished tour never starts again by itself.
 */
class OnboardingController extends Controller
{
    public const TOUR_STATUSES = ['not_started', 'in_progress', 'paused', 'completed'];

    public function tour(Request $request): JsonResponse
    {
        $last = count(GuideContent::tour()) - 1;

        $data = $request->validate([
            'status' => ['required', Rule::in(self::TOUR_STATUSES)],
            'step' => ['required', 'integer', 'min:0', "max:{$last}"],
        ]);

        $request->user()->forceFill([
            'tour_status' => $data['status'],
            'tour_step' => $data['status'] === 'completed' ? 0 : $data['step'],
        ])->save();

        return response()->json(['status' => $data['status'], 'step' => (int) $data['step']]);
    }

    /** "Skip for now" on the welcome panel. The tour stays available from the guide. */
    public function dismiss(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->forceFill(['onboarding_dismissed_at' => now()])->save();

        return $request->expectsJson()
            ? response()->json(['dismissed' => true])
            : back()->with('status', 'The welcome panel is hidden. You can take the tour any time from the Guide.');
    }

    /** "Take the tour again", from the guide or the account menu. */
    public function restart(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'tour_status' => 'not_started',
            'tour_step' => 0,
            'onboarding_dismissed_at' => null,
        ])->save();

        return redirect()->route('admin.dashboard', ['tour' => 'start']);
    }
}
