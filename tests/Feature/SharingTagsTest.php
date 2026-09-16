<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The tags WhatsApp, Facebook and LinkedIn read when someone shares a link.
 *
 * Most pages never set their own sharing text and fall back to the page title
 * and description. That fallback is what broke in production: the layout
 * printed the template directive itself instead of the title, so every shared
 * link showed "@yield('title', 'Universal Brothers')". These tests cover the
 * fallback, not only the case where a CMS page sets its own values.
 */
class SharingTagsTest extends TestCase
{
    use RefreshDatabase;

    /** Pages that do not set any sharing fields of their own. */
    public static function fallbackPages(): array
    {
        return [
            'home' => ['/'],
            'contact' => ['/contact'],
            'hajj listing' => ['/hajj'],
            'faqs' => ['/faqs'],
        ];
    }

    /** The listing pages need a category with something published in it. */
    private function seedSite(): void
    {
        Office::factory()->create();
        $category = PackageCategory::factory()->create(['name' => 'Hajj', 'slug' => 'hajj']);
        Package::factory()->create(['package_category_id' => $category->id, 'name' => 'Executive Platinum']);
    }

    #[DataProvider('fallbackPages')]
    public function test_a_page_without_its_own_sharing_text_shares_its_title_and_description(string $path): void
    {
        $this->seedSite();

        $html = $this->get($path)->assertOk()->getContent();

        preg_match('~<title>(.*?)</title>~s', $html, $title);
        preg_match('~<meta name="description" content="([^"]*)"~', $html, $description);
        preg_match('~<meta property="og:title" content="([^"]*)"~', $html, $ogTitle);
        preg_match('~<meta property="og:description" content="([^"]*)"~', $html, $ogDescription);

        $this->assertNotEmpty($title[1] ?? '', "{$path} has no page title.");
        $this->assertSame(trim($title[1]), trim($ogTitle[1] ?? ''), "{$path} does not share its own title.");
        $this->assertSame(trim($description[1] ?? ''), trim($ogDescription[1] ?? ''), "{$path} does not share its own description.");
    }

    #[DataProvider('fallbackPages')]
    public function test_no_public_page_prints_a_template_directive(string $path): void
    {
        $this->seedSite();

        $html = $this->get($path)->assertOk()->getContent();

        foreach (['@yield(', '@hasSection', '@section(', '@endif'] as $directive) {
            $this->assertStringNotContainsString($directive, $html, "{$path} printed the directive {$directive} instead of running it.");
        }
    }

    public function test_a_cms_page_still_shares_the_text_set_in_the_admin(): void
    {
        // An ampersand proves the value is escaped once, not twice.
        Page::create([
            'title' => 'Hajj Guide', 'slug' => 'hajj-guide', 'template' => 'default',
            'status' => 'published', 'is_active' => true, 'published_at' => now()->subDay(),
            'sections' => [['id' => 's_share0001', 'type' => 'text', 'visible' => true, 'data' => ['content' => '<p>Guide.</p>']]],
            'meta_title' => 'Hajj Guide | Universal Brothers',
            'meta_description' => 'Everything to prepare.',
            'og_title' => 'Hajj & Umrah — share title',
            'og_description' => 'Share description.',
        ]);

        $this->get('/hajj-guide')->assertOk()
            ->assertSee('<meta property="og:title" content="Hajj &amp; Umrah — share title">', false)
            ->assertSee('<meta property="og:description" content="Share description.">', false)
            ->assertDontSee('&amp;amp;', false);
    }

    /**
     * The head tags print section values raw, because Blade has already
     * escaped them when the section was set. This proves that: markup typed
     * into the admin's search and sharing fields reaches the page as text,
     * never as a tag, and never breaks out of the attribute it sits in.
     */
    public function test_markup_typed_into_the_sharing_fields_is_escaped_not_rendered(): void
    {
        $hostile = 'Hajj "Deal" & <script>alert(1)</script> <b>x</b> \'quote\'';

        Page::create([
            'title' => 'Hostile', 'slug' => 'hostile-page', 'template' => 'default',
            'status' => 'published', 'is_active' => true, 'published_at' => now()->subDay(),
            'sections' => [['id' => 's_share0003', 'type' => 'text', 'visible' => true, 'data' => ['content' => '<p>Body.</p>']]],
            'meta_title' => $hostile,
            'meta_description' => $hostile,
            'og_title' => $hostile,
            'og_description' => $hostile,
        ]);

        $html = $this->get('/hostile-page')->assertOk()->getContent();
        $head = substr($html, 0, (int) strpos($html, '</head>'));

        // Nothing executable survives in the head, and no attribute is broken open.
        $this->assertStringNotContainsString('<script>alert(1)</script>', $head);
        $this->assertStringNotContainsString('<b>x</b>', $head);
        $this->assertSame(0, substr_count($head, '<script>alert'), 'A script tag was written into the head.');

        // Each tag holds the value as text, escaped exactly once.
        $expected = 'Hajj &quot;Deal&quot; &amp; &lt;script&gt;alert(1)&lt;/script&gt; &lt;b&gt;x&lt;/b&gt; &#039;quote&#039;';
        $this->assertStringContainsString('<meta property="og:title" content="'.$expected.'">', $head);
        $this->assertStringContainsString('<meta property="og:description" content="'.$expected.'">', $head);
        $this->assertStringContainsString('<title>'.$expected.'</title>', $head);
        $this->assertStringContainsString('<meta name="description" content="'.$expected.'">', $head);

        // Escaped once, not twice: decoding gives back exactly what was typed.
        preg_match('~<meta property="og:title" content="([^"]*)"~', $head, $m);
        $this->assertSame($hostile, html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        $this->assertStringNotContainsString('&amp;amp;', $head);
        $this->assertStringNotContainsString('&amp;lt;', $head);
        $this->assertStringNotContainsString('&amp;quot;', $head);
    }

    public function test_a_cms_page_without_sharing_text_falls_back_to_its_own_title(): void
    {
        Page::create([
            'title' => 'Refund Policy', 'slug' => 'refund-policy-test', 'template' => 'default',
            'status' => 'published', 'is_active' => true, 'published_at' => now()->subDay(),
            'sections' => [['id' => 's_share0002', 'type' => 'text', 'visible' => true, 'data' => ['content' => '<p>Policy.</p>']]],
            'meta_title' => 'Refund Policy | Universal Brothers',
            'meta_description' => 'How refunds work.',
        ]);

        $html = $this->get('/refund-policy-test')->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:title" content="Refund Policy | Universal Brothers">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="How refunds work.">', $html);
    }
}
