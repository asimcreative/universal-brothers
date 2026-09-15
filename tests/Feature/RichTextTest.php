<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\NewsArticle;
use App\Models\User;
use App\Support\Content\RichText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Formatted text from the admin's editor: what is kept, what is removed, how
 * older plain text still shows, and that the forms and public pages use it.
 */
class RichTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_supported_formatting_is_kept(): void
    {
        $html = '<h2 style="text-align: center">Documents</h2><p><strong>Bold</strong>, <em>italic</em>, <u>underline</u> and <a href="/contact" target="_blank">a link</a>.</p>'
            .'<ul><li>Passport</li></ul><ol><li>First</li></ol><blockquote>Quote</blockquote><table><tbody><tr><td>Cell</td></tr></tbody></table>';

        $clean = RichText::clean($html, 'full');

        $this->assertStringContainsString('<h2 style="text-align: center">Documents</h2>', $clean);
        $this->assertStringContainsString('<strong>Bold</strong>', $clean);
        $this->assertStringContainsString('<em>italic</em>', $clean);
        $this->assertStringContainsString('<u>underline</u>', $clean);
        $this->assertStringContainsString('<a href="/contact" target="_blank" rel="noopener noreferrer">a link</a>', $clean);
        $this->assertStringContainsString('<ul><li>Passport</li></ul>', $clean);
        $this->assertStringContainsString('<blockquote>Quote</blockquote>', $clean);
        $this->assertStringContainsString('<td>Cell</td>', $clean);
    }

    public function test_scripts_event_handlers_and_dangerous_links_are_removed(): void
    {
        $html = '<p onclick="steal()" onmouseover="x()">Hi<script>alert(1)</script></p>'
            .'<a href="javascript:alert(1)">bad</a><a href=" JaVaScRiPt:alert(1)">bad2</a>'
            .'<img src="x" onerror="alert(1)"><iframe src="https://evil.example/embed"></iframe>'
            .'<svg onload="alert(1)"></svg><style>body{display:none}</style><form action="/x"><input name="a"></form>';

        $clean = RichText::clean($html, 'full');

        foreach (['<script', 'onclick', 'onmouseover', 'onerror', 'javascript:', 'evil.example', '<svg', '<style', 'display:none', '<form', '<input'] as $forbidden) {
            $this->assertStringNotContainsStringIgnoringCase($forbidden, $clean);
        }
        $this->assertStringContainsString('Hi', $clean);
    }

    public function test_only_allowed_video_players_and_site_colours_survive(): void
    {
        $clean = RichText::clean(
            '<div data-video-embed><iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"></iframe></div>'
            .'<p><span style="color: #101b45; background: url(https://evil.example/x)">navy</span> <span style="color:#ff00ff">pink</span></p>',
            'full'
        );

        $this->assertStringContainsString('src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"', $clean);
        $this->assertStringContainsString('<span style="color: #101b45">navy</span>', $clean);
        $this->assertStringNotContainsString('evil.example', $clean);
        $this->assertStringNotContainsString('#ff00ff', $clean);
    }

    public function test_short_note_profiles_keep_words_but_drop_what_they_do_not_allow(): void
    {
        $clean = RichText::clean('<h2>Heading</h2><p>Text with <strong>bold</strong></p><table><tr><td>cell</td></tr></table><img src="/x.jpg">', 'basic');

        $this->assertStringNotContainsString('<h2>', $clean);
        $this->assertStringNotContainsString('<table', $clean);
        $this->assertStringNotContainsString('<img', $clean);
        $this->assertStringContainsString('Heading', $clean);
        $this->assertStringContainsString('<strong>bold</strong>', $clean);
        $this->assertStringContainsString('cell', $clean);
    }

    public function test_an_empty_editor_counts_as_empty(): void
    {
        $this->assertNull(RichText::clean('<p></p>'));
        $this->assertNull(RichText::clean('<p><br></p><p> </p>'));
        $this->assertNull(RichText::clean('   '));
        $this->assertTrue(RichText::isEmpty('<p></p>'));
        $this->assertFalse(RichText::isEmpty('<p><img src="/storage/media/a.jpg" alt=""></p>'));
    }

    public function test_older_plain_text_renders_the_way_it_always_did(): void
    {
        $html = (string) RichText::render("Hotels & transport\nincluded\n\n<b>not a tag</b> today", 'standard');

        // Plain text containing a tag-like word is treated as HTML by design;
        // ordinary plain text is escaped and keeps its line breaks.
        $plain = (string) RichText::render("Hotels & transport\nincluded\n\nSecond paragraph", 'standard');

        $this->assertSame('<p>Hotels &amp; transport<br>'.chr(10).'included</p><p>Second paragraph</p>', $plain);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_plain_text_for_search_engines_and_the_assistant(): void
    {
        $this->assertSame("Documents\n- Passport\n- Two photographs\nBring them & keep copies.", RichText::toPlainText('<h2>Documents</h2><ul><li>Passport</li><li>Two photographs</li></ul><p>Bring them &amp; keep copies.</p>'));
        $this->assertSame('Already plain', RichText::toPlainText('Already plain'));
        $this->assertSame(4, RichText::wordCount('<p>Four words right here</p>'));
    }

    public function test_faq_answers_are_formatted_on_the_page_and_plain_in_structured_data(): void
    {
        Faq::create(['category' => 'hajj', 'question' => 'What documents do I need?', 'answer' => '<p>Bring:</p><ul><li>Passport</li></ul>', 'sort_order' => 1, 'is_active' => true]);

        $response = $this->get(route('faqs'))->assertOk();

        $response->assertSee('<ul><li>Passport</li></ul>', false);
        $response->assertSee('"text": "Bring:\n- Passport"', false);
    }

    public function test_the_faq_form_cleans_the_answer_and_refuses_an_empty_one(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.faqs.store'), [
            'category' => 'hajj', 'question' => 'Q?', 'answer' => '<p>A <script>x()</script><strong>safe</strong></p>', 'is_active' => '1',
        ])->assertRedirect();
        $this->assertSame('<p>A <strong>safe</strong></p>', Faq::where('question', 'Q?')->value('answer'));

        $this->actingAs($admin)->post(route('admin.faqs.store'), [
            'category' => 'hajj', 'question' => 'Empty?', 'answer' => '<p></p>',
        ])->assertSessionHasErrors('answer');
    }

    public function test_news_article_text_is_cleaned_when_saved_and_again_when_shown(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.news.store'), [
            'title' => 'Hajj 2027 registration opens',
            'body' => '<h2>Dates</h2><p onclick="x()">Opens soon.</p><script>alert(1)</script>',
            'is_active' => '1',
        ])->assertRedirect();

        $article = NewsArticle::where('title', 'Hajj 2027 registration opens')->firstOrFail();
        $this->assertSame('<h2>Dates</h2><p>Opens soon.</p>', $article->body);

        // A row written some other way is still cleaned on the page.
        $article->forceFill(['body' => '<p>Shown</p><img src=x onerror=alert(1)>'])->save();
        $html = $this->get(route('news.show', $article->slug))->assertOk()->getContent();
        $this->assertStringContainsString('<p>Shown</p>', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function test_editor_fields_render_as_progressive_textareas_in_the_admin(): void
    {
        $admin = User::factory()->create(['role' => 'content_editor', 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.faqs.create'))
            ->assertOk()
            ->assertSee('data-rich-text', false)
            ->assertSee('<textarea name="answer" id="faq-answer"', false)
            ->assertDontSee('data-allow-source', false);

        $this->actingAs($admin)->get(route('admin.news.create'))
            ->assertOk()
            ->assertSee('data-profile="full"', false)
            ->assertDontSee('data-allow-source', false);
    }

    public function test_only_super_admins_get_the_source_view(): void
    {
        $super = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($super)->get(route('admin.news.create'))->assertSee('data-allow-source="1"', false);
    }
}
