<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\Affiliation;
use App\Models\Award;
use App\Models\Faq;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Support\Library\LibraryRegistry;
use App\Support\Packages\PackageCompleteness;
use App\Support\Packages\PackageFormState;
use Illuminate\View\View;

/**
 * Every number on the dashboard is a live count from the database — nothing
 * here is estimated, sampled or hard-coded.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $hajjId = PackageCategory::where('slug', 'hajj')->value('id');
        $hajj = fn () => Package::where('package_category_id', $hajjId)->notArchived();

        $stats = [
            'hajj_total' => $hajj()->count(),
            'hajj_published' => $hajj()->where('status', 'published')->count(),
            'hajj_draft' => $hajj()->where('status', 'draft')->count(),
            'hajj_featured' => $hajj()->where('is_featured', true)->count(),
            'hajj_archived' => Package::where('package_category_id', $hajjId)->archived()->count(),
            'other_packages' => Package::where('package_category_id', '!=', $hajjId)->published()->count(),
            'inquiries_total' => Inquiry::count(),
            'inquiries_new' => Inquiry::where('status', 'new')->count(),
            'faqs' => Faq::count(),
            'awards' => Award::count(),
            'affiliations' => Affiliation::count(),
        ];

        $libraryCounts = collect(LibraryRegistry::all())
            ->map(fn ($type) => ['type' => $type, 'count' => $type->query()->where('is_active', true)->count()])
            ->values();

        // A published package that no longer meets the publishing rules — for
        // example, its only priced room was marked unavailable — is exactly the
        // kind of thing an admin needs pointing at.
        $incompletePublished = $hajj()->where('status', 'published')
            ->with(PackageFormState::RELATIONS)
            ->get()
            ->map(fn (Package $p) => ['package' => $p, 'problems' => PackageCompleteness::problems(PackageFormState::fromPackage($p))])
            ->filter(fn ($row) => $row['problems'] !== [])
            ->values();

        return view('admin.dashboard', [
            'stats' => $stats,
            'libraryCounts' => $libraryCounts,
            'incompletePublished' => $incompletePublished,
            'recentInquiries' => Inquiry::with('package')->latest()->limit(6)->get(),
            'recentlyUpdatedPackages' => Package::with('category')->latest('updated_at')->limit(6)->get(),
            'recentActivity' => AdminActivity::with('user:id,name')->latest()->limit(8)->get(),
        ]);
    }
}
