<?php

namespace Database\Seeders;

use App\Models\Hotel;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    /**
     * Real hotel names as printed in the Hajj 2027 brochure (Makkah & Medinah
     * Hotels pages) — no names invented.
     */
    public function run(): void
    {
        $hotels = [
            ['name' => 'Dar Al Tawhid Intercontinental Makkah', 'slug' => 'dar-al-tawhid-intercontinental-makkah', 'city' => 'Makkah', 'star_rating' => 5, 'sort_order' => 1],
            ['name' => 'Fairmont Clock Tower', 'slug' => 'fairmont-clock-tower', 'city' => 'Makkah', 'star_rating' => 5, 'sort_order' => 2],
            ['name' => 'Swissotel Makkah', 'slug' => 'swissotel-makkah', 'city' => 'Makkah', 'star_rating' => 5, 'sort_order' => 3],
            ['name' => 'Makkah Tower (Hajar Tower)', 'slug' => 'makkah-tower', 'city' => 'Makkah', 'star_rating' => 4, 'sort_order' => 4],
            ['name' => 'Voco Makkah by IHG', 'slug' => 'voco-makkah-by-ihg', 'city' => 'Makkah', 'star_rating' => 4, 'sort_order' => 5],
            ['name' => 'Abraaj Tower / Swiss Maqam', 'slug' => 'abraaj-tower-swiss-maqam', 'city' => 'Makkah', 'star_rating' => 5, 'description' => 'Abraaj Tower means the Swiss Maqam, Hajar Tower, Swissotel, Safwa Orchid, Al Marwa complex and the Jabal e Omar project (Hayat Regency, Address Hotel, Jumeirah Hotel, Hilton Convention, Double Tree, Marriott Hotel etc.) per the brochure\'s own definition.', 'sort_order' => 6],
            ['name' => 'Dar Al Taqwa', 'slug' => 'dar-al-taqwa', 'city' => 'Medinah', 'star_rating' => 5, 'description' => 'In front of Haram, Non-Aziziya series first stop.', 'sort_order' => 7],
            ['name' => 'Taibah Front Medinah', 'slug' => 'taibah-front-medinah', 'city' => 'Medinah', 'star_rating' => 3, 'sort_order' => 8],
            ['name' => 'Al Aqeeq / Dallah Taibah', 'slug' => 'al-aqeeq-dallah-taibah', 'city' => 'Medinah', 'star_rating' => 3, 'sort_order' => 9],
        ];

        foreach ($hotels as $hotel) {
            Hotel::updateOrCreate(['slug' => $hotel['slug']], $hotel);
        }
    }
}
