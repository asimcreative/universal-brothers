<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * FINAL_CODE_REVIEW.md M-8: the footer's Privacy Policy / Terms & Conditions /
 * Refund Policy links pointed at `href="#"` despite the public forms
 * collecting real PII (CNIC, passport number, blood group, next-of-kin). No
 * actual legal wording exists in any source document, so — per this
 * project's rule against inventing business/legal facts — these seed
 * transparent placeholder pages (not fabricated policy text) that are
 * immediately editable through the existing Pages admin CMS, rather than
 * leaving the links dead.
 */
class LegalPageSeeder extends Seeder
{
    public function run(): void
    {
        $placeholder = fn (string $title) => <<<HTML
<p>Our {$title} is currently pending final legal review and will be published here shortly.</p>
<p>If you have a question about how your information is collected, used, or retained in the meantime, please <a href="/contact">contact us directly</a>.</p>
HTML;

        $pages = [
            ['slug' => 'privacy-policy', 'title' => 'Privacy Policy'],
            ['slug' => 'terms-and-conditions', 'title' => 'Terms & Conditions'],
            ['slug' => 'refund-policy', 'title' => 'Refund Policy'],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'body' => $placeholder($page['title']),
                    'template' => 'default',
                    'is_active' => true,
                    'published_at' => now(),
                    'meta_title' => $page['title'].' | Universal Brothers',
                ]
            );
        }
    }
}
