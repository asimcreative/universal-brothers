<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Seeds a real "About Us" page from facts actually recovered from the
 * live hajjumrah.universalbrothers.com site and the Hajj 2027 brochure
 * (see EXISTING_WEBSITE_AUDIT.md / PROJECT_DISCOVERY.md) — no company
 * history, award, or leadership claim here is invented.
 */
class AboutPageSeeder extends Seeder
{
    public function run(): void
    {
        $body = <<<'HTML'
<p>Universal Brothers (Pvt) Ltd is a company of Maxim's Group, operating as an Umrah &amp; Hajj Organizer and Travel &amp; Tours Operator under the brand "Crown Packages" — <strong>The Leader &amp; Trend Setter</strong>.</p>

<p>With more than <strong>20 years</strong> in operation, Universal Brothers has served over <strong>50,000 pilgrims</strong>, earning recognition as an IATA-registered travel agency licensed and affiliated with the Ministry of Religious Affairs (Pakistan), TAAP, PHGOC, ELAF, FPCCI, HOAP, DTS, SECP and KCCI.</p>

<h2>Leadership</h2>
<p>The company is led by <strong>Furqan Abdul Qadir</strong> (Chief Executive) and <strong>Junaid Abdul Qadir</strong> (Director) — sons of Maxim's Group founder Abdul Qadir.</p>

<h2>Recognition</h2>
<ul>
<li>FPCCI Achievement Award (Gold Medal)</li>
<li>Who's Who Pakistan Award</li>
<li>Quality Standard Award</li>
<li>Best Hajj Operator in Pakistan — World Hajj &amp; Umrah Convention (WHUC), London Olympia</li>
<li>Consumers Choice Award</li>
<li>Brand Icon of Pakistan Award (2010–2011)</li>
<li>Brands of the Year Award — 25 years of excellence</li>
</ul>

<h2>Why Choose Universal Brothers</h2>
<ul>
<li>Flexibility to choose from multiple Hajj package durations</li>
<li>Choice of hotels near Haram in Makkah and Madinah</li>
<li>Best location in Mina, near Jamarat, Zone 1 Category A</li>
<li>Specially designed air-conditioned tents with private bathrooms</li>
<li>Mufti/Aalim available for ritual guidance throughout the journey</li>
<li>Personalized, escorted service at every step of Hajj</li>
</ul>
HTML;

        Page::updateOrCreate(
            ['slug' => 'about-us'],
            [
                'title' => 'About Us',
                'body' => $body,
                'template' => 'about',
                'is_active' => true,
                'published_at' => now(),
                'meta_title' => 'About Us | Universal Brothers',
                'meta_description' => 'Universal Brothers (Pvt) Ltd — 20+ years of trusted Hajj, Umrah and Tourism service, 50,000+ pilgrims served, IATA-registered.',
            ]
        );
    }
}
