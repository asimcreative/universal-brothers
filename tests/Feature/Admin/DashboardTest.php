<?php

namespace Tests\Feature\Admin;

use App\Models\AdminActivity;
use App\Models\Affiliation;
use App\Models\Award;
use App\Models\Faq;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * The dashboard shows live counts, never estimates (issue #10).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'name' => 'Office Admin']);
    }

    public function test_the_numbers_are_real_database_counts(): void
    {
        $hajj = PackageCategory::factory()->create(['slug' => 'hajj', 'name' => 'Hajj']);
        $tourism = PackageCategory::factory()->create(['slug' => 'tourism', 'name' => 'Tourism']);

        Package::factory()->count(3)->create(['package_category_id' => $hajj->id, 'status' => 'published', 'is_featured' => true]);
        Package::factory()->count(2)->draft()->create(['package_category_id' => $hajj->id]);
        Package::factory()->draft()->create(['package_category_id' => $hajj->id, 'archived_at' => now()]);
        Package::factory()->count(4)->create(['package_category_id' => $tourism->id]);

        foreach (range(1, 5) as $i) {
            Inquiry::create(['name' => "Visitor {$i}", 'email' => "v{$i}@example.com", 'phone' => '0300', 'message' => 'Hi', 'status' => $i <= 2 ? 'new' : 'contacted']);
        }
        foreach (range(1, 7) as $i) {
            Faq::create(['category' => 'hajj', 'question' => "Question {$i}?", 'answer' => 'Answer', 'is_active' => true]);
        }

        $stats = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->viewData('stats');

        $this->assertSame([
            'hajj_total' => 5, 'hajj_published' => 3, 'hajj_draft' => 2, 'hajj_featured' => 3, 'hajj_archived' => 1,
            'other_packages' => 4, 'inquiries_total' => 5, 'inquiries_new' => 2, 'faqs' => 7,
            'awards' => Award::count(), 'affiliations' => Affiliation::count(),
        ], $stats);
    }

    public function test_the_dashboard_points_at_live_packages_that_are_no_longer_complete(): void
    {
        Artisan::call('db:seed');
        $package = Package::where('code', 'UB003')->firstOrFail();
        $package->roomOptions()->update(['is_available' => false]);

        $page = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

        $page->assertSee('Needs attention');
        $page->assertSee('UB003');
        $page->assertSee('Add at least one available room type with a price.');
        $page->assertSee(route('admin.hajj-packages.edit', ['package' => $package, 'step' => 'pricing']), false);
    }

    public function test_recent_activity_names_who_did_what(): void
    {
        $this->actingAs($this->admin);
        AdminActivity::record('published', null, 'Published: UB001 — Executive Platinum.');

        $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Published: UB001 — Executive Platinum.')
            ->assertSee('Office Admin');
    }
}
