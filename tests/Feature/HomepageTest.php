<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads_successfully(): void
    {
        Office::factory()->create();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Universal Brothers');
    }

    public function test_homepage_shows_featured_packages_per_category(): void
    {
        Office::factory()->create();
        $hajj = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj']);
        Package::factory()->create([
            'package_category_id' => $hajj->id,
            'name' => 'Executive Platinum Test Package',
            'is_featured' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Featured Hajj Packages');
        $response->assertSee('Executive Platinum Test Package');
    }

    public function test_homepage_shows_active_testimonials(): void
    {
        Office::factory()->create();
        Testimonial::create([
            'name' => 'Jane Traveler',
            'quote' => 'Fantastic service throughout our Hajj journey.',
            'service_tag' => 'hajj',
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Jane Traveler');
    }

    public function test_homepage_hides_inactive_testimonials(): void
    {
        Office::factory()->create();
        Testimonial::create([
            'name' => 'Hidden Reviewer',
            'quote' => 'This should not appear.',
            'service_tag' => 'hajj',
            'is_active' => false,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Hidden Reviewer');
    }
}
