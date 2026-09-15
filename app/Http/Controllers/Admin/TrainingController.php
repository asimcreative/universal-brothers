<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTrainingProgress;
use App\Support\Training\TrainingCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Admin Guide → Video Training: the chapter index, each chapter's player and
 * written companion guide, the printable package checklist, and each admin's
 * own progress (watched position, completion). Videos are served only through
 * this controller, to signed-in admins — the files live outside the web root.
 *
 * Progress never gates anything: an admin can use the whole admin panel
 * without watching a single video.
 */
class TrainingController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $progress = $this->progress($request);

        return view('admin.guide.videos.index', [
            'categories' => TrainingCatalog::categories(),
            'grouped' => TrainingCatalog::grouped($search),
            'search' => $search,
            'progress' => $progress,
            'summary' => $this->summary($progress),
            'lastWatched' => $this->lastWatched($progress),
        ]);
    }

    public function show(Request $request, string $chapter): View
    {
        $content = TrainingCatalog::chapter($chapter) ?? abort(404);
        [$previous, $next] = TrainingCatalog::neighbours($chapter);
        $progress = $this->progress($request);

        return view('admin.guide.videos.show', [
            'chapter' => $content,
            'category' => TrainingCatalog::categories()[$content['category']],
            'chapters' => TrainingCatalog::chapters(),
            'previous' => $previous,
            'next' => $next,
            'related' => TrainingCatalog::related($content),
            'moments' => TrainingCatalog::moments($content),
            'record' => $progress->get($chapter),
            'progress' => $progress,
            'summary' => $this->summary($progress),
            // ?start=0 (Restart Training / Replay) ignores the saved position.
            'resumeAt' => $request->has('start') ? max(0, $request->integer('start')) : (int) ($progress->get($chapter)?->position_seconds ?? 0),
        ]);
    }

    /** Continue Training: the chapter last watched and not finished, else the first unfinished one. */
    public function continue(Request $request): RedirectResponse
    {
        $progress = $this->progress($request);
        $last = $this->lastWatched($progress);

        $key = ($last && ! $last->isCompleted())
            ? $last->chapter_key
            : (collect(TrainingCatalog::chapters())->keys()->first(fn ($key) => ! $progress->get($key)?->isCompleted())
                ?? array_key_first(TrainingCatalog::chapters()));

        return redirect()->route('admin.training.show', $key);
    }

    /** Restart Training: chapter 1 from the beginning. Completed chapters stay ticked. */
    public function restart(): RedirectResponse
    {
        return redirect()->route('admin.training.show', ['chapter' => array_key_first(TrainingCatalog::chapters()), 'start' => 0]);
    }

    /** Reset Progress: forget every position and tick for this admin only. */
    public function reset(Request $request): RedirectResponse
    {
        $request->user()->trainingProgress()->delete();

        return redirect()->route('admin.training.index')->with('status', 'Your training progress was reset. Watched positions and ticks are cleared.');
    }

    /** Saves the playback position while a video plays. A chapter watched to 90% counts as completed. */
    public function saveProgress(Request $request, string $chapter): JsonResponse
    {
        $content = TrainingCatalog::chapter($chapter) ?? abort(404);

        $data = $request->validate([
            'position' => ['required', 'numeric', 'min:0', 'max:36000'],
            'duration' => ['nullable', 'numeric', 'min:1', 'max:36000'],
        ]);

        $duration = (int) round($data['duration'] ?? $content['video']['duration'] ?? 0) ?: null;
        $position = (int) floor($data['position']);
        if ($duration) {
            $position = min($position, $duration);
        }

        $record = AdminTrainingProgress::firstOrNew(['user_id' => $request->user()->id, 'chapter_key' => $chapter]);
        $record->position_seconds = $position;
        $record->furthest_seconds = max((int) $record->furthest_seconds, $position);
        $record->duration_seconds = $duration ?? $record->duration_seconds;
        $record->last_watched_at = now();
        if (! $record->completed_at && $record->duration_seconds && $record->furthest_seconds >= $record->duration_seconds * config('training.complete_ratio')) {
            $record->completed_at = now();
        }
        $record->save();

        return response()->json([
            'position' => $record->position_seconds,
            'completed' => $record->isCompleted(),
            'summary' => $this->summary($this->progress($request)),
        ]);
    }

    public function complete(Request $request, string $chapter): RedirectResponse|JsonResponse
    {
        abort_unless(TrainingCatalog::chapter($chapter), 404);

        $record = AdminTrainingProgress::firstOrNew(['user_id' => $request->user()->id, 'chapter_key' => $chapter]);
        $record->completed_at ??= now();
        $record->last_watched_at ??= now();
        $record->save();

        return $request->expectsJson()
            ? response()->json(['completed' => true])
            : back()->with('status', 'Chapter marked as completed.');
    }

    public function uncomplete(Request $request, string $chapter): RedirectResponse|JsonResponse
    {
        abort_unless(TrainingCatalog::chapter($chapter), 404);

        $request->user()->trainingProgress()->where('chapter_key', $chapter)->update(['completed_at' => null]);

        return $request->expectsJson()
            ? response()->json(['completed' => false])
            : back()->with('status', 'Chapter marked as not completed.');
    }

    public function checklist(): View
    {
        return view('admin.guide.videos.checklist', ['checklist' => TrainingCatalog::checklist()]);
    }

    public function downloadChecklist(): Response
    {
        $lines = ['HAJJ PACKAGE CREATION CHECKLIST — Universal Brothers', 'Package code: ____________   Checked by: ____________   Date: ____________', ''];
        foreach (TrainingCatalog::checklist() as $group => $items) {
            $lines[] = strtoupper($group);
            foreach ($items as $item) {
                $lines[] = '[ ] '.$item;
            }
            $lines[] = '';
        }

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="hajj-package-checklist.txt"',
        ]);
    }

    // ------------------------------------------------------------------
    // Files (videos, captions, thumbnails) — admins only
    // ------------------------------------------------------------------

    public function video(string $chapter): BinaryFileResponse
    {
        return $this->file($chapter, 'file', 'video/webm');
    }

    public function captions(string $chapter): BinaryFileResponse
    {
        return $this->file($chapter, 'captions', 'text/vtt; charset=UTF-8');
    }

    public function poster(string $chapter): BinaryFileResponse
    {
        return $this->file($chapter, 'poster', 'image/jpeg');
    }

    private function file(string $chapter, string $kind, string $type): BinaryFileResponse
    {
        $video = TrainingCatalog::chapter($chapter)['video'] ?? abort(404);
        $name = $video[$kind] ?? abort(404);

        // BinaryFileResponse answers Range requests, so the player can seek.
        return response()->file(TrainingCatalog::path($name), [
            'Content-Type' => $type,
            'Cache-Control' => 'private, max-age=3600',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    // ------------------------------------------------------------------

    /** @return Collection<string, AdminTrainingProgress> keyed by chapter */
    private function progress(Request $request): Collection
    {
        return $request->user()->trainingProgress()
            ->whereIn('chapter_key', array_keys(TrainingCatalog::chapters()))
            ->get()
            ->keyBy('chapter_key');
    }

    private function summary(Collection $progress): array
    {
        $total = TrainingCatalog::count();
        $completed = $progress->filter->isCompleted()->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'watched' => $progress->filter(fn ($p) => $p->furthest_seconds > 0 || $p->isCompleted())->count(),
            'percent' => $total ? (int) round($completed / $total * 100) : 0,
        ];
    }

    private function lastWatched(Collection $progress): ?AdminTrainingProgress
    {
        return $progress->filter(fn ($p) => $p->last_watched_at)->sortByDesc('last_watched_at')->first();
    }
}
