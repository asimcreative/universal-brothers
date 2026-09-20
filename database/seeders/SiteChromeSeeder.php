<?php

namespace Database\Seeders;

use App\Models\NewsArticle;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * The two things the header's top strip is made of: the announcements that
 * feed the ticker, and a link for each social platform so its icon appears.
 *
 * A seeder rather than a one-off script because both had been set by hand in
 * the database, and `migrate:fresh --seed` wiped them — the ticker went silent
 * and eight of the nine social icons vanished, with nothing in the repository
 * to put them back. Anything the chrome needs in order to look finished has to
 * be reproducible from the repository.
 *
 * Idempotent, twice over: an announcement is created only when its slug is
 * absent, and a platform is given a placeholder only when nothing is saved for
 * it. Running this on a database where the client has entered their real
 * Facebook address will not overwrite it.
 *
 * Every announcement below states something the database or the approved
 * brochure copy already says. Nothing here invents a deadline, a seat count,
 * a price or an offer — those are the client's to write.
 */
class SiteChromeSeeder extends Seeder
{
    /**
     * The platforms the header and footer know how to render. `#` is a
     * deliberate placeholder: the client asked for the icons to be visible
     * before they had gathered the addresses, and an admin replaces each one
     * from Settings without a developer.
     */
    private const PLATFORMS = [
        'social_facebook', 'social_instagram', 'social_youtube', 'social_tiktok',
        'social_linkedin', 'social_x', 'social_threads', 'social_pinterest',
    ];

    private const ANNOUNCEMENTS = [
        [
            'slug' => 'hajj-2027-programme-published',
            'title' => 'Hajj 2027 (1448 AH) programme is published',
            'excerpt' => 'Every Hajj 2027 package is online with its real itinerary, hotels and pricing.',
            'body' => '<p>Our full Hajj 2027 (1448 AH) programme is now published. Each package carries its own itinerary, its confirmed hotels in Makkah and Madinah, and its pricing — nothing is listed that is not arranged.</p><p>Both Madinah-first and Makkah-first routings are available, in shifting and non-shifting options.</p>',
        ],
        [
            'slug' => 'mina-camp-zone-1-category-a',
            'title' => 'Our Mina camp is Zone 1, Category A — near Jamarat',
            'excerpt' => 'Where our pilgrims stay during the days of Hajj, and why the location matters.',
            'body' => '<p>Universal Brothers camps in Zone 1, Category A in Mina, close to Jamarat. Tents are air-conditioned with private bathrooms.</p><p>The walk to Jamarat is the part of Hajj most affected by where a camp sits, which is why this is the first thing we are asked about and the first thing we confirm.</p>',
        ],
        [
            'slug' => 'umrah-all-year-round',
            'title' => 'Umrah arranged all year round, around your own dates',
            'excerpt' => 'Individual, family and group Umrah, planned to suit your travel dates.',
            'body' => '<p>Umrah is arranged throughout the year and through every Ramadan, for individuals, families and groups.</p><p>Dates, duration, accommodation and travel are planned around you rather than around a fixed departure.</p>',
        ],
        [
            'slug' => 'iata-registered-licensed-operator',
            'title' => 'IATA-registered operator, Hajj Licence No. 2014',
            'excerpt' => 'The registrations and licences Universal Brothers operates under.',
            'body' => '<p>Universal Brothers (Pvt) Ltd is an IATA-registered travel agency and holds Hajj Licence No. 2014. We are a company of Maxim\'s Group, operating under the brand &ldquo;Crown Packages&rdquo;.</p>',
        ],
        [
            'slug' => 'tours-pakistan-and-abroad',
            'title' => 'Tours across northern Pakistan and destinations abroad',
            'excerpt' => 'Leisure travel, planned by the same team that arranges Hajj and Umrah.',
            'body' => '<p>Alongside Hajj and Umrah we arrange leisure travel — the northern valleys of Pakistan, and destinations abroad.</p><p>Visas, flights and hotels are handled by the same team.</p>',
        ],
    ];

    public function run(): void
    {
        $placeholders = 0;

        foreach (self::PLATFORMS as $key) {
            if (trim((string) SiteSetting::get($key)) !== '') {
                continue;
            }

            SiteSetting::set($key, '#');
            $placeholders++;
        }

        $created = 0;

        foreach (self::ANNOUNCEMENTS as $i => $article) {
            if (NewsArticle::where('slug', $article['slug'])->exists()) {
                continue;
            }

            NewsArticle::create([
                'title' => $article['title'],
                'slug' => $article['slug'],
                'excerpt' => $article['excerpt'],
                'body' => $article['body'],
                'is_active' => true,
                // A day apart, so the order in the ticker is stable and
                // deliberate rather than five rows sharing one timestamp.
                'published_at' => now()->subDays(count(self::ANNOUNCEMENTS) - $i),
                'meta_title' => $article['title'].' | Universal Brothers',
                'meta_description' => $article['excerpt'],
            ]);

            $created++;
        }

        $this->command?->info(sprintf(
            'SiteChromeSeeder: %d announcement(s) created, %d social placeholder(s) set.',
            $created,
            $placeholders
        ));
    }
}
