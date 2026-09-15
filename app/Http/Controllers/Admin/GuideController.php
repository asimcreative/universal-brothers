<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Guide\GuideContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The admin guide: a help centre of plain-language sections, each of which an
 * admin can mark as read. Completion is per admin, so each member of staff
 * sees their own progress.
 */
class GuideController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        return view('admin.guide.index', [
            'groups' => GuideContent::groups(),
            'grouped' => GuideContent::grouped($search),
            'search' => $search,
            'total' => count(GuideContent::sections()),
            'completed' => $this->completedKeys($request),
            'tourStatus' => $request->user()->tour_status,
        ]);
    }

    public function show(Request $request, string $section): View
    {
        $content = GuideContent::section($section) ?? abort(404);
        [$previous, $next] = GuideContent::neighbours($section);

        return view('admin.guide.show', [
            'section' => $content,
            'groupTitle' => GuideContent::groups()[$content['group']] ?? '',
            'groupSections' => GuideContent::grouped()[$content['group']] ?? [],
            'links' => GuideContent::links($content),
            'previous' => $previous,
            'next' => $next,
            'completed' => $this->completedKeys($request),
        ]);
    }

    public function complete(Request $request, string $section): RedirectResponse
    {
        abort_unless(GuideContent::section($section), 404);

        $request->user()->guideCompletions()->firstOrCreate(['section_key' => $section], ['completed_at' => now()]);

        return back()->with('status', 'Marked as read. It stays ticked in the guide.');
    }

    public function uncomplete(Request $request, string $section): RedirectResponse
    {
        abort_unless(GuideContent::section($section), 404);

        $request->user()->guideCompletions()->where('section_key', $section)->delete();

        return back()->with('status', 'Marked as not read.');
    }

    /** @return list<string> */
    private function completedKeys(Request $request): array
    {
        return $request->user()->guideCompletions()
            ->whereIn('section_key', array_keys(GuideContent::sections()))
            ->pluck('section_key')
            ->all();
    }
}
