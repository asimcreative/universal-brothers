<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Real, named testimonials recovered from the live tourism.universalbrothers.com
 * homepage carousel (see EXISTING_WEBSITE_AUDIT.md). All four are Hajj/Umrah
 * reviews despite living on the tourism site currently — tagged here by the
 * service they actually describe rather than by page of origin. Standard
 * practice: the client should reconfirm consent to reuse before final launch.
 */
class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'name' => 'Haseeb Jawed',
                'quote' => 'Universal Brothers are top quality tour operators, we have had a fabulous experience with them performing Umrah. We stayed in 5 Star Luxurious hotels...',
                'service_tag' => 'umrah',
                'sort_order' => 1,
            ],
            [
                'name' => 'Mustafa Aslam',
                'quote' => 'We went on Hajj in 2018 and I will say that I was impressed with their services...',
                'service_tag' => 'hajj',
                'sort_order' => 2,
            ],
            [
                'name' => 'Rehan Ahmed',
                'quote' => '...experience of more than a decade, they are immensely experienced religious tour operator...',
                'service_tag' => 'hajj',
                'sort_order' => 3,
            ],
            [
                'name' => 'Ali Naviwala',
                'quote' => 'I performed Hajj for the first time and was told about the quality of their service by my friend...',
                'service_tag' => 'hajj',
                'sort_order' => 4,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::updateOrCreate(
                ['name' => $testimonial['name'], 'quote' => $testimonial['quote']],
                array_merge($testimonial, [
                    'source' => 'Recovered from tourism.universalbrothers.com homepage carousel (live site audit)',
                    'is_active' => true,
                ])
            );
        }
    }
}
