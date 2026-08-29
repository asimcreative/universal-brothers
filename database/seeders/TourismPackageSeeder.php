<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageSeries;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds real tourism package names/categories recovered from the live
 * tourism.universalbrothers.com listing pages (see EXISTING_WEBSITE_AUDIT.md).
 * Every individual product detail page on that live site currently returns
 * HTTP 500, so itineraries/inclusions/hotel names are genuinely unavailable
 * — those fields are left null/empty here for the client or admin to fill
 * in, rather than invented. Only "The Splendid Skardu Tour Direct from
 * Dubai" has a confirmed real price (₨197,500) recovered from the listing
 * grid; every other price is left null.
 */
class TourismPackageSeeder extends Seeder
{
    public function run(): void
    {
        $category = PackageCategory::where('slug', 'tourism')->firstOrFail();
        $domestic = PackageSeries::where('slug', 'domestic')->firstOrFail();
        $international = PackageSeries::where('slug', 'international')->firstOrFail();

        $domesticTours = [
            'Eid & Spring Hunza Tour',
            'Eid & Spring Skardu Tour',
            'Winter Malam Jabba Tour',
            'The Splendid Skardu Tour Direct from Dubai',
            'Bhurban Tour',
            'Gilgit Tour',
            'Kashmir Tour',
            'Malam Jabba Tour',
            'Skardu 4N/5D Tour — Standard',
            'Skardu 4N/5D Tour — Economy',
            'Kaghan Valley 5N/6D Tour — Standard',
            'Kaghan Valley 5N/6D Tour — Economy',
            'Skardu / Hunza / Gilgit 8N/9D Tour — Standard',
            'Skardu / Hunza / Gilgit 8N/9D Tour — Economy',
        ];

        $internationalTours = [
            'Europe Tour',
            'Maldives Tour',
            'Jordan Tour',
            'Bintan Island (Indonesia) Tour',
            'Egypt Tour',
            'Thailand Tour',
            'Sri Lanka Tour',
            'Singapore Tour',
            'Turkey Tour',
            'Indonesia Tour',
            'Dubai Tour',
            'Malaysia / Singapore / Thailand Combo Tour',
            'Europe Holidays',
            'Road To Turkey',
            'Dubai 5D/4N Tour',
            'South Africa Tour',
            'Maldives Honeymoon Package',
            'Malaysia Tour',
            'Baku + Dubai Tour',
            'Hong Kong Tour',
            'The Great China Package',
        ];

        $sortOrder = 0;
        foreach ($domesticTours as $name) {
            $this->seedPackage($category->id, $domestic->id, $name, ++$sortOrder,
                $name === 'The Splendid Skardu Tour Direct from Dubai' ? 197500.00 : null);
        }

        foreach ($internationalTours as $name) {
            $this->seedPackage($category->id, $international->id, $name, ++$sortOrder, null);
        }
    }

    private function seedPackage(int $categoryId, int $seriesId, string $name, int $sortOrder, ?float $price): void
    {
        $slug = Str::slug($name);

        Package::updateOrCreate(
            ['package_category_id' => $categoryId, 'slug' => $slug],
            [
                'package_series_id' => $seriesId,
                'name' => $name,
                'summary' => 'Recovered from the live tourism.universalbrothers.com listing pages. Full itinerary, inclusions, and hotel details pending — every individual product detail page on the live site currently returns a server error, so this content could not be recovered and is not invented here.',
                'currency' => 'PKR',
                'starting_price' => $price,
                'is_featured' => false,
                'is_seasonal' => false,
                'status' => 'published',
                'published_at' => now(),
                'sort_order' => $sortOrder,
                'meta_title' => $name.' | Universal Brothers Tourism',
                'meta_description' => 'Domestic and international tourism packages from Universal Brothers.',
            ]
        );
    }
}
